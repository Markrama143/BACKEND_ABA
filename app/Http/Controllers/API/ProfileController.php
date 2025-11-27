<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class ProfileController extends Controller
{
    // Serve image via API (avoid CORS)
    public function getAvatar($filename)
    {
        $path = 'avatars/' . $filename;

        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        $file = Storage::disk('public')->path($path);

        // Determine MIME type with fallbacks
        $type = $this->getMimeType($file);

        // response()->file() adds appropriate headers when CORS is configured
        return Response::file($file, [
            'Content-Type' => $type
        ]);
    }

    /**
     * Get MIME type using multiple fallback methods
     */
    private function getMimeType($file)
    {
        // Method 1: PHP mime_content_type (if available)
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($file);
            if ($mimeType !== false) {
                return $mimeType;
            }
        }

        // Method 2: Fallback to extension map
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

    // Upload avatar
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $user = $request->user();

        if ($request->hasFile('avatar')) {
            // Delete old avatar if present
            if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->profile_photo_path = $path;
            $user->save();

            // Return API URL for avatar
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

    // Get user info
    public function user(Request $request)
    {
        $user = $request->user();

        // Convert stored avatar path to API URL
        if ($user->profile_photo_path) {
            $filename = basename($user->profile_photo_path);
            $user->avatar_url = url("/api/avatars/{$filename}");
        } else {
            $user->avatar_url = null;
        }

        return response()->json($user);
    }
}
