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

            // selec * from users inner
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
    public function bulkStore(array $data)
    {
        return $this->model::insert($data); // Retorna true/false
    }

    public function getPaginated($relations = [], $perPage = 10)
    {
        if (!empty($relations)) {
            return $this->model::with($relations)->paginate($perPage);
        }
        return $this->model::paginate($perPage);
    }

    public function getDateRange($from = null, $to = null, $relations = [])
    {
        if (!empty($relations)) {
            return $this->model::with($relations)
                ->whereBetween('created_at', [$from, $to])
                ->orderBy('created_at', 'desc')
                ->get();
        }
        return $this->model->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
