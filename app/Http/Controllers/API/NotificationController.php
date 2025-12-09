<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // Get all notifications for the logged-in user
        $notifications = $request->user()->notifications;
        
        return response()->json([
            'success' => true,
            'notifications' => $notifications
        ]);
    }

    public function markAsRead($id, Request $request)
    {
        $notification = $request->user()->notifications()->find($id);
        
        if($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }
}