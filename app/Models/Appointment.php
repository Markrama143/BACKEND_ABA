<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    // Primary key is 'id'
    protected $fillable = [
        'patient_id',
        'date',
        'time',
        'name',
        'sex',
        'age',
        'email',
        'phone_number',
        'animal_type'
    ];

    // Appointment belongs to a patient
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }
}
