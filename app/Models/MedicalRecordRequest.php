<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalRecordRequest extends Model
{
    // Tabla de solicitudes de historias clínicas
    protected $table = 'medical_record_requests';

    protected $fillable = [
        'requester_id',
        'requested_id',
        'request_date',
        'response_date',
        'medical_record_id',
        'remarks',
        'status',
    ];

    // Relación con la historia clínica
    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    // Relación con el usuario que solicita
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    // Relación con el usuario que solicita
    public function requested()
    {
        return $this->belongsTo(User::class, 'requested_id');
    }

    // Relación con la factura
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}