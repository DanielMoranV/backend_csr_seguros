<?php

namespace App\Repositories;

use App\Interfaces\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class BaseRepository implements BaseRepositoryInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getAll(array $relations = [])
    {
        if (!empty($relations)) {
            return $this->model::with($relations)->get();
        }
        return $this->model::all();
    }

    public function getById($id, array $relations = [])
    {
        if (!empty($relations)) {
            return $this->model::with($relations)->findOrFail($id);
        }
        return $this->model::findOrFail($id);
    }
    public function store(array $data)
    {
        return $this->model::create($data);
    }
    public function update(array $data, $id)
    {
        $record = $this->model::findOrFail($id);
        $record->update($data);
        return $record;
    }
    public function delete($id)
    {
        $response = $this->model::find($id);
        return $response->delete(); // Esto ejecutará la eliminación lógica

    }
    public function restore($id)
    {
        $response = $this->model::onlyTrashed()->find($id);
        return $response->restore(); // Restaura registro eliminado
    }
}