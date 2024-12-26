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
        'admission_number',
        'invoice_number'
    ];

    // Relación con la lista de admisiones
    public function admissionsList()
    {
        return $this->hasMany(AdmissionsList::class);
    }
}
