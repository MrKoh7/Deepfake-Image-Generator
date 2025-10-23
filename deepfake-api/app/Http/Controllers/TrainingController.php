<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class TrainingController extends Controller
{
    public function train(Request $request)
    {
        $request->validate([
            'dataset_path' => 'required|string',
        ]);

        // ✅ Python setup
        $pythonPath = "C:\\FYPImageGenerator\\deepfake-api\\venv\\Scripts\\python.exe";
        $scriptPath = base_path('python/train64.py');
        $datasetPath = $request->input('dataset_path');

        // ✅ Per-user run-specific model directory
        $userId = $request->user()->id ?? 'guest';
        $timestamp = date('Ymd_His'); // unique run timestamp
        $modelDir = storage_path("app/public/models/$userId/run_$timestamp");

        if (!is_dir($modelDir)) {
            mkdir($modelDir, 0777, true);
        }

        // ✅ Merge env vars so Python works on Windows
        $env = array_merge(getenv(), [
            'PYTHONHASHSEED'   => '0',
            'PYTHONIOENCODING' => 'utf-8',
            'SystemRoot'       => getenv('SystemRoot') ?: 'C:\\Windows',
            'TEMP'             => getenv('TEMP') ?: sys_get_temp_dir(),
            'TMP'              => getenv('TMP') ?: sys_get_temp_dir(),
        ]);

        // ✅ Run training process
        $process = new Process([$pythonPath, $scriptPath, $datasetPath, $modelDir]);
        $process->setTimeout(36000); // 10 hours max
        $process->setEnv($env);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json([
                'status'  => 'error',
                'message' => $process->getErrorOutput() ?: 'Training failed',
            ], 500);
        }

        // ✅ Verify files exist
        $finalModel = $modelDir . "/generator_final.h5";
        $logFile    = $modelDir . "/training_log.txt";

        if (!file_exists($finalModel)) {
            return response()->json(['status' => 'error', 'message' => 'No final model generated'], 500);
        }

        // ✅ Create single ZIP file for this run only
        $zipFile = $modelDir . "/trained_model.zip";
        if (file_exists($zipFile)) unlink($zipFile);

        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE) === TRUE) {
            // Add final model
            $zip->addFile($finalModel, "generator_final.h5");

            // Add training log
            if (file_exists($logFile)) {
                $zip->addFile($logFile, "training_log.txt");
            }

            // Add only sample images from this run
            $samplesDir = $modelDir . "/samples";
            if (is_dir($samplesDir)) {
                foreach (glob($samplesDir . "/*") as $sf) {
                    if (is_file($sf)) {
                        $zip->addFile($sf, "samples/" . basename($sf));
                    }
                }
            }

            $zip->close();
        }


        // ✅ Return response
        return response()->json([
            'status'       => 'success',
            'message'      => 'Training completed',
            'zip_download' => url(str_replace(storage_path('app/public'), 'storage', $zipFile)),
            'model_path'   => $finalModel, // path to final trained model
            'output'       => $process->getOutput(),
        ]);
    }
}
