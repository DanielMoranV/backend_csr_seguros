<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    // Tabla de envíos
    protected $table = 'shipments';

    protected $fillable = [
        'verified_shipment_date', // Fecha de envío
        'reception_date', // Fecha de recepción
        'invoice_number', // Número de la factura
        'remarks', // Observaciones
        'trama_date', // Fecha de verificación por trama
        'courier_date', // Fecha de verificación por courier
        'email_verified_date', // Fecha de verificación por email
        'url_sustenance' // URL de la sustentación
    ];

    // Relación con la lista de admisiones
    public function admissionsList()
    {
        return $this->hasMany(AdmissionsList::class);
    }
}
