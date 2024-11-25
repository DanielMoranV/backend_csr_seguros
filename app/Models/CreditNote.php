<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    // Tabla de notas de crédito
    protected $table = 'credit_notes';

    protected $fillable = [
        'date',
        'number',
        'invoice_id',
        'reason',
    ];

    // Relación con la factura
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}