<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    // Tabla de facturas
    protected $table = 'invoices';

    protected $fillable = [
        'number',
        'issue_date',
        'status',
        'biller',
        'payment_date',
        'amount',
        'admission_id',
    ];

    // Relación con la admisión
    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    // Relación con la aseguradora
    public function insurer()
    {
        return $this->belongsTo(Insurer::class);
    }

    // Relación con las devoluciones
    public function devolutions()
    {
        return $this->hasMany(Devolution::class);
    }

    // Relación con las notas de crédito
    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }

    // Relación con las liquidaciones
    public function settlements()
    {
        return $this->hasMany(Settlement::class);
    }

    // Relación con las solicitudes de historias clínicas
    public function medicalRecordRequests()
    {
        return $this->hasMany(MedicalRecordRequest::class);
    }
}
