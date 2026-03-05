<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController extends AbstractController
{
    #[Route('/api/login', name: 'app_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtTokenManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        $identifier = $data['email'] ?? $data['userName'] ?? null;
        $password = $data['password'] ?? null;

        if (!$identifier || !$password) {
            return $this->json(['error' => 'Email/Username and password required'], 400);
        }

        $user = $userRepository->findOneBy(['email' => $identifier]) 
            ?? $userRepository->findOneBy(['userName' => $identifier]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Invalid credentials'], 401);
        }

        $token = $jwtTokenManager->create($user);

        return $this->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'userName' => $user->getUserName(),
                // Keep key name aligned with register payload; stored in entity as `imageUrl`
                'profileImageUrl' => $user->getImageUrl(),
            ]
        ], 200);
    }
}