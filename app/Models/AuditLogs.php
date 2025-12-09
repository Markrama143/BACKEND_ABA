<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLogs extends Model
{
    use HasFactory;

    // Explicitly set table name for non-standard plural model name
    protected $table = 'audit_logs'; 
    
    
    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'details',
    ];

    public function user(): BelongsTo
    {
        // Assuming your User model is App\Models\User
        return $this->belongsTo(\App\Models\User::class, 'user_id'); 
    }
}