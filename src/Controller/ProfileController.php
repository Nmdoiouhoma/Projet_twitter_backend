<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;

final class ProfileController extends AbstractController
{
    #[Route('/api/profile/{userName}', name: 'app_user_profile', methods: ['GET'])]
    public function getUserProfile(UserRepository $userRepository, string $userName): JsonResponse
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
                'username' => $user->getUserName(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'profileImageUrl' => $user->getImageUrl(),
                'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
                'tweets' => array_map(function($tweet) {
                    return [
                        'id' => $tweet->getId(),
                        'content' => $tweet->getContent(),
                        'createdAt' => $tweet->getCreatedAt()?->format('Y-m-d H:i:s'),
                        'imageUrl' => $tweet->getImageUrl(),
                        'likesCount' => count($tweet->getLikes()),
                    ];
                }, $user->getTweets()->toArray()),
                'tweetsCount' => count($user->getTweets()),
            ]
        ]);
    }
}
