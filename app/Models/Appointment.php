<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guardian',
        'guardian_relationship', // <--- ADD THIS LINE
        'name',
        'age',
        'sex',
        'animal_type',
        'phone_number',
        'email',
        'date',
        'time',
        'purpose',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}