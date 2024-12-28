<?php

namespace App\Interfaces;

interface AdmissionsListRepositoryInterface extends BaseRepositoryInterface
{
    // funcion para comprobar si existe por admission_number
    public function exists(string $column, string $value): bool;
}
