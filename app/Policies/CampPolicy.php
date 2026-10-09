<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Camp;
use App\Models\CampUser;
use App\Models\User;

class CampPolicy
{
    /**
     * Rolle des Users in diesem Lager laut aktiver Mitgliedschaft (camp_users), sonst null.
     */
    public static function membershipRole(User $user, Camp $camp): ?UserRole
    {
        return CampUser::where('camp_id', $camp->id)
            ->where('user_id', $user->id)
            ->where('active', true)
            ->first()?->role_id;
    }

    /**
     * Lager bearbeiten oder löschen: nur als Lagerleitung (oder Admin) dieses Lagers.
     */
    public function manage(User $user, Camp $camp): bool
    {
        return self::membershipRole($user, $camp)?->canManage() ?? false;
    }

    /**
     * Lager wechseln: ins Global-Camp immer, sonst nur mit aktiver Mitgliedschaft.
     */
    public function switchTo(User $user, Camp $camp): bool
    {
        return $camp->global_camp || self::membershipRole($user, $camp) !== null;
    }
}
