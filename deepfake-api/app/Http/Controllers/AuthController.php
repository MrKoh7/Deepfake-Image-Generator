<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),   // ✅ gives detailed messages
            ], 422);
        }

        // 🔹 Check if user exists
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => '❌ Email not found!'], 401);
        }

        // 🔹 Check password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => '❌ Wrong password!'], 401);
        }

        // 🔹 Login success
        Auth::login($user);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]);
    }


    public function logout(Request $request)
    {
        Auth::logout();
        return response()->json(['message' => '✅ Logged out successfully']);
    }
}
