<?php

namespace Tests\Unit;

use App\Support\RoleIds;
use PHPUnit\Framework\TestCase;

class RoleIdsTest extends TestCase
{
    public function test_registration_roles_are_volunteer_and_requester()
    {
        $this->assertSame([6, 7], RoleIds::registrationRoles());
        $this->assertSame(1, RoleIds::ADMIN);
        $this->assertSame(6, RoleIds::VOLUNTEER);
        $this->assertSame(7, RoleIds::REQUESTER);
    }
}
