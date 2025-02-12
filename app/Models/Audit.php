<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $table = 'audits';

    protected $fillable = [
        'auditor',
        'description',
        'status',
        'url',
        'admission_number',
        'invoice_number',
        'type',
    ];

    // Relación con la lista de admisiones
    public function admissionsList()
    {
        return $this->hasMany(AdmissionsList::class);
    }
}
