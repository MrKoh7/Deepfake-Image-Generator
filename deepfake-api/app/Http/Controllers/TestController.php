<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;


class TestController extends Controller
{
    public function test(Request $request)
    {
        $pythonPath = "C:\\FYPImageGenerator\\deepfake-api\\venv\\Scripts\\python.exe";
        $scriptPath = base_path('python/test_generator.py');

        // Per-user model directory
        $userId = $request->user()->id ?? 'guest';
        $modelRoot = storage_path("app/public/models/$userId");

        if (!is_dir($modelRoot)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'No trained models found for this user.',
            ], 404);
        }

        // 🔎 Look for generator_final.h5 in all run_* subfolders
        $files = glob($modelRoot . "/run_*/generator_final.h5");
        if (empty($files)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No generator model (.h5) found.',
            ], 404);
        }

        // Pick the most recent run by file modified time
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $modelPath = $files[0]; // latest generator_final.h5

        // Per-user test output folder (inside same run folder)
        $outputDir = dirname($modelPath) . "/tests";
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // ✅ Environment vars (same as TrainingController)
        $env = array_merge(getenv(), [
            'PATH'              => getenv('PATH'),
            'PYTHONHASHSEED'    => '0',
            'PYTHONIOENCODING'  => 'utf-8',
            'SystemRoot'        => getenv('SystemRoot') ?: 'C:\\Windows',
            'TEMP'              => getenv('TEMP') ?: sys_get_temp_dir(),
            'TMP'               => getenv('TMP') ?: sys_get_temp_dir(),
        ]);

        // Run Python test script
        $process = new Process(
            [$pythonPath, $scriptPath, $modelPath, $outputDir],
            base_path(),
            $env
        );
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json([
                'status' => 'error',
                'message' => $process->getErrorOutput() ?: 'Test failed',
            ], 500);
        }

        $result = json_decode($process->getOutput(), true);

        if (!is_array($result) || ($result['status'] ?? '') !== 'success') {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'] ?? 'Invalid response from test script',
            ], 500);
        }

        // Convert test image path → public URL
        $publicPath = str_replace(storage_path('app/public'), 'storage', $result['image_path']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Test image generated',
            'image'   => url($publicPath),
        ]);
    }
}
