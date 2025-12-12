<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Authenticated;

class LogAuthenticated
{
    /**
     * Handle the event.
     */
    public function handle(Authenticated $event): void
    {
        if ($event->guard === 'filament' && $event->user) {
            ActivityLog::log([
                'action' => 'login',
                'model_type' => get_class($event->user),
                'model_id' => $event->user->id,
                'description' => "Login: {$event->user->username}",
                'properties' => [
                    'username' => $event->user->username,
                    'telegram_user_id' => $event->user->telegram_user_id,
                ],
            ]);
        }
    }
}