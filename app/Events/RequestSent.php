<?php

namespace App\Events;

use App\Models\MedicalRecordRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $requestData;
    public $userId;

    public function __construct(MedicalRecordRequest $medicalRecordRequest)
    {
        // Solo los datos necesarios, evita pasar el modelo entero
        $this->requestData = $medicalRecordRequest->only(['id']);
        $this->userId = auth()->id(); // mejor usar request()->user()->id si estás en contexto de request
    }

    public function broadcastOn(): Channel
    {
        return new Channel('medical-record-requests');
    }

    public function broadcastAs(): string
    {
        return 'request.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'medical_record_request' => $this->requestData,
            'user_id' => $this->userId,
        ];
    }
}
