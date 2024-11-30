<?php

namespace App\Interfaces;

interface MedicalRecordRepositoryInterface extends BaseRepositoryInterface
{
    public function updateByNumber($number, $data);
}