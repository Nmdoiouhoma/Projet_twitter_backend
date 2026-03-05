<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Entity\Tweet;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api/notifications')]
final class NoticationsController extends AbstractController
{
    #[Route('', name: 'notifications_index', methods: ['GET'])]
    public function index(
        #[CurrentUser] ?User $user,
        NotificationRepository $repo
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $notifications = $repo->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        $data = array_map(function (Notification $n) {
            return [
                'id' => $n->getId(),
                'type' => $n->getType(),
                'createdAt' => $n->getCreatedAt()?->format('Y-m-d H:i:s'),
                'isRead' => $n->isRead(),
                'actorProfileImageUrl' => $n->getFollower()?->getImageUrl()
                ?? $n->getLiker()?->getImageUrl(),
                'followerUsername' => $n->getFollower()?->getUsername(),
                'likerUsername' => $n->getLiker()?->getUsername(),
                'tweetId' => $n->getTweet()?->getId(),
                'tweetContent' => $n->getTweet()?->getContent(),
            ];
        }, $notifications);

        return $this->json($data);
    }

    #[Route('/{id}/read', name: 'notification_mark_read', methods: ['PATCH'])]
    public function markAsRead(
        #[CurrentUser] ?User $user,
        Notification $notification,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        if ($notification->getUser() !== $user) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $notification->setIsRead(true);
        $em->flush();

        return $this->json(['message' => 'Notification marked as read']);
    }

    #[Route('', name: 'notifications_clear', methods: ['DELETE'])]
    public function deleteAll(
        #[CurrentUser] ?User $user,
        NotificationRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $notifications = $repo->findBy(['user' => $user]);
        foreach ($notifications as $notification) {
            $em->remove($notification);
        }
        $em->flush();

        return $this->json(['message' => 'All notifications deleted']);
    }
}