<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    // Tabla de envíos
    protected $table = 'shipments';

    protected $fillable = [
        'shipment_date',
        'reception_date',
        'invoice_id',
        'remarks',
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