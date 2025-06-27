<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse mail est déjà utilisée')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $lastname = null;

    #[ORM\OneToMany(targetEntity: Trip::class, mappedBy: 'traveler', orphanRemoval: true)]
    private Collection $trips;

    #[ORM\OneToMany(targetEntity: ShareInvitation::class, mappedBy: 'userToShareWith', orphanRemoval: true)]
    private Collection $shareInvitations;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(length: 25, unique: true, nullable: false)]
    private string $username;

    #[ORM\Column(nullable: false)]
    private bool $privateProfile;

    #[ORM\OneToMany(targetEntity: Follows::class, mappedBy: 'follower', orphanRemoval: true)]
    private Collection $follows;

    #[ORM\OneToMany(targetEntity: Follows::class, mappedBy: 'followedBy', orphanRemoval: true)]
    private Collection $followedBy;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $avatar = null;

    #[ORM\OneToMany(targetEntity: UserBadges::class, mappedBy: 'user', orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'DESC'])]
    private Collection $userBadges;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $biography = null;

    public function __construct()
    {
        $this->trips = new ArrayCollection();
        $this->shareInvitations = new ArrayCollection();
        $this->follows = new ArrayCollection();
        $this->followedBy = new ArrayCollection();
        $this->privateProfile = false;
        $this->setRoles(['ROLE_USER']);
        $this->userBadges = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getCompleteName(): ?string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    /**
     * @return Collection<int, Trip>
     */
    public function getTrips(): Collection
    {
        return $this->trips;
    }

    public function addTrip(Trip $trip): static
    {
        if (!$this->trips->contains($trip)) {
            $this->trips->add($trip);
            $trip->setTraveler($this);
        }

        return $this;
    }

    public function removeTrip(Trip $trip): static
    {
        if ($this->trips->removeElement($trip)) {
            // set the owning side to null (unless already changed)
            if ($trip->getTraveler() === $this) {
                $trip->setTraveler(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ShareInvitation>
     */
    public function getShareInvitations(): Collection
    {
        return $this->shareInvitations;
    }

    public function addShareInvitation(ShareInvitation $shareInvitation): static
    {
        if (!$this->shareInvitations->contains($shareInvitation)) {
            $this->shareInvitations->add($shareInvitation);
            $shareInvitation->setUserToShareWith($this);
        }

        return $this;
    }

    public function removeShareInvitation(ShareInvitation $shareInvitation): static
    {
        if ($this->shareInvitations->removeElement($shareInvitation)) {
            // set the owning side to null (unless already changed)
            if ($shareInvitation->getUserToShareWith() === $this) {
                $shareInvitation->setUserToShareWith(null);
            }
        }

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(string $googleId): static
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function isPrivateProfile(): bool
    {
        return $this->privateProfile;
    }

    public function setPrivateProfile(bool $privateProfile): static
    {
        $this->privateProfile = $privateProfile;

        return $this;
    }

    /**
     * @return Collection<int, Follows>
     */
    public function getFollows(): Collection
    {
        return $this->follows;
    }

    /**
     * @return Collection<int, Follows>
     */
    public function getApprovedFollows(): Collection
    {
        return $this->follows->filter(function ($follow) {
            return $follow->isIsApproved();
        });
    }

    public function addFollow(Follows $follow): static
    {
        if (!$this->follows->contains($follow)) {
            $this->follows->add($follow);
            $follow->setFollower($this);
        }

        return $this;
    }

    public function removeFollow(Follows $follow): static
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
     * @return Collection<int, Follows>
     */
    public function getFollowedBy(): Collection
    {
        return $this->followedBy;
    }

    /**
     * @return Collection<int, Follows>
     */
    public function getApprovedFollowedBy(): Collection
    {
        return $this->followedBy->filter(function ($followed) {
            return $followed->isIsApproved();
        });
    }

    public function addFollowedBy(Follows $followedBy): static
    {
        if (!$this->followedBy->contains($followedBy)) {
            $this->followedBy->add($followedBy);
            $followedBy->setFollowedBy($this);
        }

        return $this;
    }

    public function removeFollowedBy(Follows $followedBy): static
    {
        if ($this->followedBy->removeElement($followedBy)) {
            // set the owning side to null (unless already changed)
            if ($followedBy->getFollowedBy() === $this) {
                $followedBy->setFollowedBy(null);
            }
        }

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return Collection<int, UserBadges>
     */
    public function getUserBadges(): Collection
    {
        return $this->userBadges;
    }

    public function addUserBadge(UserBadges $userBadge): static
    {
        if (!$this->userBadges->contains($userBadge)) {
            $this->userBadges->add($userBadge);
            $userBadge->setUser($this);
        }

        return $this;
    }

    public function removeUserBadge(UserBadges $userBadge): static
    {
        if ($this->userBadges->removeElement($userBadge)) {
            // set the owning side to null (unless already changed)
            if ($userBadge->getUser() === $this) {
                $userBadge->setUser(null);
            }
        }

        return $this;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(?string $biography): static
    {
        $this->biography = $biography;

        return $this;
    }
}
