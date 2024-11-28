<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\AuthUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Interfaces\UserRepositoryInterface;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    protected $userRepository;
    protected $relations = ['roles'];

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(StoreUserRequest $request)
    {
        try {
            $data = $request->validated();
            $data['password'] = Hash::make($data['password']);
            $user = $this->userRepository->store($data);

            if (!$user) {
                return ApiResponseClass::errorResponse('Error al crear el usuario');
            }
            $token = JWTAuth::fromUser($user);
            return ApiResponseClass::sendResponse([
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60
            ], 'Usuario creado exitosamente', 201);
        } catch (\Exception $e) {
            return ApiResponseClass::rollback($e, 'Error en el proceso de creación del usuario');
        }
    }

    public function login(AuthUserRequest $request)
    {
        $credentials = $request->only(['dni', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return ApiResponseClass::sendResponse(null, 'Unauthorized', 401);
        }

        $user = auth()->user();
        $user = $this->userRepository->getById($user->id, $this->relations);

        return ApiResponseClass::sendResponse([
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ], 'Datos del usuario autenticado');
    }

    public function me()
    {
        $user = auth()->user();
        $token = JWTAuth::fromUser($user);

        $user = $this->userRepository->getById($user->id, $this->relations);

        return ApiResponseClass::sendResponse([
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ], 'Datos del usuario autenticado');
    }

    public function logout()
    {
        auth()->logout(true);
        return ApiResponseClass::sendResponse(null, 'Successfully logged out', 200);
    }

    public function refresh()
    {
        $token = JWTAuth::refresh();
        return ApiResponseClass::sendResponse([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ], 'Token actualizado');
    }
}
