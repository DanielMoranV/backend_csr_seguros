<?php

namespace App\Repositories;

use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
    public function findByDni(string $dni)
    {
        return $this->model::where('dni', $dni)->firstOrFail();
    }
    public function deleteUser(string $id)
    {
        $user = $this->model::findOrFail($id);

        // Verifica si el usuario tiene relaciones en otras tablas
        $hasRelations = $user->products()->exists() ||
            $user->stockMovements()->exists() || $user->parameters()->exists();
        if ($hasRelations) {
            // Desactiva al usuario
            $user->update(['is_active' => false]);
            return ['status' => 'disabled', 'user' => $user];
        } else {
            // Si no tiene relaciones, elimínalo físicamente
            $user->forceDelete();
            return ['status' => 'deleted', 'user' => $user];
        }
    }
}