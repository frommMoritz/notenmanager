<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;


/**
 * @ORM\Entity(repositoryClass="App\Repository\SubjectRepository")
 */
class Subject
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=70)
     * @Assert\Length(
     *      min = 2,
     *      max = 50,
     *      minMessage="Der Name mus mindestens 2 Zeichen lang seien",
     *      maxMessage="Der Name darf maximal 60 Zeichen lang seien",
     * )
     * */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Mark", mappedBy="subject")
     */
    private $marks;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\SchoolYear", inversedBy="subjects")
     */
    private $schoolyear;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $changed_at;

    private $average;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_template;

    public function __construct()
    {
        $this->marks = new ArrayCollection();
        $this->created_at = new \Datetime();
        $this->is_template = false;
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

    /**
     * @return Collection|Mark[]
     */
    public function getMarks(): Collection
    {
        return $this->marks;
    }

    public function addMark(Mark $mark): self
    {
        if (!$this->marks->contains($mark)) {
            $this->marks[] = $mark;
            $mark->setSubject($this);
        }

        return $this;
    }

    public function removeMark(Mark $mark): self
    {
        if ($this->marks->contains($mark)) {
            $this->marks->removeElement($mark);
            // set the owning side to null (unless already changed)
            if ($mark->getSubject() === $this) {
                $mark->setSubject(null);
            }
        }

        return $this;
    }

    public function getSchoolyear(): ?SchoolYear
    {
        return $this->schoolyear;
    }

    public function setSchoolyear(?SchoolYear $schoolyear): self
    {
        $this->schoolyear = $schoolyear;

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

    public function getAverage() {
        return $this->average;
    }

    public function setAverage($average) {
        $this->average = $average;
    }

    public function getIsTemplate(): ?bool
    {
        return $this->is_template;
    }

    public function setIsTemplate(bool $is_template): self
    {
        $this->is_template = $is_template;

        return $this;
    }
}