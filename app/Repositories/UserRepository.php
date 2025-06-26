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

        // Por seguridad, siempre desactivamos el usuario en lugar de eliminarlo físicamente
        // Esto preserva la integridad de los datos y permite auditoría
        $user->update(['is_active' => false]);
        return ['status' => 'disabled', 'user' => $user];
    }

    public function restore($id)
    {
        $user = $this->model::findOrFail($id);
        $user->update(['is_active' => true]);
        return $user;
    }
}