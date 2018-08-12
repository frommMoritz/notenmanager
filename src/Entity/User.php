<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;



/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity("email")
 */
class User implements UserInterface, \Serializable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25)
     * @Assert\NotBlank()
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=64)
     * @Assert\NotBlank()
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=190, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email(
     *     message = "Die Email '{{ value }}' ist nicht valide",
     *     checkMX = false
     * )
     */
    private $email;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    private $roles;


    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $markRange;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\SchoolYear", mappedBy="user")
     */
    private $schoolYears;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $changed_at;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Template", mappedBy="creator")
     */
    private $templates;

    public function __construct() {
        $this->isActive = true;
        $this->roles = 'ROLE_USER';
        $this->schoolYears = new ArrayCollection();
        $this->created_at = new \Datetime();
        $this->templates = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function eraseCredentials()
    {
    }

    public function getSalt() {
        return null;
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt,
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt
        ) = unserialize($serialized, array('allowed_classes' => false));
    }

    public function getRoles()
    {
        return explode('|', $this->roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = implode('|', $roles);

        return $this;
    }

    public function getMarkRange(): ?array
    {

        $markRange = unserialize($this->markRange);
        if (!is_array($markRange)) {
            $markRange = [];
        }
        $markRange = array_merge(['best' => 1,
        'worst' => 6,
        'round' => 2], $markRange);
        return $markRange;
    }

    public function setMarkRange(?array $markRange): self
    {
        $this->markRange = serialize($markRange);

        return $this;
    }

    /**
     * @return Collection|SchoolYear[]
     */
    public function getSchoolYears(): Collection
    {
        return $this->schoolYears;
    }

    public function addSchoolYear(SchoolYear $schoolYear): self
    {
        if (!$this->schoolYears->contains($schoolYear)) {
            $this->schoolYears[] = $schoolYear;
            $schoolYear->setUser($this);
        }

        return $this;
    }

    public function removeSchoolYear(SchoolYear $schoolYear): self
    {
        if ($this->schoolYears->contains($schoolYear)) {
            $this->schoolYears->removeElement($schoolYear);
            // set the owning side to null (unless already changed)
            if ($schoolYear->getUser() === $this) {
                $schoolYear->setUser(null);
            }
        }

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

    /**
     * @return Collection|Template[]
     */
    public function getTemplates(): Collection
    {
        return $this->templates;
    }

    public function addTemplate(Template $template): self
    {
        if (!$this->templates->contains($template)) {
            $this->templates[] = $template;
            $template->setCreator($this);
        }

        return $this;
    }

    public function removeTemplate(Template $template): self
    {
        if ($this->templates->contains($template)) {
            $this->templates->removeElement($template);
            // set the owning side to null (unless already changed)
            if ($template->getCreator() === $this) {
                $template->setCreator(null);
            }
        }

        return $this;
    }
}
