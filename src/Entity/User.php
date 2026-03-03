<?php

namespace App\Entity;

use App\Enum\Role; 
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')] 
#[UniqueEntity('email')] 
#[UniqueEntity('userName')] 
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email {{ value }} n\'est pas valide.')]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire')]
    #[Assert\Length(min: 8, minMessage: 'Votre mot de passe doit faire au moins {{ limit }} caractères')]
    #[ORM\Column(type: "string", length: 255)]
    private ?string $password = null; // Sera hashé

    #[ORM\Column(enumType: Role::class, name: "role")]
    private Role $role = Role::USER; // Rôle par défaut

    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    #[ORM\Column(length: 50)]
    private ?string $firstname = null;

    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[ORM\Column(length: 50)]
    private ?string $lastname = null;

    #[Assert\NotBlank(message: 'Le nom d\'utilisateur est obligatoire')]
    #[Assert\Length(min: 3, minMessage: 'Le nom d\'utilisateur doit faire au moins {{ limit }} caractères')]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $userName = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    // Un User peut avoir plusieurs NewsItems (tweets)
    // 'author' dans NewsItem est le ManyToOne inversé
    #[ORM\OneToMany(targetEntity: NewsItem::class, mappedBy: 'author', cascade: ['remove'])] 
    private Collection $newsItems;

    // Un User peut avoir émis plusieurs Likes (un Like est fait PAR un User)
    // 'user' dans Like est le ManyToOne inversé
    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: 'user', cascade: ['remove'])] 
    private Collection $givenLikes;

    /**
     * @var Collection<int, Follow>
     */
    #[ORM\OneToMany(targetEntity: Follow::class, mappedBy: 'follower')]
    private Collection $follows;

    /**
     * @var Collection<int, Follow>
     */
    #[ORM\OneToMany(targetEntity: Follow::class, mappedBy: 'following')]
    private Collection $following; 

    public function __construct()
    {
        $this->newsItems = new ArrayCollection();
        $this->givenLikes = new ArrayCollection();
        $this->follows = new ArrayCollection();
        $this->following = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- Symfony Security UserInterface methods ---

    // Cette méthode est requise par UserInterface et Doctrine pour identifier l'utilisateur.
    // Elle ne doit pas être redéfinie par un setId si l'ID est auto-généré.
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void
    {
        // Efface les informations sensibles, comme le mot de passe brut s'il était stocké
        // $this->password = null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER']; 
        if ($this->role instanceof Role) {
            $roles[] = $this->role->value; 
        }
        return array_unique($roles);
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(Role $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): static
    {
        $this->userName = $userName;
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

    // --- NewsItems (Tweets) owned by this User ---
    /**
     * @return Collection<int, NewsItem>
     */
    public function getNewsItems(): Collection
    {
        return $this->newsItems;
    }

    public function addNewsItem(NewsItem $newsItem): static
    {
        if (!$this->newsItems->contains($newsItem)) {
            $this->newsItems->add($newsItem);
            $newsItem->setAuthor($this);
        }
        return $this;
    }

    public function removeNewsItem(NewsItem $newsItem): static
    {
        if ($this->newsItems->removeElement($newsItem)) {
            // set the owning side to null (unless already changed)
            if ($newsItem->getAuthor() === $this) {
                $newsItem->setAuthor(null);
            }
        }
        return $this;
    }

    // --- Likes given by this User ---
    /**
     * @return Collection<int, Like>
     */
    public function getGivenLikes(): Collection
    {
        return $this->givenLikes;
    }

    public function addGivenLike(Like $like): static
    {
        if (!$this->givenLikes->contains($like)) {
            $this->givenLikes->add($like);
            $like->setUser($this);
        }
        return $this;
    }

    public function removeGivenLike(Like $like): static
    {
        if ($this->givenLikes->removeElement($like)) {
            if ($like->getUser() === $this) {
                $like->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Follow>
     */
    public function getFollows(): Collection
    {
        return $this->follows;
    }

    public function addFollow(Follow $follow): static
    {
        if (!$this->follows->contains($follow)) {
            $this->follows->add($follow);
            $follow->setFollower($this);
        }

        return $this;
    }

    public function removeFollow(Follow $follow): static
    {
        if ($this->follows->removeElement($follow)) {
            // set the owning side to null (unless already changed)
            if ($follow->getFollower() === $this) {
                $follow->setFollower(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Follow>
     */
    public function getFollowing(): Collection
    {
        return $this->following;
    }

    public function addFollowing(Follow $following): static
    {
        if (!$this->following->contains($following)) {
            $this->following->add($following);
            $following->setFollowing($this);
        }

        return $this;
    }

    public function removeFollowing(Follow $following): static
    {
        if ($this->following->removeElement($following)) {
            // set the owning side to null (unless already changed)
            if ($following->getFollowing() === $this) {
                $following->setFollowing(null);
            }
        }

        return $this;
    }
}
