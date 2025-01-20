<?php

namespace App\Interfaces;

interface MedicalRecordRequestRepositoryInterface extends BaseRepositoryInterface
{
    public function searchByMedicalRecordNumber(string $medicalRecordNumber);
}
