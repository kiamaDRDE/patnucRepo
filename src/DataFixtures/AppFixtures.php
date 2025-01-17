<?php

namespace App\DataFixtures;

use App\Entity\Roles\Roles;
use App\Entity\Users\Users;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création des rôles
        $userRole = new Roles();
        $userRole->setRoleName('ROLE_USER')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $adminRole = new Roles();
        $adminRole->setRoleName('ROLE_ADMIN')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $superAdminRole = new Roles();
        $superAdminRole->setRoleName('ROLE_SUPER_ADMIN')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $manager->persist($userRole);
        $manager->persist($adminRole);
        $manager->persist($superAdminRole);

        // 1. Création du super administrateur
        $superAdmin = new Users();
        $superAdmin->setFirstName('Super')
            ->setLastName('Admin')
            ->setEmail('superadmin@example.com')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $hashedPassword = $this->passwordHasher->hashPassword($superAdmin, 'superadmin123');
        $superAdmin->setPassword($hashedPassword);

        $superAdmin->addRole($superAdminRole);
        $superAdmin->addRole($adminRole); // Super admin hérite aussi de ROLE_ADMIN
        $superAdmin->addRole($userRole); // Et de ROLE_USER
        $manager->persist($superAdmin);

        // 2. Création de l'administrateur
        $admin = new Users();
        $admin->setFirstName('Admin')
            ->setLastName('User')
            ->setEmail('admin@example.com')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);

        $admin->addRole($adminRole);
        $admin->addRole($userRole); // Administrateur hérite aussi de ROLE_USER
        $manager->persist($admin);

        // 3. Création de 20 utilisateurs classiques
        for ($i = 1; $i <= 20; $i++) {
            $user = new Users();
            $user->setFirstName("User{$i}")
                ->setLastName("Last{$i}")
                ->setEmail("user{$i}@example.com")
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());

            $hashedPassword = $this->passwordHasher->hashPassword($user, 'user123');
            $user->setPassword($hashedPassword);

            $user->addRole($userRole); // Chaque utilisateur reçoit ROLE_USER
            $manager->persist($user);
        }

        // Sauvegarde dans la base de données
        $manager->flush();
    }
}
