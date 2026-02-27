<?php

namespace App\Controller;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Enum\Role;

final class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function index(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        JWTTokenManagerInterface $jwtTokenManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        $email = $data['email'] ?? null;
        $firtname = $data['firstname'] ?? null;
        $lastname = $data['lastname'] ?? null;
        $username = $data ['userName'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json(['error' => 'Email and password required'], 400);
        }

        $user = new User();
        $user->setRole(Role::USER);
        $user->setEmail($email);
        $user->setFirstname($firtname);
        $user->setLastname($lastname);
        $user->setUserName($username);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], 400);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $token = $jwtTokenManager->create($user);

        return $this->json(['message' => 'User registered successfully', 'token' => $token], 201);
    }
}
