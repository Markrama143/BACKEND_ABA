<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VaccineStock extends Model
{
    use HasFactory;

    // Allow mass assignment for date and quantity
    protected $fillable = ['date', 'quantity'];
}