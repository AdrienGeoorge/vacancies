<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use App\Repository\TripRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\MaxDepth;

#[ORM\Entity(repositoryClass: TripRepository::class)]
class Trip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $departureDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $returnDate = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $image = null;

    #[ApiProperty(readableLink: true)]
    #[ORM\ManyToOne(inversedBy: 'trips')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $traveler = null;

    #[ORM\OneToMany(targetEntity: Accommodation::class, mappedBy: 'trip', orphanRemoval: true)]
    private Collection $accommodations;

    #[ORM\OneToMany(targetEntity: Transport::class, mappedBy: 'trip', orphanRemoval: true)]
    private Collection $transports;

    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'trip', orphanRemoval: true)]
    private Collection $activities;

    #[ORM\OneToMany(targetEntity: VariousExpensive::class, mappedBy: 'trip', orphanRemoval: true)]
    private Collection $variousExpensives;

    #[ORM\OneToMany(targetEntity: TripDocument::class, mappedBy: 'trip', orphanRemoval: true)]
    private Collection $documents;

    #[ORM\OneToMany(targetEntity: PlanningEvent::class, mappedBy: 'trip', orphanRemoval: true)]
    #[ORM\OrderBy(['start' => 'ASC'])]
    private Collection $planningEvents;

    #[ORM\OneToMany(targetEntity: ShareInvitation::class, mappedBy: 'trip', orphanRemoval: true)]
    private Collection $shareInvitations;

    #[ORM\OneToMany(targetEntity: OnSiteExpense::class, mappedBy: 'trip', orphanRemoval: true)]
    private Collection $onSiteExpenses;

    #[ApiProperty(readableLink: true)]
    #[ORM\OneToMany(targetEntity: TripTraveler::class, mappedBy: 'trip', orphanRemoval: true)]
    private Collection $tripTravelers;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $blocNotes = null;

    #[ORM\Column(length: 9, nullable: true)]
    private ?string $visibility = null;

    #[ORM\OneToMany(targetEntity: TripDestination::class, mappedBy: 'trip', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['displayOrder' => 'ASC'])]
    #[MaxDepth(1)]
    private Collection $destinations;

    public function __construct()
    {
        $this->accommodations = new ArrayCollection();
        $this->transports = new ArrayCollection();
        $this->activities = new ArrayCollection();
        $this->variousExpensives = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->planningEvents = new ArrayCollection();
        $this->shareInvitations = new ArrayCollection();
        $this->onSiteExpenses = new ArrayCollection();
        $this->tripTravelers = new ArrayCollection();
        $this->destinations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDepartureDate(): ?\DateTimeInterface
    {
        return $this->departureDate;
    }

    public function setDepartureDate(?\DateTimeInterface $departureDate): static
    {
        $this->departureDate = $departureDate;

        return $this;
    }

    public function getReturnDate(): ?\DateTimeInterface
    {
        return $this->returnDate;
    }

    public function setReturnDate(?\DateTimeInterface $returnDate): static
    {
        $this->returnDate = $returnDate;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getTraveler(): ?User
    {
        return $this->traveler;
    }

    public function setTraveler(?User $traveler): static
    {
        $this->traveler = $traveler;

        return $this;
    }

    /**
     * @return Collection<int, Accommodation>
     */
    public function getAccommodations(): Collection
    {
        return $this->accommodations;
    }

    public function addAccommodation(Accommodation $accommodation): static
    {
        if (!$this->accommodations->contains($accommodation)) {
            $this->accommodations->add($accommodation);
            $accommodation->setTrip($this);
        }

        return $this;
    }

    public function removeAccommodation(Accommodation $accommodation): static
    {
        if ($this->accommodations->removeElement($accommodation)) {
            // set the owning side to null (unless already changed)
            if ($accommodation->getTrip() === $this) {
                $accommodation->setTrip(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transport>
     */
    public function getTransports(): Collection
    {
        return $this->transports;
    }

    public function addTransport(Transport $transport): static
    {
        if (!$this->transports->contains($transport)) {
            $this->transports->add($transport);
            $transport->setTrip($this);
        }

        return $this;
    }

    public function removeTransport(Transport $transport): static
    {
        if ($this->transports->removeElement($transport)) {
            // set the owning side to null (unless already changed)
            if ($transport->getTrip() === $this) {
                $transport->setTrip(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Activity>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function addActivity(Activity $activity): static
    {
        if (!$this->activities->contains($activity)) {
            $this->activities->add($activity);
            $activity->setTrip($this);
        }

        return $this;
    }

    public function removeActivity(Activity $activity): static
    {
        if ($this->activities->removeElement($activity)) {
            // set the owning side to null (unless already changed)
            if ($activity->getTrip() === $this) {
                $activity->setTrip(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, VariousExpensive>
     */
    public function getVariousExpensives(): Collection
    {
        return $this->variousExpensives;
    }

    public function addVariousExpensife(VariousExpensive $variousExpensife): static
    {
        if (!$this->variousExpensives->contains($variousExpensife)) {
            $this->variousExpensives->add($variousExpensife);
            $variousExpensife->setTrip($this);
        }

        return $this;
    }

    public function removeVariousExpensife(VariousExpensive $variousExpensife): static
    {
        if ($this->variousExpensives->removeElement($variousExpensife)) {
            // set the owning side to null (unless already changed)
            if ($variousExpensife->getTrip() === $this) {
                $variousExpensife->setTrip(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TripDocument>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(TripDocument $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setTrip($this);
        }

        return $this;
    }

    public function removeDocument(TripDocument $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getTrip() === $this) {
                $document->setTrip(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PlanningEvent>
     */
    public function getPlanningEvents(): Collection
    {
        return $this->planningEvents;
    }

    public function addPlanningEvent(PlanningEvent $planningEvent): static
    {
        if (!$this->planningEvents->contains($planningEvent)) {
            $this->planningEvents->add($planningEvent);
            $planningEvent->setTrip($this);
        }

        return $this;
    }

    public function removePlanningEvent(PlanningEvent $planningEvent): static
    {
        if ($this->planningEvents->removeElement($planningEvent)) {
            // set the owning side to null (unless already changed)
            if ($planningEvent->getTrip() === $this) {
                $planningEvent->setTrip(null);
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
            $shareInvitation->setTrip($this);
        }

        return $this;
    }

    public function removeShareInvitation(ShareInvitation $shareInvitation): static
    {
        if ($this->shareInvitations->removeElement($shareInvitation)) {
            // set the owning side to null (unless already changed)
            if ($shareInvitation->getTrip() === $this) {
                $shareInvitation->setTrip(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, OnSiteExpense>
     */
    public function getOnSiteExpenses(): Collection
    {
        return $this->onSiteExpenses;
    }

    public function addOnSiteExpense(OnSiteExpense $onSiteExpense): static
    {
        if (!$this->onSiteExpenses->contains($onSiteExpense)) {
            $this->onSiteExpenses->add($onSiteExpense);
            $onSiteExpense->setTrip($this);
        }

        return $this;
    }

    public function removeOnSiteExpense(OnSiteExpense $onSiteExpense): static
    {
        if ($this->onSiteExpenses->removeElement($onSiteExpense)) {
            // set the owning side to null (unless already changed)
            if ($onSiteExpense->getTrip() === $this) {
                $onSiteExpense->setTrip(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TripTraveler>
     */
    public function getTripTravelers(): Collection
    {
        return $this->tripTravelers;
    }

    public function addTripTraveler(TripTraveler $tripTraveler): static
    {
        if (!$this->tripTravelers->contains($tripTraveler)) {
            $this->tripTravelers->add($tripTraveler);
            $tripTraveler->setTrip($this);
        }

        return $this;
    }

    public function removeTripTraveler(TripTraveler $tripTraveler): static
    {
        if ($this->tripTravelers->removeElement($tripTraveler)) {
            // set the owning side to null (unless already changed)
            if ($tripTraveler->getTrip() === $this) {
                $tripTraveler->setTrip(null);
            }
        }

        return $this;
    }

    public function getBlocNotes(): ?string
    {
        return $this->blocNotes;
    }

    public function setBlocNotes(?string $blocNotes): static
    {
        $this->blocNotes = $blocNotes;

        return $this;
    }

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    public function setVisibility(?string $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @return Collection<int, TripDestination>
     */
    public function getDestinations(): Collection
    {
        return $this->destinations;
    }

    public function addDestination(TripDestination $destination): self
    {
        if (!$this->destinations->contains($destination)) {
            $this->destinations->add($destination);
            $destination->setTrip($this);
        }
        return $this;
    }

    public function removeDestination(TripDestination $destination): self
    {
        if ($this->destinations->removeElement($destination)) {
            if ($destination->getTrip() === $this) {
                $destination->setTrip(null);
            }
        }
        return $this;
    }
}
