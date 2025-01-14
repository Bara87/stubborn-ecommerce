<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\SweatshirtRepository;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;



#[ORM\Entity(repositoryClass: SweatshirtRepository::class)]
class Sweatshirt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Assert\NotNull(message: 'Le prix ne peut pas être vide')]
    #[Assert\Type(type: 'float', message: 'Le prix doit être un nombre')]
    #[Assert\Positive(message: 'Le prix doit être supérieur à 0')]
    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(nullable: true)]
    private ?array $sizes = null;

    #[ORM\Column]
    private ?bool $featured = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;

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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getSizes(): ?array
    {
        return $this->sizes;
    }

    public function setSizes(?array $sizes): static
    {
        $this->sizes = $sizes;

        return $this;
    }

    public function isFeatured(): ?bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): static
    {
        $this->featured = $featured;

        return $this;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath;

        return $this;
    }
}
