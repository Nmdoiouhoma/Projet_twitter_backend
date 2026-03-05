<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;

final class UserController extends AbstractController
{
    #[Route('/api/users', name: 'app_user', methods: ['GET'])]
    public function GetUsers(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();

        $userData = array_map(function(User $user) {
            return [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'lastname' => $user->getLastname(),
                'firstname' => $user->getFirstname()
            ];
        }, $users);
    
        return $this->json([
            'success' => true,
            'data' => $userData,
            'count' => count($userData)
        ]);
    }
    #[Route('/api/user/{userName}', name: 'app_user_by_name', methods: ['GET'])]
    public function getUserByUsername(UserRepository $userRepository, string $userName): JsonResponse
    {
        $user = $userRepository->findOneBy(['userName' => $userName]);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
            ]
        ]);
    }

    #[Route('/api/user/{userName}', name: 'update_user', methods: ['PATCH'])]
    public function updateUser(UserRepository $userRepository, string $userName, Request $request): JsonResponse
    {
        $user = $userRepository->findOneBy(['userName' => $userName]);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    
        $data = json_decode($request->getContent(), true);
        
        // Vérifier si le nouveau userName existe déjà
        if (isset($data['userName']) && $data['userName'] !== $user->getUserName()) {
            $existingUser = $userRepository->findOneBy(['userName' => $data['userName']]);
            if ($existingUser) {
                return $this->json([
                    'success' => false,
                    'message' => 'Username already exists'
                ], 409);
            }
        }
        
        $user->setUsername($data['username'] ?? $user->getUsername());
        $user->setPassword($data['password'] ?? $user->getPassword());
        $user->setLastname($data['lastname'] ?? $user->getLastname());
        $user->setFirstname($data['firstname'] ?? $user->getFirstname());
        $user->setEmail($data['email'] ?? $user->getEmail());

        $userRepository->save($user, true);

        return $this->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
            ]
        ]);
    }

    #[Route('/api/user/{userName}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(UserRepository $userRepository, string $userName): JsonResponse
    {
        $user = $userRepository->findOneBy(['userName' => $userName]);

        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $userRepository->remove($user, true);

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
            ],
            'message' => 'User deleted successfully'
        ]);
    }

    #[Route('/api/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

    // Avec JWT, la déconnexion se fait côté client (suppression du token)
    return $this->json([
        'success' => true,
        'message' => 'User logged out successfully',
        'data' => [
            'username' => $currentUser->getUsername()
        ]
    ]);
}
}