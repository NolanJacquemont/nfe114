<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\PractitionerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PractitionerRepository::class)]
#[ORM\Table(name: 'practitioners')]
#[ApiResource]
class Practitioner extends User
{
    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $speciality = null;

//    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
//    #[ORM\JoinColumn(nullable: false)]
//    private ?User $user = null;

    /**
     * @var Collection<int, Slot>
     */
    #[ORM\OneToMany(targetEntity: Slot::class, mappedBy: 'practitioner', orphanRemoval: true)]
    private Collection $slots;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    public function __construct()
    {
        parent::__construct();
        $this->slots = new ArrayCollection();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSpeciality(): ?string
    {
        return $this->speciality;
    }

    public function setSpeciality(string $speciality): static
    {
        $this->speciality = $speciality;

        return $this;
    }

//    public function getUser(): ?User
//    {
//        return $this->user;
//    }
//
//    public function setUser(User $user): static
//    {
//        $this->user = $user;
//
//        return $this;
//    }

    /**
     * @return Collection<int, Slot>
     */
    public function getSlots(): Collection
    {
        return $this->slots;
    }

    public function addSlot(Slot $slot): static
    {
        if (!$this->slots->contains($slot)) {
            $this->slots->add($slot);
            $slot->setPractitioner($this);
        }

        return $this;
    }

    public function removeSlot(Slot $slot): static
    {
        if ($this->slots->removeElement($slot)) {
            // set the owning side to null (unless already changed)
            if ($slot->getPractitioner() === $this) {
                $slot->setPractitioner(null);
            }
        }

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

}
