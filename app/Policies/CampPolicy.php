<?php

namespace App\Policies;

use App\Models\Camp;
use App\Models\CampUser;
use App\Models\User;

class CampPolicy
{
    /**
     * Rolle des Users in diesem Lager laut aktiver Mitgliedschaft (camp_users), sonst null.
     */
    public static function membershipRole(User $user, Camp $camp): ?int
    {
        return CampUser::where('camp_id', $camp->id)
            ->where('user_id', $user->id)
            ->where('active', true)
            ->value('role_id');
    }

    /**
     * Lager bearbeiten oder löschen: nur als Lagerleitung (oder Admin) dieses Lagers.
     */
    public function manage(User $user, Camp $camp): bool
    {
        return in_array(self::membershipRole($user, $camp), [
            config('status.role_Administrator'),
            config('status.role_Lagerleiter'),
        ], true);
    }

    /**
     * Lager wechseln: ins Global-Camp immer, sonst nur mit aktiver Mitgliedschaft.
     */
    public function switchTo(User $user, Camp $camp): bool
    {
        return $camp->global_camp || self::membershipRole($user, $camp) !== null;
    }
}
