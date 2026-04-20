<?php

namespace App\Entity;

use App\Repository\TDepartementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TDepartementRepository::class)]
class TDepartement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 5)]
    private ?string $code_postal = null;

    /**
     * @var Collection<int, Operation>
     */
    #[ORM\OneToMany(targetEntity: Operation::class, mappedBy: 'tdepartement')]
    private Collection $operations;

    /**
     * @var Collection<int, Interlocuteur>
     */
    #[ORM\OneToMany(targetEntity: Interlocuteur::class, mappedBy: 'departement')]
    private Collection $interlocuteurs;

    /**
     * Constructeur : Initialise les collections d'opérations et d'interlocuteurs.
     */
    public function __construct()
    {
        $this->operations = new ArrayCollection();
        $this->interlocuteurs = new ArrayCollection();
    }

    /**
     * Retourne l'identifiant unique du département.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le code postal associé au département.
     */
    public function getCodePostal(): ?string
    {
        return $this->code_postal;
    }

    /**
     * Définit le code postal du département.
     */
    public function setCodePostal(string $code_postal): static
    {
        $this->code_postal = $code_postal;

        return $this;
    }

    /**
     * Retourne la liste des opérations liées à ce département.
     * @return Collection<int, Operation>
     */
    public function getOperations(): Collection
    {
        return $this->operations;
    }

    /**
     * Ajoute une opération à la collection et définit ce département comme parent.
     */
    public function addOperation(Operation $operation): static
    {
        if (!$this->operations->contains($operation)) {
            $this->operations->add($operation);
            $operation->setTdepartement($this);
        }

        return $this;
    }

    /**
     * Supprime une opération de la collection et rompt le lien de parenté.
     */
    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // Définit le côté propriétaire à null si le lien pointait vers cet objet
            if ($operation->getTdepartement() === $this) {
                $operation->setTdepartement(null);
            }
        }

        return $this;
    }

    /**
     * Retourne la liste des interlocuteurs rattachés à ce département.
     * @return Collection<int, Interlocuteur>
     */
    public function getInterlocuteurs(): Collection
    {
        return $this->interlocuteurs;
    }

    /**
     * Ajoute un interlocuteur à la collection et définit ce département comme parent.
     */
    public function addInterlocuteur(Interlocuteur $interlocuteur): static
    {
        if (!$this->interlocuteurs->contains($interlocuteur)) {
            $this->interlocuteurs->add($interlocuteur);
            $interlocuteur->setDepartement($this);
        }

        return $this;
    }

    /**
     * Supprime un interlocuteur de la collection et rompt le lien de parenté.
     */
    public function removeInterlocuteur(Interlocuteur $interlocuteur): static
    {
        if ($this->interlocuteurs->removeElement($interlocuteur)) {
            // Définit le côté propriétaire à null si le lien pointait vers cet objet
            if ($interlocuteur->getDepartement() === $this) {
                $interlocuteur->setDepartement(null);
            }
        }

        return $this;
    }
}