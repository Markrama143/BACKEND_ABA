<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\password;

class AuthController extends Controller
{
    /**
     * Handle user registration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    // Register user
    public function register(Request $request)
    {
        // 1. Validate input
        // Name is generated if missing; contact_number is accepted
        $request->validate([
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'contact_number' => 'nullable|string|max:20',
        ]);

        // 2. Generate name from email local-part if missing
        $generatedName = explode('@', $request->email)[0];

        $user = User::create([
            'name' => $generatedName,
            'email' => $request->email,
            'password' => $request->password, // Laravel automatically hashes this if using modern versions
            'contact_number' => $request->contact_number, // Saving the contact number
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            // Use 'token' field to match client
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 200);
    } // Returns 200 for client convenience

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

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User logged in successfully',
            // Use 'token' field
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    // Logout user
    public function logout(Request $request)
    {
        // Only delete token if user is logged in
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'User logged out successfully',
        ]);
    }
}
