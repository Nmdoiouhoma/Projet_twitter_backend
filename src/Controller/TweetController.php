<?php

namespace App\Controller;

use App\Entity\Like;
use App\Entity\Tweet;
use App\Entity\User;
use App\Entity\Follow;
use App\Repository\FollowRepository;
use App\Repository\LikeRepository;
use App\Repository\TweetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class TweetController extends AbstractController
{
    #[Route('/api/tweets', name: 'tweet_index', methods: ['GET'])]
    public function index(TweetRepository $tweetRepository): JsonResponse
    {
        $user = $this->getUser();

        $tweets = $tweetRepository->findBy([], ['createdAt' => 'DESC']);

        if ($user instanceof User) {
            $tweets = array_filter(
                $tweets,
                fn (Tweet $tweet) => $tweet->getAuthor() !== $user
            );
        }

        $data = array_map(function (Tweet $tweet) {
            return [
                'id' => $tweet->getId(),
                'content' => $tweet->getContent(),
                'imageUrl' => $tweet->getImageUrl(),
                'author' => [
                    'id' => $tweet->getAuthor()->getId(),
                    'username' => $tweet->getAuthor()->getUserName(),
                ],
                'likeCount' => $tweet->getLikeCount(),
                'retweetsCount' => $tweet->getRetweetsCount(),
                'createdAt' => $tweet->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $tweet->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $tweets);

        return $this->json($data);
    }

    #[Route('/upload', name: 'api_upload_public', methods: ['POST'])]
    public function uploadPublic(Request $request): JsonResponse
    {
        
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No file provided'], 400);
        }

        if (!str_starts_with($file->getMimeType() ?? '', 'image/')) {
            return $this->json(['error' => 'Invalid file type'], 400);
        }
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $newFilename = uniqid('tweet_', true) . '.' . $file->guessExtension();

        try {
            $file->move($uploadDir, $newFilename);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Failed to move file'], 500);
        }
        $publicUrl = sprintf(
            'http://127.0.0.1:8000/uploads/%s',
            $newFilename
        );

        return $this->json(['url' => $publicUrl], 201);
    }

    #[Route('/api/upload', name: 'api_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No file provided'], 400);
        }

        if (!str_starts_with($file->getMimeType() ?? '', 'image/')) {
            return $this->json(['error' => 'Invalid file type'], 400);
        }
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $newFilename = uniqid('tweet_', true) . '.' . $file->guessExtension();

        try {
            $file->move($uploadDir, $newFilename);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Failed to move file'], 500);
        }
        $publicUrl = sprintf(
            'http://127.0.0.1:8000/uploads/%s',
            $newFilename
        );

        return $this->json(['url' => $publicUrl], 201);
    }

    #[Route('/api/tweets', name: 'tweet_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $content = trim($data['content'] ?? '');

        if ($content === '') {
            return $this->json(['error' => 'Content is required'], 400);
        }

        if (mb_strlen($content) > 280) {
            return $this->json(['error' => 'Content must be at most 280 characters'], 400);
        }

        $tweet = new Tweet();
        $tweet->setContent($content);
        $tweet->setAuthor($user);

        $imageUrl = isset($data['imageUrl']) ? trim((string) $data['imageUrl']) : null;
        $tweet->setImageUrl($imageUrl !== '' ? $imageUrl : null);

        $entityManager->persist($tweet);
        $entityManager->flush();

        return $this->json([
            'id' => $tweet->getId(),
            'content' => $tweet->getContent(),
            'imageUrl' => $tweet->getImageUrl(),
            'author' => [
                'id' => $tweet->getAuthor()->getId(),
                'username' => $tweet->getAuthor()->getUserName(),
            ],
            'likeCount' => $tweet->getLikeCount(),
            'retweetsCount' => $tweet->getRetweetsCount(),
            'createdAt' => $tweet->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $tweet->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ], 201);
    }

    #[Route('/api/tweets/{id}', name: 'tweet_show', methods: ['GET'])]
    public function show(Tweet $tweet): JsonResponse
    {
        return $this->json([
            'id' => $tweet->getId(),
            'content' => $tweet->getContent(),
            'imageUrl' => $tweet->getImageUrl(),
            'author' => [
                'id' => $tweet->getAuthor()->getId(),
                'username' => $tweet->getAuthor()->getUserName(),
            ],
            'likeCount' => $tweet->getLikeCount(),
            'retweetsCount' => $tweet->getRetweetsCount(),
            'createdAt' => $tweet->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $tweet->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/api/tweets/{id}', name: 'tweet_update', methods: ['PUT', 'PATCH'])]
    public function update(
        Request $request,
        Tweet $tweet,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        if ($tweet->getAuthor() !== $user) {
            return $this->json(['error' => 'You can only edit your own tweets'], 403);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $content = trim($data['content'] ?? '');

        if ($content === '') {
            return $this->json(['error' => 'Content is required'], 400);
        }

        if (mb_strlen($content) > 280) {
            return $this->json(['error' => 'Content must be at most 280 characters'], 400);
        }

        $tweet->setContent($content);

        if (array_key_exists('imageUrl', $data)) {
            $imageUrl = trim((string) $data['imageUrl']);
            $tweet->setImageUrl($imageUrl !== '' ? $imageUrl : null);
        }

        $entityManager->flush();

        return $this->json([
            'id' => $tweet->getId(),
            'content' => $tweet->getContent(),
            'imageUrl' => $tweet->getImageUrl(),
            'author' => [
                'id' => $tweet->getAuthor()->getId(),
                'username' => $tweet->getAuthor()->getUserName(),
            ],
            'likeCount' => $tweet->getLikeCount(),
            'retweetsCount' => $tweet->getRetweetsCount(),
            'createdAt' => $tweet->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $tweet->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/api/tweets/{id}', name: 'tweet_delete', methods: ['DELETE'])]
    public function delete(
        Tweet $tweet,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        if ($tweet->getAuthor() !== $user) {
            return $this->json(['error' => 'You can only delete your own tweets'], 403);
        }

        $entityManager->remove($tweet);
        $entityManager->flush();

        return $this->json(null, 204);
    }

    #[Route('/api/tweets/{id}/like', name: 'tweet_like', methods: ['POST'])]
    public function like(
        Tweet $tweet,
        LikeRepository $likeRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $existingLike = $likeRepository->findOneBy([
            'user' => $user,
            'tweet' => $tweet,
        ]);

        if ($existingLike) {
            return $this->json(['error' => 'Already liked'], 400);
        }

        $like = new Like();
        $like->setUser($user);
        $like->setTweet($tweet);

        $tweet->addLike($like);

        $entityManager->persist($like);
        $entityManager->persist($tweet);
        $entityManager->flush();

        return $this->json([
            'message' => 'Tweet liked successfully',
            'tweetId' => $tweet->getId(),
            'likeCount' => $tweet->getLikeCount(),
        ]);
    }

    #[Route('/api/tweets/{id}/like', name: 'tweet_unlike', methods: ['DELETE'])]
    public function unlike(
        Tweet $tweet,
        LikeRepository $likeRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $existingLike = $likeRepository->findOneBy([
            'user' => $user,
            'tweet' => $tweet,
        ]);

        if (!$existingLike) {
            return $this->json(['error' => 'Not liked'], 400);
        }

        $tweet->removeLike($existingLike);

        $entityManager->remove($existingLike);
        $entityManager->persist($tweet);
        $entityManager->flush();

        return $this->json([
            'message' => 'Tweet unliked successfully',
            'tweetId' => $tweet->getId(),
            'likeCount' => $tweet->getLikeCount(),
        ]);
    }

    #[Route('/api/feed', name: 'tweet_feed', methods: ['GET'])]
    public function feed(
        TweetRepository $tweetRepository,
        FollowRepository $followRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        // Récupérer les utilisateurs suivis
        $follows = $followRepository->findBy(['follower' => $user]);
        $followingUsers = array_map(fn(Follow $f) => $f->getFollowing(), $follows);

        if (empty($followingUsers)) {
            return $this->json([]);
        }

        // Récupérer leurs tweets
        $tweets = $tweetRepository->findBy(
            ['author' => $followingUsers],
            ['createdAt' => 'DESC']
        );

        // Exclure les propres tweets de l'utilisateur au cas où il se suivrait
        $tweets = array_filter(
            $tweets,
            fn (Tweet $tweet) => $tweet->getAuthor() !== $user
        );

        $data = array_map(function (Tweet $tweet) {
            return [
                'id' => $tweet->getId(),
                'content' => $tweet->getContent(),
                'imageUrl' => $tweet->getImageUrl(),
                'author' => [
                    'id' => $tweet->getAuthor()->getId(),
                    'username' => $tweet->getAuthor()->getUserName(),
                ],
                'likeCount' => $tweet->getLikeCount(),
                'createdAt' => $tweet->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $tweets);

        return $this->json($data);
    }
}

