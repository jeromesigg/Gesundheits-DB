<?php

namespace App\Policies;

use App\Models\CampUser;
use App\Models\User;

class UserPolicy
{
    /**
     * Profil ändern: nur das eigene Konto.
     */
    public function updateProfile(User $user, User $target): bool
    {
        return $user->is($target);
    }

    /**
     * Leitende verwalten: als Lagerleitung nur Mitglieder des aktuellen Lagers (auch inaktive), nie Admins.
     */
    public function manage(User $user, User $target): bool
    {
        if (! $user->isManager()) {
            return false;
        }
        $targetRole = CampUser::where('camp_id', $user->camp_id)
            ->where('user_id', $target->id)
            ->value('role_id');

        return $targetRole !== null && $targetRole !== config('status.role_Administrator');
    }
}
