<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdmissionsList extends Model
{
    protected $table = 'admissions_lists';
    protected $fillable = [
        'admission_number',
        'period',
        'start_date',
        'end_date',
        'biller',
        'shipment_id',
        'audit_id',
        'medical_record_request_id',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }

    public function medicalRecordRequest()
    {
        return $this->belongsTo(MedicalRecordRequest::class);
    }
}
