<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;

class AuthController extends Controller
{
    /**
     * Handle user registration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // 1. Validate the incoming request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'sex' => 'required|string|max:10',
            'age' => 'required|integer',
            'address' => 'required|string|max:255',
            'contact_number' => 'required|string|max:15',
            'password' => 'required|string|min:8|confirmed', // 'confirmed' checks for 'password_confirmation'

        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); // 422 Unprocessable Entity
        }

        // 2. Create and save the new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'sex' => $request->sex,
            'age' => $request->age,
            'address' => $request->address,
            'contact_number' => $request->contact_number,
            'password' => Hash::make($request->password),
            'password_confirmation' => Hash::make($request->password_confirmation)
        ]);

        // 3. Create a token for the new user
        $token = $user->createToken('authToken')->plainTextToken;

        // 4. Return the user and token
        // This response matches what your Flutter AuthService expects
        return response()->json([
            'user' => $user,
            'token' => $token
        ], 201); // 201 Created
    }

    /**
     * Handle user login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // 1. Validate the incoming request
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 2. Attempt to authenticate the user
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            // 401 Unauthorized
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // 3. Get the authenticated user
        $user = User::where('email', $request->email)->first();

        // 4. Create a token for the user
        $token = $user->createToken('authToken')->plainTextToken;

        // 5. Return the user and token
        // This response matches what your Flutter AuthService expects
        return response()->json([
            'user' => $user,
            'token' => $token
        ], 200); // 200 OK
    }
}
