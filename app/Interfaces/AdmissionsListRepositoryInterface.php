<?php

namespace App\Interfaces;

interface AdmissionsListRepositoryInterface extends BaseRepositoryInterface
{
    // funcion para comprobar si existe por admission_number
    public function exists(string $column, string $value): bool;
    // funcion para obtener por periodo
    public function getByPeriod(string $period, array $relations = []);
    // funcion para obtener todos los periodos
    public function getAllPeriods();
    // funcion para actualizar por admission_number
    public function updateByAdmissionNumber(string $admissionNumber, array $data): bool;
}