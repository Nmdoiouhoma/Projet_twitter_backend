<?php

namespace App\Entity;

use App\Repository\TweetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TweetRepository::class)]
#[ORM\HasLifecycleCallbacks] // ← AJOUT
class Tweet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tweets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\Column(length: 280)] 
    private ?string $content = null;

    #[ORM\Column(options: ['default' => 0])] 
    private int $likeCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $retweetsCount = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)] 
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Like>
     */
    #[ORM\OneToMany(mappedBy: 'tweet', targetEntity: Like::class, cascade: ['remove'])]
    private Collection $likes;

    public function __construct()
    {
        $this->likes = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->likeCount = 0;
        $this->retweetsCount = 0;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getLikeCount(): int
    {
        return $this->likeCount;
    }

    public function setLikeCount(int $likeCount): static
    {
        $this->likeCount = $likeCount;
        return $this;
    }

    /**
     * @return Collection<int, Like>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(Like $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setTweet($this);
            $this->incrementLikeCount();
        }

        return $this;
    }

    public function removeLike(Like $like): static
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getTweet() === $this) {
                $like->setTweet(null);
                $this->decrementLikeCount();
            }
        }

        return $this;
    }

    public function incrementLikeCount(): void
    {
        $this->likeCount++;
    }

    public function decrementLikeCount(): void
    {
        $this->likeCount = max(0, $this->likeCount - 1);
    }

    public function updateLikeCountFromCollection(): void
    {
        $this->likeCount = $this->likes->count();
    }

    public function getRetweetsCount(): int
    {
        return $this->retweetsCount;
    }

    public function setRetweetsCount(int $retweetsCount): static
    {
        $this->retweetsCount = $retweetsCount;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
