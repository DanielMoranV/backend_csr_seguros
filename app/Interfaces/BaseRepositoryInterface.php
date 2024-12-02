<?php

namespace App\Interfaces;

interface BaseRepositoryInterface
{
    public function getAll(array $relations = []);
    public function getById($id, array $relations = []);
    public function store(array $data);
    public function update(array $data, $id);
    public function delete($id);
    public function restore($id);
    public function bulkStore(array $data);
    public function getPaginated($relations = [], $perPage = 10);
}