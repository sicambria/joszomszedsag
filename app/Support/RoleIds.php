<?php

namespace App\Support;

/**
 * Legacy Voyager role ids (seeded, not migrated).
 * Use these constants instead of magic numbers.
 */
final class RoleIds
{
    const ADMIN = 1;
    const VOLUNTEER = 6;
    const REQUESTER = 7;

    /**
     * @return int[]
     */
    public static function registrationRoles()
    {
        return [self::VOLUNTEER, self::REQUESTER];
    }
}
