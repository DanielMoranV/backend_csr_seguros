<?php

namespace App\Interfaces;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByDni(string $dni);
    public function deleteUser(string $id);
}