<?php

namespace App\DataFixtures;

use App\Entity\Tweet;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
{
    $firstnames = ['Alice', 'Bob', 'Charlie', 'Diana', 'Eve'];
    $lastnames = ['Dupont', 'Martin', 'Bernard', 'Durand', 'Petit'];

    for ($i = 1; $i <= 5; $i++) {
        $user = new User();
        $user->setEmail("user{$i}@twitter.com");
        $user->setUsername("user{$i}");
        $user->setFirstname($firstnames[$i - 1]);
        $user->setLastname($lastnames[$i - 1]);
        $user->setPassword(
            $this->hasher->hashPassword($user, 'password123')
        );

        $manager->persist($user);

        // Chaque user crée 3 tweets
        for ($j = 1; $j <= 3; $j++) {
            $tweet = new Tweet();
            $tweet->setContent("Tweet numéro {$j} de user{$i} !");
            $tweet->setAuthor($user);

            $manager->persist($tweet);
        }
    }

    $manager->flush();
}
}
