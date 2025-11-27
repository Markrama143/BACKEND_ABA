<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VaccineStock extends Model
{
    use HasFactory;

    // ERROR FIX: You MUST have this line to save data
    protected $fillable = ['date', 'quantity'];
}