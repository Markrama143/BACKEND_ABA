<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // Register user
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'contact_number' => 'nullable|string|max:20',
        ]);

        $generatedName = explode('@', $request->email)[0];

        $user = User::create([
            'name' => $generatedName,
            'email' => $request->email,
            // SECURITY FIX: Hash the password before storage
            'password' => Hash::make($request->password),
            'contact_number' => $request->contact_number,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // 1. Create Token
        $token = $user->createToken('auth_token')->plainTextToken;

        // 2. SAVE LOGIN HISTORY (Requirement)
        // We use try-catch so if this fails, the user can still log in
        try {
            DB::table('login_histories')->insert([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'login_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log error but don't crash the app
            Log::error("Login History Error: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'User logged in successfully',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    // Logout user
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'User logged out successfully',
        ]);
    }
}
