<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Devolution extends Model
{
    // Tabla de devoluciones
    protected $table = 'devolutions';

    protected $fillable = [
        'date',
        'invoice_id',
        'type',
        'reason',
        'period',
        'biller',
        'status',
        'admission_id',
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
}