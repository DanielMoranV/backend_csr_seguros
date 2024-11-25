<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insurer extends Model
{
    // Tabla de aseguradoras
    protected $table = 'insurers';

    protected $fillable = [
        'name',
        'shipping_period',
        'payment_period',
    ];

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

    // Relación con las solicitudes de historias clínicas
    public function medicalRecordRequests()
    {
        return $this->hasMany(MedicalRecordRequest::class);
    }
}