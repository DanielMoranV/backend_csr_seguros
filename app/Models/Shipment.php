<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    // Tabla de envíos
    protected $table = 'shipments';

    protected $fillable = [
        'verified_shipment', // Verificado por el envío
        'shipment_date', // Fecha de envío
        'reception_date', // Fecha de recepción
        'invoice_id', // ID de la factura
        'remarks', // Observaciones
        'trama_verified', // Verificado por trama
        'trama_date', // Fecha de verificación por trama
        'courier_verified', // Verificado por courier
        'courier_date', // Fecha de verificación por courier
        'email_verified', // Verificado por email
        'email_verified_date', // Fecha de verificación por email
    ];

    // Relación con la factura
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // Relación con la liquidación
    public function settlement()
    {
        return $this->hasOne(Settlement::class);
    }
}