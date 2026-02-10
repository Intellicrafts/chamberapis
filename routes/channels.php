<?php

use App\Models\ConsultationSession;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Authorization for consultation chat channel
 */
Broadcast::channel('consultation.{token}', function ($user, $token) {
    $session = ConsultationSession::where('session_token', $token)->first();
    
    if (!$session) {
        return false;
    }

    // Only participants can join the channel
    return $session->isParticipant($user->id);
});
