<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $table = 'audits';

    protected $fillable = ['name', 'description', 'status'];

    // Relación con la liquidación
    public function settlement()
    {
        return $this->hasMany(Settlement::class);
    }

    // Relación con la devolución
    public function devolution()
    {
        return $this->hasMany(Devolution::class);
    }
}
