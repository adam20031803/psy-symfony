<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Create an admin user')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@atomicyou.com']);
        if ($existing) {
            $output->writeln('<comment>Admin already exists: admin@atomicyou.com</comment>');
            return Command::SUCCESS;
        }

        $admin = new User();
        $admin->setPrenom('Admin');
        $admin->setNom('Atomic You');
        $admin->setEmail('admin@atomicyou.com');
        $admin->setRole('admin');
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));

        $this->em->persist($admin);
        $this->em->flush();

        $output->writeln('<info>✅ Admin created successfully!</info>');
        $output->writeln('  Email   : admin@atomicyou.com');
        $output->writeln('  Password: admin123');

        return Command::SUCCESS;
    }
}
