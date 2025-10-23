<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class GenerationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'model' => 'required|string|in:asian_male,asian_female,european_male,european_female,africa_male',
            'count' => 'sometimes|integer|min:1|max:20',
        ]);

        $modelKey = $request->input('model');
        $count    = $request->input('count', 1);

        // Python executable in venv
        $pythonPath = "C:\\FYPImageGenerator\\deepfake-api\\venv\\Scripts\\python.exe";
        $scriptPath = base_path('python/infer.py');

        $env = array_merge(getenv(), [
            'PATH'             => getenv('PATH'),
            'PYTHONHASHSEED'   => '0',
            'PYTHONIOENCODING' => 'utf-8',
            'SystemRoot'       => getenv('SystemRoot') ?: 'C:\\Windows',
            'TEMP'             => getenv('TEMP') ?: sys_get_temp_dir(),
            'TMP'              => getenv('TMP') ?: sys_get_temp_dir(),
        ]);

        $process = new Process(
            [$pythonPath, $scriptPath, $modelKey, strval($count)],
            base_path(),
            $env
        );
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json([
                'status'  => 'error',
                'message' => $process->getErrorOutput() ?: $process->getOutput() ?: 'Python process failed'
            ], 500);
        }

        $raw  = trim($process->getOutput());
        $data = json_decode($raw, true);

        if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
            return response()->json([
                'status'  => 'error',
                'message' => $raw ?: 'Invalid Python response'
            ], 500);
        }

        $urls    = [];
        $names   = [];
        $sources = [];

        foreach ($data['filenames'] as $fileObj) {
            $filePath   = $fileObj['path'] ?? '';
            $fileSource = $fileObj['source'] ?? 'unknown';

            if (!file_exists($filePath)) {
                continue;
            }

            $ethnicityFolder = "generated/{$modelKey}";
            $newFileName     = basename($filePath);
            $storagePath     = "public/{$ethnicityFolder}/{$newFileName}";

            // copy the file into storage
            Storage::put($storagePath, file_get_contents($filePath));

            // Relative path for Laravel’s `url()`
            $relativePath = "storage/{$ethnicityFolder}/{$newFileName}";

            $names[]   = $relativePath;
            $sources[] = $fileSource;
            $urls[]    = url($relativePath);
        }

        return response()->json([
            'status'      => 'success',
            'message'     => $data['message'] ?? 'OK',
            'image_urls'  => $urls,
            'image_names' => $names,
            'sources'     => $sources,
        ]);
    }
}
