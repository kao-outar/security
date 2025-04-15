<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
class Product
{
    #[ORM\Id]
    #[ORM\Column(name: "shopify_id", type: "string", length: 255)]
    private string $shopifyId;  // Utilisation de shopify_id comme clé primaire

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'products')]
    #[ORM\JoinColumn(name: 'created_by', referencedColumnName: 'id', nullable: false)]
    private User $createdBy;

    #[ORM\Column]
    private int $salesCount = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $price = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    public function getShopifyId(): string
    {
        return $this->shopifyId;
    }

    public function setShopifyId(string $shopifyId): static
    {
        $this->shopifyId = $shopifyId;

        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getSalesCount(): int
    {
        return $this->salesCount;
    }

    public function setSalesCount(int $salesCount): static
    {
        $this->salesCount = $salesCount;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }
}
