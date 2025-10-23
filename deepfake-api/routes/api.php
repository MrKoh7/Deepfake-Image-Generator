<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

use App\Http\Controllers\GenerationController;
use App\Http\Controllers\ZipController;
use App\Http\Controllers\DatasetController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\AuthController;


// endpoints
Route::post('/generate', [GenerationController::class, 'store']);
Route::post('/download-zip', [ZipController::class, 'downloadZip']);
Route::post('/upload-dataset', [DatasetController::class, 'upload']);
Route::post('/train-generator', [TrainingController::class, 'train']);
Route::post('/test-generator', [TestController::class, 'test']);

// ✅ Register
Route::post('/register', function (Request $request) {
    try {
        $messages = [
            'name.required' => 'Please enter your name.',
            'email.required' => 'Please enter your email.',
            'email.email' => 'Please enter a valid email address.',
            'email.regex' => 'Email must contain a valid domain with a proper extension like .com, .net, etc.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Please enter a password.',
            'password.min' => 'Password must be at least 6 characters.',
        ];

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:users,name',
            'email' => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                'unique:users,email',
                'regex:/^[\w\.\-]+@([\w\-]+\.)+[a-zA-Z]{2,}$/'
            ],
            'password' => 'required|string|min:6',
        ], $messages);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
        ]);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'errors' => $e->errors()
        ], 422);
    }
});



// Login & Logout
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// refresh session
Route::get('/me', function () {
    if (auth()->check()) {
        $user = auth()->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);
    }
    return response()->json(null, 401);
});
