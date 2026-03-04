<?php

namespace App\Entity;

use App\Repository\NewsItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsItemRepository::class)]
#[ORM\HasLifecycleCallbacks] // Utile pour createdAt si vous ne l'initialisez pas dans le constructeur
class NewsItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null; // Pourrait être optionnel ou juste le début du contenu

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null; // Le corps du tweet

    #[ORM\Column]
    private ?bool $isPublished = false; // Par défaut non publié

    #[ORM\Column(nullable: true)] // Peut être null si non publié ou si publié immédiatement
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(length: 255, nullable: true)] // L'image est optionnelle
    private ?string $featuredImage = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    // Un NewsItem est créé PAR UN User (l'auteur)
    #[ORM\ManyToOne(inversedBy: 'newsItems')] // 'newsItems' sera dans User
    #[ORM\JoinColumn(nullable: false)] // Un tweet doit avoir un auteur
    private ?User $author = null;

    // Un NewsItem a plusieurs Likes associés
    // La relation est gérée par l'entité Like (owning side)
    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: 'newsItem', cascade: ['remove'])] // cascade: ['remove'] supprime les likes si le newsItem est supprimé
    private Collection $likes;

    #[ORM\Column]
    private int $likesCount = 0; // Initialisé à 0

    // Pas besoin de createdAt si on utilise HasLifecycleCallbacks et le setter
    // #[ORM\Column]
    // private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->likes = new ArrayCollection();
        // $this->createdAt = new \DateTimeImmutable(); // Peut être géré par @ORM\PrePersist
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
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

    public function isPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;
        // Si on publie, on set la date de publication
        if ($isPublished && !$this->publishedAt) {
            $this->setPublishedAt(new \DateTimeImmutable());
        }
        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getFeaturedImage(): ?string
    {
        return $this->featuredImage;
    }

    public function setFeaturedImage(?string $featuredImage): static
    {
        $this->featuredImage = $featuredImage;
        return $this;
    }
    
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Pas besoin de setter si @ORM\PrePersist est utilisé et si on ne change pas la date de création
    // public function setCreatedAt(\DateTimeImmutable $createdAt): static
    // {
    //     $this->createdAt = $createdAt;
    //     return $this;
    // }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;
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
            $like->setNewsItem($this);
            // Incrémente le compteur de likes
            $this->incrementLikesCount();
        }
        return $this;
    }

    public function removeLike(Like $like): static
    {
        if ($this->likes->removeElement($like)) {
            // set the owning side to null (unless already changed)
            if ($like->getNewsItem() === $this) {
                $like->setNewsItem(null);
                // Décrémente le compteur de likes
                $this->decrementLikesCount();
            }
        }
        return $this;
    }

    public function getLikesCount(): int
    {
        return $this->likesCount;
    }

    // Méthodes pour gérer le compteur de likes
    public function incrementLikesCount(): void
    {
        $this->likesCount++;
    }

    public function decrementLikesCount(): void
    {
        // Assure que le compteur ne descend pas en dessous de 0
        $this->likesCount = max(0, $this->likesCount - 1);
    }

    // Méthode pour mettre à jour le likesCount à partir de la collection de Likes
    // Utile si le compteur n'est pas mis à jour via add/removeLike directement.
    // Peut être appelé via @ORM\PostLoad ou manuellement.
    public function updateLikesCountFromCollection(): void
    {
        $this->likesCount = $this->likes->count();
    }
}
