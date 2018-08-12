<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TemplateRepository")
 */
class Template
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=190)
     * @Assert\NotBlank()
     * @Assert\Length(
     *      min = 2,
     *      max = 25,
     *      minMessage = "Der Name muss mindestens {{ limit }} Zeichen lang seien",
     *      maxMessage = "Der Name darf nicht länger als {{ limit }} Zeichen lang seien"
     * )
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="templates")
     */
    private $creator;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $changed_at;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_global;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_active;

    public function __construct() {
        $this->created_at = new \Datetime();
        $this->is_global = false;
        $this->is_active = true;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getChangedAt(): ?\DateTimeInterface
    {
        return $this->changed_at;
    }

    public function setChangedAt(?\DateTimeInterface $changed_at): self
    {
        $this->changed_at = $changed_at;

        return $this;
    }

    public function getIsGlobal(): ?bool
    {
        return $this->is_global;
    }

    public function setIsGlobal(bool $is_global): self
    {
        $this->is_global = $is_global;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): self
    {
        $this->is_active = $is_active;

        return $this;
    }
}
