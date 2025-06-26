<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dni' => $this->dni,
            'nick' => $this->nick,
            'is_active' => $this->is_active,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'url_photo_profile' => $this->url_photo_profile,
            'position' => $this->position,
            'role' => $this->whenLoaded('roles') && $this->roles->isNotEmpty()
                ? new RoleResource($this->roles->first())
                : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
