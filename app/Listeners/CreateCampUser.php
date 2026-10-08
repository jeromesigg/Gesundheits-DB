<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\UserCreated;
use App\Models\Camp;
use App\Models\CampUser;

class CreateCampUser
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(UserCreated $event)
    {
        //
        $camp_global = Camp::where('global_camp', true)->first();
        $camp_user = CampUser::firstOrCreate(['camp_id' => $camp_global['id'], 'user_id' => $event->user['id']]);
        $camp_user->update([
            'role_id' => UserRole::Participant,
        ]);
        if (! $event->user->camp) {
            $event->user->update([
                'camp_id' => $camp_global['id'],
            ]);
        }
        if (! $event->user->role) {
            $event->user->update([
                'role_id' => UserRole::Participant,
            ]);
        }
    }
}
