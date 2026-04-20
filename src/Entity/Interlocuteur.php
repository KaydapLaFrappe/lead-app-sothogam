<?php

namespace App\Entity;

use App\Repository\InterlocuteurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterlocuteurRepository::class)]
class Interlocuteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $mail = null;

    #[ORM\Column(length: 20)]
    private ?string $portable = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    /**
     * @var Collection<int, Lead>
     */
    #[ORM\OneToMany(targetEntity: Lead::class, mappedBy: 'interlocuteur')]
    private Collection $leads;

    #[ORM\ManyToOne(inversedBy: 'interlocuteurs')]
    private ?TDepartement $departement = null;

    #[ORM\Column(length: 255)]
    private ?string $identifier = null;

    /**
     * Constructeur : Initialise la collection de leads comme une ArrayCollection vide.
     */
    public function __construct()
    {
        $this->leads = new ArrayCollection();
    }

    /**
     * Retourne l'identifiant unique (clé primaire) de l'interlocuteur.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retourne le nom d'utilisateur.
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * Définit le nom d'utilisateur.
     */
    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Retourne le rôle affecté à l'interlocuteur.
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * Définit le rôle de l'interlocuteur.
     */
    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    /**
     * Retourne le nom de famille.
     */
    public function getNom(): ?string
    {
        return $this->nom;
    }

    /**
     * Définit le nom de famille.
     */
    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    /**
     * Retourne le prénom.
     */
    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    /**
     * Définit le prénom.
     */
    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    /**
     * Retourne l'adresse email.
     */
    public function getMail(): ?string
    {
        return $this->mail;
    }

    /**
     * Définit l'adresse email.
     */
    public function setMail(string $mail): static
    {
        $this->mail = $mail;
        return $this;
    }

    /**
     * Retourne le numéro de téléphone portable.
     */
    public function getPortable(): ?string
    {
        return $this->portable;
    }

    /**
     * Définit le numéro de téléphone portable.
     */
    public function setPortable(string $portable): static
    {
        $this->portable = $portable;
        return $this;
    }

    /**
     * Retourne le statut de l'interlocuteur.
     */
    public function getStatut(): ?string
    {
        return $this->statut;
    }

    /**
     * Définit le statut de l'interlocuteur.
     */
    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    /**
     * Retourne la collection de tous les leads associés à cet interlocuteur.
     * @return Collection<int, Lead>
     */
    public function getLeads(): Collection
    {
        return $this->leads;
    }

    /**
     * Ajoute un lead à l'interlocuteur et définit l'interlocuteur côté Lead (côté propriétaire).
     */
    public function addLead(Lead $lead): static
    {
        if (!$this->leads->contains($lead)) {
            $this->leads->add($lead);
            $lead->setInterlocuteur($this);
        }
        return $this;
    }

    /**
     * Supprime un lead de l'interlocuteur et rompt le lien côté Lead.
     */
    public function removeLead(Lead $lead): static
    {
        if ($this->leads->removeElement($lead)) {
            // Définit le côté propriétaire à null si le lien pointait encore vers cet objet
            if ($lead->getInterlocuteur() === $this) {
                $lead->setInterlocuteur(null);
            }
        }
        return $this;
    }

    /**
     * Retourne l'entité département associée.
     */
    public function getDepartement(): ?TDepartement
    {
        return $this->departement;
    }

    /**
     * Définit le département de l'interlocuteur.
     */
    public function setDepartement(?TDepartement $departement): static
    {
        $this->departement = $departement;
        return $this;
    }

    /**
     * Retourne l'identifiant 
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * Définit l'identifiant métier.
     */
    public function setIdentifier(string $identifier): static
    {
        $this->identifier = $identifier;
        return $this;
    }
}