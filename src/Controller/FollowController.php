<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\FollowRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class FollowController extends AbstractController
{
   #[Route('/follow/user/{id}', name: 'app_follow', methods: ['POST'])]
   public function followUser(User $userToFollow): JsonResponse
   {
       $currentUser = $this->getUser();

       if (!$currentUser) {
           return $this->json(['error' => 'Unauthorized'], 401);
       }

       if ($currentUser === $userToFollow) {
           return $this->json(['error' => 'Cannot follow yourself'], 400);
       }

       $existingFollow = $this->followUserRepository->findOneBy([
           'follower' => $currentUser,
           'following' => $userToFollow
       ]);

         if ($existingFollow) {
              return $this->json(['error' => 'Already following this user'], 400);
         }

         $follow = new Follow();
         $follow->setFollower($currentUser);
         $follow->setFollowing($userToFollow);

         $this->followUserRepository->save($follow);

         return $this->json(['message' => 'User followed successfully', 'username' => $userToFollow->getUsername(),
         'followersCount' => count($userToFollow->getFollowers())]);
   }

   #[Route('/unfollow', name: 'app_unfollow', methods: ['DELETE'])]
   public function unfollowUser(): JsonResponse
   {
       $currentUser = $this->getUser();

       if (!$currentUser) {
           return $this->json(['error' => 'Unauthorized'], 401);
       }

       $userToUnfollow = $this->getUser(); // Get the user to unfollow (you might want to change this logic)

       $existingFollow = $this->followUserRepository->findOneBy([
           'follower' => $currentUser,
           'following' => $userToUnfollow
       ]);

       if (!$existingFollow) {
           return $this->json(['error' => 'Not following this user'], 400);
       }

       $this->followUserRepository->remove($existingFollow);

       return $this->json(['message' => 'User unfollowed successfully', 'username' => $userToUnfollow->getUsername(),
           'followersCount' => count($userToUnfollow->getFollowers())]);
   }

   #[Route('user/{id}/{following}', name: 'user_following', methods: ['GET'])]
   public function getFollowing(User $user): JsonResponse
   {
        $following = $this->followRepository->findBy(['follower' => $user]);

        $data = array_map(function (Follow $follow) {
            return [
                'id' => $follow->getFollowing()->getId(),
                'username' => $follow->getFollowing()->getUsername(),
                'followedAt' => $follow->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }, $following);

        return $this->json($data);
    }

    #[Route('/users/{id}/followers', name: 'user_followers', methods: ['GET'])]
    public function getFollowers(User $user): JsonResponse
    {
        $followers = $this->followRepository->findBy(['following' => $user]);

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