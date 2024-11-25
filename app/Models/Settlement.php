<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    // Tabla de liquidaciones
    protected $table = 'settlements';

    protected $fillable = [
        'admission_id',
        'biller',
        'received_file',
        'reception_date',
        'settled',
        'settled_date',
        'audited',
        'audited_date',
        'billed',
        'invoice_id',
        'shipped',
        'shipment_date',
        'paid',
        'payment_date',
        'status',
        'period',
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

    // Relación con el envío
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}