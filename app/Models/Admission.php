<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    // Tabla de admisiones
    protected $table = 'admissions';

    protected $fillable = [
        'number',
        'attendance_date',
        'attendance_hour',
        'type',
        'doctor',
        'insurer_id',
        'status',
        'company',
        'amount',
        'patient',
        'medical_record_id',
    ];

    // Relación con la historia clínica
    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    // Relación con la aseguradora
    public function insurer()
    {
        return $this->belongsTo(Insurer::class);
    }

    // Relación con las facturas
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    // Relación con las liquidaciones
    public function settlements()
    {
        return $this->hasMany(Settlement::class);
    }
}
