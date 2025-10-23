<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ZipArchive;

class ZipController extends Controller
{
    public function downloadZip(Request $request)
    {
        // Expecting relative paths to (e.g. storage/generated/... or storage/good_pool/...)
        $files = $request->input('files', []);

        if (empty($files)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No files provided'
            ], 400);
        }

        $zipFileName = 'generated_images_' . time() . '.zip';
        $zipPath = storage_path('app/public/' . $zipFileName);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            foreach ($files as $relativePath) {
                // Convert relative path into absolute system path
                $filePath = public_path($relativePath);

                if (file_exists($filePath)) {
                    // Store only the file name inside the zip
                    $zip->addFile($filePath, basename($filePath));
                }
            }
            $zip->close();
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not create zip'
            ], 500);
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}
