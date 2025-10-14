<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // User 1 - ROLE_AUTHOR
        $user1 = new User();
        $user1->setEmail('author1@example.com');
        $user1->setRoles(['ROLE_AUTHOR']);
        $user1->setUsername('author1');
        $user1->setPassword(
            $this->passwordHasher->hashPassword($user1, 'password123')
        );
        $manager->persist($user1);

        // User 2 - ROLE_AUTHOR
        $user2 = new User();
        $user2->setEmail('author2@example.com');
        $user2->setRoles(['ROLE_AUTHOR']);
        $user2->setUsername('author2');
        $user2->setPassword(
            $this->passwordHasher->hashPassword($user2, 'password123')
        );
        $manager->persist($user2);

        // User 3 - ROLE_ADMIN
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setUsername('admin');
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'adminpass')
        );
        $manager->persist($admin);

        $manager->flush();
    }
}
