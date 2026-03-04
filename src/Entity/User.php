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
#[ORM\HasLifecycleCallbacks]
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

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Tweet::class, orphanRemoval: true)]
    private Collection $tweets;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

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

    #[ORM\Column(type: 'integer')]
    private int $countFollowers = 0;

    #[ORM\Column(type: 'integer')]
    private int $countFollowing = 0;

    public function __construct()
    {
        $this->givenLikes = new ArrayCollection();
        $this->follows = new ArrayCollection();
        $this->following = new ArrayCollection();
        $this->tweets = new ArrayCollection();
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->userName;
    }

    public function eraseCredentials(): void
    {
       
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
            if ($following->getFollowing() === $this) {
                $following->setFollowing(null);
            }
        }

        return $this;
    }

    public function getTweets(): Collection
    {
        return $this->tweets;
    }

    public function addTweet(Tweet $tweet): static
    {
        if (!$this->tweets->contains($tweet)) {
            $this->tweets->add($tweet);
            $tweet->setAuthor($this);
        }
        return $this;
    }

    public function getCountFollowers(): int
    {
        return $this->countFollowers;
    }

    public function incrementCountFollowers(): void
    {
        $this->countFollowers++;
    }

    public function decrementCountFollowers(): void
    {
        if ($this->countFollowers > 0) {
            $this->countFollowers--;
        }
    }

    public function getCountFollowing(): int
    {
        return $this->countFollowing;
    }

    public function incrementCountFollowing(): void
    {
        $this->countFollowing++;
    }

    public function decrementCountFollowing(): void
    {
        if ($this->countFollowing > 0) {
            $this->countFollowing--;
        }
    }
}
