<?php

namespace App\Interfaces;

interface InsurerRepositoryInterface extends BaseRepositoryInterface
{
    public function updateByName($name, $data);
}