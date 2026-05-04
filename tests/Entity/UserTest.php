<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetRolesIncludesRoleUser(): void
    {
        $user = new User();
        
        $roles = $user->getRoles();
        
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testGetRolesForAdminIncludesRoleAdmin(): void
    {
        $user = new User();
        $user->setRole('admin');
        
        $roles = $user->getRoles();
        
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
        $this->assertTrue($user->isAdmin());
    }

    public function testGetRolesForCoachIncludesRoleAdmin(): void
    {
        $user = new User();
        $user->setRole('coach');
        
        $roles = $user->getRoles();
        
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
        $this->assertTrue($user->isAdmin());
    }

    public function testSettersAndGetters(): void
    {
        $user = new User();
        $user->setNom('Doe')
             ->setPrenom('John')
             ->setEmail('john.doe@example.com')
             ->setAge(30)
             ->setTelephone('12345678');

        $this->assertEquals('Doe', $user->getNom());
        $this->assertEquals('John', $user->getPrenom());
        $this->assertEquals('john.doe@example.com', $user->getEmail());
        $this->assertEquals('john.doe@example.com', $user->getUserIdentifier());
        $this->assertEquals(30, $user->getAge());
        $this->assertEquals('12345678', $user->getTelephone());
        $this->assertTrue($user->isActive());
    }

    public function testEraseCredentialsDoesNotThrowError(): void
    {
        $user = new User();
        $user->eraseCredentials();
        $this->assertTrue(true); // Simply asserts that no error was thrown
    }
}
