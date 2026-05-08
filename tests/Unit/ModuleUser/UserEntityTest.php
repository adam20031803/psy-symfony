<?php

declare(strict_types=1);

namespace App\Tests\Unit\ModuleUser;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserEntityTest extends TestCase
{
    public function testGetRolesForStandardUserContainsRoleUser(): void
    {
        $user = new User();
        $user->setEmail('etudiant@example.com');
        $user->setRole('user');

        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
        $this->assertNotContains('ROLE_ADMIN', $roles);
    }

    public function testIsAdminIsTrueForCoachAndAdminRoles(): void
    {
        $coach = new User();
        $coach->setRole('coach');
        $this->assertTrue($coach->isAdmin());

        $admin = new User();
        $admin->setRole('admin');
        $this->assertTrue($admin->isAdmin());

        $member = new User();
        $member->setRole('user');
        $this->assertFalse($member->isAdmin());
    }
}
