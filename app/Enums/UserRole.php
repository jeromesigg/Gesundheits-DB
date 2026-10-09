<?php

namespace App\Enums;

/**
 * Rolle einer Person im aktuellen Lager; Werte = IDs der Tabelle roles.
 */
enum UserRole: int
{
    case Administrator = 1;
    case CampLeader = 2;
    case Helper = 3;
    case Participant = 4;

    /**
     * Lagerleitung oder Admin: Leitende, Fragen, Lager, Gesundheitsblätter verwalten.
     */
    public function canManage(): bool
    {
        return in_array($this, [self::Administrator, self::CampLeader], true);
    }

    /**
     * Helfende und höher: Gesundheitsdaten des Lagers sehen, Interventionen erfassen.
     */
    public function canHelp(): bool
    {
        return $this->canManage() || $this === self::Helper;
    }

    /**
     * Rollen, die eine Lagerleitung vergeben darf (nie Administrator).
     *
     * @return array<int, self>
     */
    public static function assignable(): array
    {
        return [self::CampLeader, self::Helper, self::Participant];
    }

    /**
     * @return array<int, int>
     */
    public static function assignableValues(): array
    {
        return array_map(fn (self $role) => $role->value, self::assignable());
    }
}
