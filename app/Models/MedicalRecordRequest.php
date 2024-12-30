<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalRecordRequest extends Model
{
    // Tabla de solicitudes de historias clínicas
    protected $table = 'medical_record_requests';

    protected $fillable = [
        'requester_nick',
        'requested_nick',
        'admission_number',
        'medical_record_number',
        'request_date',
        'response_date',
        'remarks',
        'status',
    ];

    // Relación con la lista de admisiones
    public function admissionsList()
    {
        return $this->hasMany(AdmissionsList::class);
    }
}