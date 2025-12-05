<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    // FIX: Updated $fillable to match the new database column names
    protected $fillable = [
        'user_id',
        'name',
        'age',
        'sex',
        'animal_type',
        'phone_number',
        'email',
        'date',     // Renamed from appointment_date
        'time',     // Renamed from appointment_time
        'purpose',
        'status',
    ];

    /**
     * Relationship: An appointment belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
