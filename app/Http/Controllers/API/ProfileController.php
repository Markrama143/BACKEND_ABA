<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class ProfileController extends Controller
{
    // 1. SERVE IMAGE VIA API (Fixes CORS)
    public function getAvatar($filename)
    {
        $path = 'avatars/' . $filename;

        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        $file = Storage::disk('public')->path($path);

        // Fixed: Using multiple approaches to determine MIME type without calling undefined methods
        $type = $this->getMimeType($file);

        // response()->file() automatically adds the correct CORS headers
        // if your Laravel CORS config is set up (which it is by default for API routes)
        return Response::file($file, [
            'Content-Type' => $type
        ]);
    }

    /**
     * Get MIME type using multiple fallback methods
     */
    private function getMimeType($file)
    {
        // Method 1: Use PHP's mime_content_type function (if available)
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($file);
            if ($mimeType !== false) {
                return $mimeType;
            }
        }

        // Method 2: Guess from file extension
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }

    // 2. UPLOAD AVATAR
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            // Delete old image
            if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            // Store new
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->profile_photo_path = $path;
            $user->save();

            // Generate API URL instead of direct storage URL
            $filename = basename($path);
            $url = url("/api/avatars/{$filename}");

            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated',
                'avatar_url' => $url
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
    }

    // 3. GET USER INFO
    public function user(Request $request)
    {
        $user = $request->user();

        // Convert stored path to API URL
        if ($user->profile_photo_path) {
            $filename = basename($user->profile_photo_path);
            $user->avatar_url = url("/api/avatars/{$filename}");
        } else {
            $user->avatar_url = null;
        }

        return response()->json($user);
    }
}
