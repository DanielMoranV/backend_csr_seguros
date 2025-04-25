<?php


use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

// routes/channels.php

// Broadcast::channel('medical-record-requests', function ($user) {
//     // Aquí puedes agregar una lógica para permitir que los demás usuarios escuchan el evento
//     // pero no el usuario que lo emite

//     Log::info('Canal medical-record-requests emitido');
//     return $user->id !== $this->medicalRecordRequest->user_id;  // Verifica que no sea el emisor
// });