<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    // The primary key is 'id' as per the migration ($table->id())
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

    // Relationship with Patient model
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }
}
