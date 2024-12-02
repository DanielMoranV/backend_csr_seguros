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

    // Relación con las admisiones
    public function admissions()
    {
        return $this->hasMany(Admission::class);
    }
}