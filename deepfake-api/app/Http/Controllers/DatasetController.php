<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DatasetController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|image|mimes:jpg,jpeg,png|max:4096'
        ]);

        // Get user ID or "guest" if not logged in
        $userId = $request->user()->id ?? 'guest';
        $datasetPath = "datasets/$userId";

        // Make sure the folder exists
        Storage::makeDirectory("public/$datasetPath");

        // Save uploaded files
        foreach ($request->file('files') as $file) {
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs("public/$datasetPath", $filename);
        }

        // Full absolute path for training script
        $absolutePath = storage_path("app/public/$datasetPath");

        return response()->json([
            'status' => 'success',
            'message' => 'Dataset uploaded',
            'path' => $absolutePath,   // will be passed into TrainingController
            'relative' => "storage/$datasetPath", // optional for frontend reference
        ]);
    }
}
