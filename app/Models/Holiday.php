<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    // This allows the 'date' and 'name' fields to be mass-assigned
    protected $fillable = [
        'date',
        'name',
    ];
}