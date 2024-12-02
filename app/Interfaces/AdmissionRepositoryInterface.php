<?php

namespace App\Interfaces;

interface AdmissionRepositoryInterface extends BaseRepositoryInterface
{
    public function updateByNumber($number, $data);
    public function getExistingNumbers(array $numbers);
}