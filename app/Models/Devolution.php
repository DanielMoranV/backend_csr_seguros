<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Devolution extends Model
{
    // Tabla de devoluciones
    protected $table = 'devolutions';

    protected $fillable = [
        'sisclin_id',
        'date',
        'invoice_id',
        'type',
        'reason',
        'period',
        'biller',
        'status',
        'is_paid',
        'is_uncollectible',
        'admission_id',
        'admission_number',
        'medical_record_number',
        'patient_name',
        'insurer_name',
        'attendance_date',
        'doctor',
        'invoice_date',
        'invoice_amount',
        'audit_id',
    ];

    protected $casts = [
        'is_paid'           => 'boolean',
        'is_uncollectible'  => 'boolean',
        'attendance_date'   => 'datetime',
        'invoice_date'      => 'datetime',
        'invoice_amount'    => 'decimal:2',
    ];

    // Relación con la admisión
    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    // Relación con la factura
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Relación con el auditor
    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }
}
