<?php

namespace App\Entity;

use App\Repository\OperationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OperationRepository::class)]
class Operation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_debut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_fin = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?TDepartement $tdepartement = null;

    /**
     * Constructeur de l'entité.
     */
    public function __construct()
    {
        
    }

    /**
     * Retourne l'identifiant unique de l'opération.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le nom de l'opération commerciale ou technique.
     */
    public function getNom(): ?string
    {
        return $this->nom;
    }

    /**
     * Définit le nom de l'opération.
     */
    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Retourne la date de début de l'opération.
     */
    public function getDateDebut(): ?\DateTime
    {
        return $this->date_debut;
    }

    /**
     * Définit la date de début de l'opération.
     */
    public function setDateDebut(\DateTime $date_debut): static
    {
        $this->date_debut = $date_debut;

        return $this;
    }

    /**
     * Retourne la date de fin prévue pour l'opération.
     */
    public function getDateFin(): ?\DateTime
    {
        return $this->date_fin;
    }

    /**
     * Définit la date de fin de l'opération.
     */
    public function setDateFin(\DateTime $date_fin): static
    {
        $this->date_fin = $date_fin;

        return $this;
    }

    /**
     * Retourne le département associé à cette opération.
     */
    public function getTdepartement(): ?TDepartement
    {
        return $this->tdepartement;
    }

    /**
     * Définit le département (TDepartement) rattaché à l'opération.
     */
    public function setTdepartement(?TDepartement $tdepartement): static
    {
        $this->tdepartement = $tdepartement;

        return $this;
    }
}