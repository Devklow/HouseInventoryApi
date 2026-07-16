<?php

namespace App\Entity;

use App\Repository\HouseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HouseRepository::class)]
class House
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $background = null;

    #[ORM\ManyToOne(inversedBy: 'houses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $userEntity = null;

    /**
     * @var Collection<int, StorageBox>
     */
    #[ORM\OneToMany(targetEntity: StorageBox::class, mappedBy: 'house', orphanRemoval: true)]
    private Collection $storages;

    /**
     * @var Collection<int, Wall>
     */
    #[ORM\OneToMany(targetEntity: Wall::class, mappedBy: 'house', orphanRemoval: true)]
    private Collection $walls;

    public function __construct()
    {
        $this->storages = new ArrayCollection();
        $this->walls = new ArrayCollection();
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

    public function getBackground(): ?string
    {
        return $this->background;
    }

    public function setBackground(string $background): static
    {
        $this->background = $background;

        return $this;
    }

    public function getUserEntity(): ?User
    {
        return $this->userEntity;
    }

    public function setUserEntity(?User $userEntity): static
    {
        $this->userEntity = $userEntity;

        return $this;
    }

    /**
     * @return Collection<int, StorageBox>
     */
    public function getStorages(): Collection
    {
        return $this->storages;
    }

    public function addStorage(StorageBox $storage): static
    {
        if (!$this->storages->contains($storage)) {
            $this->storages->add($storage);
            $storage->setHouse($this);
        }

        return $this;
    }

    public function removeStorage(StorageBox $storage): static
    {
        if ($this->storages->removeElement($storage)) {
            // set the owning side to null (unless already changed)
            if ($storage->getHouse() === $this) {
                $storage->setHouse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Wall>
     */
    public function getWalls(): Collection
    {
        return $this->walls;
    }

    public function addWall(Wall $wall): static
    {
        if (!$this->walls->contains($wall)) {
            $this->walls->add($wall);
            $wall->setHouse($this);
        }

        return $this;
    }

    public function removeWall(Wall $wall): static
    {
        if ($this->walls->removeElement($wall)) {
            // set the owning side to null (unless already changed)
            if ($wall->getHouse() === $this) {
                $wall->setHouse(null);
            }
        }

        return $this;
    }
}
