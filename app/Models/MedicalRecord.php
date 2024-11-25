<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    // Tabla de historias clínicas
    protected $table = 'medical_records';

    protected $fillable = [
        'number',
        'patient',
        'color',
        'description',
    ];

    // Relación con las admisiones
    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }

    // Relación con las solicitudes de historias clínicas
    public function medicalRecordRequests()
    {
        return $this->hasMany(MedicalRecordRequest::class);
    }
}