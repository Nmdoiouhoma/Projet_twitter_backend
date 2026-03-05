<?php

namespace App\Controller;

use App\Entity\Follow;
use App\Entity\User;
use App\Entity\Notification;
use App\Repository\FollowRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;

final class FollowController extends AbstractController
{
    #[Route('/api/follow/user/{id}', name: 'app_follow', methods: ['POST'])]
public function followUser(
    User $userToFollow,
    FollowRepository $followUserRepository,
    EntityManagerInterface $entityManager
): JsonResponse {
    $currentUser = $this->getUser();

    if (!$currentUser instanceof User) {
        return $this->json(['error' => 'Unauthorized'], 401);
    }

    if ($currentUser === $userToFollow) {
        return $this->json(['error' => 'Cannot follow yourself'], 400);
    }

    $existingFollow = $followUserRepository->findOneBy([
        'follower' => $currentUser,
        'following' => $userToFollow,
    ]);

    if ($existingFollow) {
        return $this->json(['error' => 'Already following this user'], 400);
    }

    $follow = new Follow();
    $follow->setFollower($currentUser);
    $follow->setFollowing($userToFollow);

    $currentUser->incrementCountFollowing();
    $userToFollow->incrementCountFollowers();

    // Notification "follow"
    $notification = new Notification();
    $notification->setType('follow');
    $notification->setUser($userToFollow);                
    $notification->setFollower($currentUser);            
    $notification->setIsRead(false);
    $notification->setCreatedAt(new \DateTimeImmutable());

    $entityManager->persist($notification);
    $entityManager->persist($follow);
    $entityManager->persist($currentUser);
    $entityManager->persist($userToFollow);
    $entityManager->flush();

    return $this->json([
        'message' => 'User followed successfully',
        'username' => $userToFollow->getUserName(),
        'followersCount' => $userToFollow->getCountFollowers(),
        'userId' => $userToFollow->getId(),
        'followingCount' => $currentUser->getCountFollowing(),
    ]);
}

    #[Route('/api/unfollow/user/{id}', name: 'app_unfollow', methods: ['DELETE'])]
    public function unfollowUser(
        User $userToUnfollow,
        FollowRepository $followUserRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $currentUser = $this->getUser();

       if (!$currentUser) {
           return $this->json(['error' => 'Unauthorized'], 401);
       }

       $existingFollow = $followUserRepository->findOneBy([
           'follower' => $currentUser,
           'following' => $userToUnfollow
       ]);

       if (!$existingFollow) {
           return $this->json(['error' => 'Not following this user'], 400);
       }

       // met à jour les compteurs sur les deux utilisateurs
       $currentUser->decrementCountFollowing();
       $userToUnfollow->decrementCountFollowers();

       $entityManager->remove($existingFollow);
       $entityManager->persist($currentUser);
       $entityManager->persist($userToUnfollow);
       $entityManager->flush();

       return $this->json([
           'message' => 'User unfollowed successfully',
           'username' => $userToUnfollow->getUserName(),
           'followersCount' => $userToUnfollow->getCountFollowers(),
           'followingCount' => $currentUser->getCountFollowing(),
       ]);
   }

    #[Route('/api/users/{id}/following', name: 'user_following', methods: ['GET'])]
    public function getFollowing(User $user, FollowRepository $followRepository): JsonResponse
    {
        $following = $followRepository->findBy(['follower' => $user]);

        $data = array_map(function (Follow $follow) {
            return [
                'id' => $follow->getFollowing()->getId(),
                'username' => $follow->getFollowing()->getUsername(),
                'followedAt' => $follow->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }, $following);

        return $this->json($data);
    }

    #[Route('/api/users/{id}/followers', name: 'user_followers', methods: ['GET'])]
    public function getFollowers(User $user, FollowRepository $followRepository): JsonResponse
    {
        $followers = $followRepository->findBy(['following' => $user]);

        $data = array_map(function (Follow $follow) {
            return [
                'id' => $follow->getFollower()->getId(),
                'username' => $follow->getFollower()->getUsername(),
                'followedAt' => $follow->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }, $followers);

        return $this->json($data);
    }
}