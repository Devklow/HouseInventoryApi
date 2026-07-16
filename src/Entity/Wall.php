<?php

namespace App\Entity;

use App\Repository\WallRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WallRepository::class)]
class Wall
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $posX = null;

    #[ORM\Column]
    private ?float $posY = null;

    #[ORM\Column]
    private ?float $posX2 = null;

    #[ORM\Column]
    private ?float $posY2 = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\ManyToOne(inversedBy: 'walls')]
    #[ORM\JoinColumn(nullable: false)]
    private ?House $house = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosX(): ?float
    {
        return $this->posX;
    }

    public function setPosX(float $posX): static
    {
        $this->posX = $posX;

        return $this;
    }

    public function getPosY(): ?float
    {
        return $this->posY;
    }

    public function setPosY(float $posY): static
    {
        $this->posY = $posY;

        return $this;
    }

    public function getPosX2(): ?float
    {
        return $this->posX2;
    }

    public function setPosX2(float $posX2): static
    {
        $this->posX2 = $posX2;

        return $this;
    }

    public function getPosY2(): ?float
    {
        return $this->posY2;
    }

    public function setPosY2(float $posY2): static
    {
        $this->posY2 = $posY2;

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

    public function getHouse(): ?House
    {
        return $this->house;
    }

    public function setHouse(?House $house): static
    {
        $this->house = $house;

        return $this;
    }
}
