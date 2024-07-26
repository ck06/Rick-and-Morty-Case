<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $dimension = null;

    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Character>
     */
    #[ORM\OneToMany(mappedBy: 'originLocation', targetEntity: Character::class)]
    private Collection $originCharacters;

    /**
     * @var Collection<int, Character>
     */
    #[ORM\OneToMany(mappedBy: 'location', targetEntity: Character::class)]
    private Collection $characters;

    public function __construct()
    {
        $this->originCharacters = new ArrayCollection();
        $this->characters = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDimension(): ?string
    {
        return $this->dimension;
    }

    public function setDimension(string $dimension): static
    {
        $this->dimension = $dimension;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, Character>
     */
    public function getOriginCharacters(): Collection
    {
        return $this->originCharacters;
    }

    public function addOriginCharacter(Character $originCharacter): static
    {
        if (!$this->originCharacters->contains($originCharacter)) {
            $this->originCharacters->add($originCharacter);
            $originCharacter->setOriginLocation($this);
        }

        return $this;
    }

    public function removeOriginCharacter(Character $originCharacter): static
    {
        if ($this->originCharacters->removeElement($originCharacter)) {
            // set the owning side to null (unless already changed)
            if ($originCharacter->getOriginLocation() === $this) {
                $originCharacter->setOriginLocation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Character>
     */
    public function getCharacters(): Collection
    {
        return $this->characters;
    }

    public function addCharacter(Character $character): static
    {
        if (!$this->characters->contains($character)) {
            $this->characters->add($character);
            $character->setLocation($this);
        }

        return $this;
    }

    public function removeCharacter(Character $character): static
    {
        if ($this->characters->removeElement($character)) {
            // set the owning side to null (unless already changed)
            if ($character->getLocation() === $this) {
                $character->setLocation(null);
            }
        }

        return $this;
    }
}
