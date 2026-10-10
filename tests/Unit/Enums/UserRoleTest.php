<?php

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Rechte pro Rolle, ohne Datenbank. Abgleich mit der Tabelle roles: Tests\Feature\Enums\UserRoleTest.
 */
class UserRoleTest extends TestCase
{
    /**
     * @return array<string, array{UserRole, bool, bool}>
     */
    public static function permissions(): array
    {
        return [
            'Administrator' => [UserRole::Administrator, true, true],
            'Lagerleitung' => [UserRole::CampLeader, true, true],
            'Helfende' => [UserRole::Helper, false, true],
            'Teilnehmende' => [UserRole::Participant, false, false],
        ];
    }

    #[DataProvider('permissions')]
    public function test_permissions_per_role(UserRole $role, bool $canManage, bool $canHelp): void
    {
        $this->assertSame($canManage, $role->canManage());
        $this->assertSame($canHelp, $role->canHelp());
    }

    public function test_camp_leader_can_assign_all_roles_except_administrator(): void
    {
        $this->assertSame([UserRole::CampLeader, UserRole::Helper, UserRole::Participant], UserRole::assignable());
        $this->assertSame([2, 3, 4], UserRole::assignableValues());
    }
}
