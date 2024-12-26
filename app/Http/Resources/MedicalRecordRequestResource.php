<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalRecordRequestResource extends JsonResource
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
            'requester_nick' => $this->requester_nick,
            'requested_nick' => $this->requested_nick,
            'admision_number' => $this->admision_number,
            'request_date' => $this->request_date,
            'response_date' => $this->response_date,
            'remarks' => $this->remarks,
            'status' => $this->status,
        ];
    }
}
