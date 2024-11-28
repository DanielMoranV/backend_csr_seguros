<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    protected $userRepositoryInterface;
    protected $relations = ['roles'];

    public function __construct(UserRepositoryInterface $userRepositoryInterface)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
    }

    public function index()
    {
        $data = $this->userRepositoryInterface->getAll($this->relations);
        return ApiResponseClass::sendResponse(UserResource::collection($data), '', 200);
    }

    public function show(string $id)
    {
        $user = $this->userRepositoryInterface->getById($id, $this->relations);
        return ApiResponseClass::sendResponse(new UserResource($user), '', 200);
    }

    public function store(StoreUserRequest $request)
    {
        $data = [
            'name' => $request->name,
            'dni' => $request->dni,
            'phone' => $request->phone,
            'photo' => $request->photo,
            'position' => $request->position,
            'email' => $request->email,
            'password' => $request->password,
        ];

        DB::beginTransaction();
        try {
            // Store user data
            $user = $this->userRepositoryInterface->store($data);

            // Assign role if provided
            if (!empty($request->role)) {
                $role = Role::where('name', $request->role)->firstOrFail();
                $user->assignRole($role);
            }

            // Reload user with relationships to include company and role details
            $user->load('roles');

            DB::commit();
            return ApiResponseClass::sendResponse($user, 'Record created successfully', 201);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ApiResponseClass::rollback($ex);
        }
    }
    public function update(UpdateUserRequest $request, string $id)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {
            $user =  $this->userRepositoryInterface->update($data, $id);
            DB::commit();
            return ApiResponseClass::sendResponse($user, 'Record updated succesful', 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            return ApiResponseClass::rollback($ex);
        }
    }

    public function photoProfile(Request $request, string $id)
    {
        $data = $request->all();

        if ($request->hasFile('photo_profile')) {
            $file = $request->file('photo_profile');
            $path = $file->store('profile_photos', 'public');
            $data['url_photo_profile']  = $path;
        }

        DB::beginTransaction();
        try {
            Log::info($data);
            $this->userRepositoryInterface->update($data, $id);
            DB::commit();
            return ApiResponseClass::sendResponse($data, 'Record updated succesful', 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            DB::rollBack();
            return ApiResponseClass::rollback($ex);
        }
    }




    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $result = $this->userRepositoryInterface->deleteUser($id);

            if ($result['status'] === 'disabled') {
                return ApiResponseClass::sendResponse(null, 'Usuario deshabilitado exitosamente', 200);
            } elseif ($result['status'] === 'deleted') {
                return ApiResponseClass::sendResponse(null, 'Usuario eliminado exitosamente', 200);
            }
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Error al eliminar el usuario');
        }
    }

    public function restore(string $id)
    {
        $this->userRepositoryInterface->restore($id);
        return ApiResponseClass::sendResponse(null, 'Record restore succesful', 200);
    }
}
