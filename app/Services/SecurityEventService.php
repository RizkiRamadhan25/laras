<?php

namespace App\Services;

use App\Enums\SecurityEventType;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Http\Request;

class SecurityEventService
{
    public function record(
        User $user,
        SecurityEventType $type,
        Request $request
    ): SecurityEvent {
        return SecurityEvent::query()
            ->create([
                'user_id' => $user->id,

                'type' => $type,

                'ip_address' =>
                    $request->ip(),

                'user_agent' =>
                    mb_substr(
                        (string) $request
                            ->userAgent(),
                        0,
                        1000
                    ),

                'occurred_at' => now(),
            ]);
    }
}
