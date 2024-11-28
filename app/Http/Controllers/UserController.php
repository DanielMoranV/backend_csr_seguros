<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private $relations = ['roles'];
    private UserRepositoryInterface $userRepositoryInterface;

    public function __construct(UserRepositoryInterface $userRepositoryInterface)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
    }

    public function index()
    {
        $data = $this->userRepositoryInterface->getAll($this->relations);
        return ApiResponseClass::sendResponse(UserResource::collection($data), 'Usuarios obtenidos correctamente', 200);
    }

    public function show($id)
    {
        $data = $this->userRepositoryInterface->getById($id, $this->relations);
        return ApiResponseClass::sendResponse(new UserResource($data), 'Usuario obtenido correctamente', 200);
    }

    public function store(StoreUserRequest $request)
    {
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profile_photo_path' => $request->profile_photo_path,
            'role_id' => $request->role_id ?? 1,
            'phone' => $request->phone,
        ];
        $user = $this->userRepositoryInterface->store($data);
        return ApiResponseClass::sendResponse(new UserResource($user), 'Usuario creado correctamente', 201);
    }
}
