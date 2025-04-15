<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\Table(name: "roles")] // 👈 FORCER le nom correct de la table Supabase ici
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: "nom", length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $can_post_login = null;

    #[ORM\Column]
    private ?bool $can_get_my_user = null;

    #[ORM\Column]
    private ?bool $can_get_users = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $can_post_products = null;

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

    public function isCanPostLogin(): ?bool
    {
        return $this->can_post_login;
    }

    public function setCanPostLogin(bool $can_post_login): static
    {
        $this->can_post_login = $can_post_login;
        return $this;
    }

    public function isCanGetMyUser(): ?bool
    {
        return $this->can_get_my_user;
    }

    public function setCanGetMyUser(bool $can_get_my_user): static
    {
        $this->can_get_my_user = $can_get_my_user;
        return $this;
    }

    public function isCanGetUsers(): ?bool
    {
        return $this->can_get_users;
    }

    public function setCanGetUsers(bool $can_get_users): static
    {
        $this->can_get_users = $can_get_users;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->name;
    }

    public function getCanPostProducts(): ?bool
    {
        return $this->can_post_products;
    }

    public function setCanPostProducts(?bool $can_post_products): self
    {
        $this->can_post_products = $can_post_products;
        return $this;
    }
}
