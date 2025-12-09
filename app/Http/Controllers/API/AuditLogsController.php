<?php

namespace App\Http\Controllers\API;

// Model import must be correct (AuditLogs)
use App\Models\AuditLogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class AuditLogsController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $is_admin = $user && $user->role === 'admin';

        if (!$is_admin) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        // ✅ FIX 1: Eager load the 'user' relationship for the frontend
        $query = AuditLogs::with('user')
            ->select('id', 'action', 'table_name', 'details', 'user_id', 'created_at')
            ->latest();

        $logs = $query->paginate(5);

        // ✅ FIX 2: Return the paginated object directly, not wrapped in another 'data' array.
        // The frontend is configured to unwrap the Laravel paginated structure.
        return response()->json($logs);
    }
    // ...
}
