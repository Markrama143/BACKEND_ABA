<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    protected $primaryKey = 'doctors_id';
    protected $fillable = ['name', 'contact_number'];

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'doctors_id');
    }
}
