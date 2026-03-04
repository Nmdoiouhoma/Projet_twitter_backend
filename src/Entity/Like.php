<?php

namespace App\Entity;

use App\Repository\LikeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LikeRepository::class)]
#[ORM\Table(name: 'likes')] 
class Like
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'givenLikes')] 
    #[ORM\JoinColumn(nullable: false)] 
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'likes')] 
    #[ORM\JoinColumn(nullable: false)] 
    private ?NewsItem $newsItem = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $likedAt = null;

    // Constructeur pour initialiser la date
    public function __construct()
    {
        $this->likedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getNewsItem(): ?NewsItem
    {
        return $this->newsItem;
    }

    public function setNewsItem(?NewsItem $newsItem): static
    {
        $this->newsItem = $newsItem;
        return $this;
    }

    public function getLikedAt(): ?\DateTimeImmutable
    {
        return $this->likedAt;
    }

    public function setLikedAt(\DateTimeImmutable $likedAt): static
    {
        $this->likedAt = $likedAt;
        return $this;
    }
}
