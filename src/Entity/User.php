<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $pass = null;

    /**
     * @var Collection<int, UserTheme>
     */
    #[ORM\OneToMany(targetEntity: UserTheme::class, mappedBy: 'userEntity')]
    private Collection $themes;

    /**
     * @var Collection<int, House>
     */
    #[ORM\OneToMany(targetEntity: House::class, mappedBy: 'userEntity', orphanRemoval: true)]
    private Collection $houses;

    public function __construct()
    {
        $this->themes = new ArrayCollection();
        $this->houses = new ArrayCollection();
    }

        public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPass(): ?string
    {
        return $this->pass;
    }

    public function setPass(string $pass): static
    {
        $this->pass = $pass;

        return $this;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /**
     * @return Collection<int, UserTheme>
     */
    public function getThemes(): Collection
    {
        return $this->themes;
    }

    public function addTheme(UserTheme $theme): static
    {
        if (!$this->themes->contains($theme)) {
            $this->themes->add($theme);
            $theme->setNo($this);
        }

        return $this;
    }

    public function removeTheme(UserTheme $theme): static
    {
        if ($this->themes->removeElement($theme)) {
            // set the owning side to null (unless already changed)
            if ($theme->getNo() === $this) {
                $theme->setNo(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, House>
     */
    public function getHouses(): Collection
    {
        return $this->houses;
    }

    public function addHouse(House $house): static
    {
        if (!$this->houses->contains($house)) {
            $this->houses->add($house);
            $house->setUserEntity($this);
        }

        return $this;
    }

    public function removeHouse(House $house): static
    {
        if ($this->houses->removeElement($house)) {
            // set the owning side to null (unless already changed)
            if ($house->getUserEntity() === $this) {
                $house->setUserEntity(null);
            }
        }

        return $this;
    }


    public function getPassword(): ?string
    {
        return $this->pass;
    }
}
