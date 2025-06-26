<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponseClass;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    protected $userRepositoryInterface;
    protected $relations = ['roles'];

    public function __construct(UserRepositoryInterface $userRepositoryInterface)
    {
        $this->userRepositoryInterface = $userRepositoryInterface;
        $this->middleware('compress')->only('index');
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
        $data = $request->validated();
        
        // Set DNI as default password
        $data['password'] = Hash::make($data['dni']);

        DB::beginTransaction();
        try {
            // Store user data
            $user = $this->userRepositoryInterface->store($data);

            // Assign admin role by default
            $adminRole = Role::where('name', 'admin')->first();
            if (!$adminRole) {
                throw new \Exception("Admin role not found in system");
            }
            $user->assignRole($adminRole);

            // Reload user with relationships
            $user->load($this->relations);

            DB::commit();
            return ApiResponseClass::sendResponse(new UserResource($user), 'Record created successfully', 201);
        } catch (\Exception $ex) {
            Log::error('Error creating user: ' . $ex->getMessage(), [
                'user_data' => $request->except('password'),
                'trace' => $ex->getTraceAsString()
            ]);
            DB::rollBack();
            return ApiResponseClass::rollback($ex);
        }
    }
    public function update(UpdateUserRequest $request, string $id)
    {
        $data = $request->validated();
        
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        DB::beginTransaction();
        try {
            $user = $this->userRepositoryInterface->update($data, $id);
            $user->load($this->relations);
            
            DB::commit();
            return ApiResponseClass::sendResponse(new UserResource($user), 'Record updated successfully', 200);
        } catch (\Exception $ex) {
            Log::error('Error updating user: ' . $ex->getMessage(), [
                'user_id' => $id,
                'user_data' => $request->except('password'),
                'trace' => $ex->getTraceAsString()
            ]);
            DB::rollBack();
            return ApiResponseClass::rollback($ex);
        }
    }

    public function photoProfile(Request $request, string $id)
    {
        $request->validate([
            'photo_profile' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        DB::beginTransaction();
        try {
            // Get current user to access previous photo
            $currentUser = $this->userRepositoryInterface->getById($id);
            $data = [];

            if ($request->hasFile('photo_profile')) {
                // Delete previous photo if exists
                if ($currentUser->url_photo_profile && Storage::disk('public')->exists($currentUser->url_photo_profile)) {
                    Storage::disk('public')->delete($currentUser->url_photo_profile);
                }

                // Store new photo
                $file = $request->file('photo_profile');
                $path = $file->store('profile_photos', 'public');
                $data['url_photo_profile'] = $path;
            }

            $user = $this->userRepositoryInterface->update($data, $id);
            $user->load($this->relations);
            
            DB::commit();
            return ApiResponseClass::sendResponse(new UserResource($user), 'Profile photo updated successfully', 200);
        } catch (\Exception $ex) {
            Log::error('Error updating profile photo: ' . $ex->getMessage(), [
                'user_id' => $id,
                'trace' => $ex->getTraceAsString()
            ]);
            DB::rollBack();
            return ApiResponseClass::rollback($ex);
        }
    }




    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $result = $this->userRepositoryInterface->deleteUser($id);

            DB::commit();
            
            if ($result['status'] === 'disabled') {
                return ApiResponseClass::sendResponse(null, 'Usuario deshabilitado exitosamente', 200);
            } elseif ($result['status'] === 'deleted') {
                return ApiResponseClass::sendResponse(null, 'Usuario eliminado exitosamente', 200);
            }
        } catch (\Exception $ex) {
            Log::error('Error deleting user: ' . $ex->getMessage(), [
                'user_id' => $id,
                'trace' => $ex->getTraceAsString()
            ]);
            DB::rollBack();
            return ApiResponseClass::rollback($ex, 'Error al eliminar el usuario');
        }
    }

    public function restore(string $id)
    {
        DB::beginTransaction();
        try {
            $user = $this->userRepositoryInterface->restore($id);
            $user->load($this->relations);
            
            DB::commit();
            return ApiResponseClass::sendResponse(new UserResource($user), 'Record restored successfully', 200);
        } catch (\Exception $ex) {
            Log::error('Error restoring user: ' . $ex->getMessage(), [
                'user_id' => $id,
                'trace' => $ex->getTraceAsString()
            ]);
            DB::rollBack();
            return ApiResponseClass::rollback($ex);
        }
    }
}