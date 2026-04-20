<?php

namespace App\Entity;

use App\Repository\LeadArchivedRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant un Lead archivé.
 * Utilisée pour conserver une trace historique des leads après leur traitement ou suppression.
 */
#[ORM\Entity(repositoryClass: LeadArchivedRepository::class)]
class LeadArchived
{
    /**
     * Identifiant unique de l'archive en base de données (clé primaire)
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Identifiant d'origine du lead avant son archivage
     */
    #[ORM\Column]
    private ?int $id_lead = null;

    /**
     * Date à laquelle le lead original a été créé
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date_creation = null;

    /**
     * Catégorie du demandeur (ex: Particulier, Professionnel) stockée sous forme d'index
     */
    #[ORM\Column]
    private ?int $categorie_demandeur = null;

    /**
     * Code postal associé à la demande
     */
    #[ORM\Column(length: 255)]
    private ?string $CP = null;

    /**
     * Libellé ou code du département
     */
    #[ORM\Column(length: 255)]
    private ?string $departement = null;

    /**
     * Contenu textuel du message ou de la demande initiale
     */
    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    /**
     * Type d'activité déclaré par le demandeur
     */
    #[ORM\Column(length: 255)]
    private ?string $activite_demandeur = null;

    /**
     * Statut final du lead au moment de l'archivage (0: en attente, 1: traité, etc.)
     */
    #[ORM\Column]
    private ?int $statut = null;

    /**
     * Date à laquelle le lead a reçu une réponse ou a été traité
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $date_reponse = null;

    /**
     * Canal d'origine du lead (1: Sothoferm, 2: Lumis, 3: Bati)
     */
    #[ORM\Column]
    private ?int $source = null;

    /**
     * Identifiant du commercial (interlocuteur) qui était en charge du lead
     */
    #[ORM\Column]
    private ?int $interlocuteur_id = null;

    /**
     * Information sur l'orientation client finale
     */
    #[ORM\Column(length: 255)]
    private ?string $orientation_client = null;

    /**
     * Catégorie de collection de produits concernée par la demande
     */
    #[ORM\Column(length: 255)]
    private ?string $collection_category = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdLead(): ?int
    {
        return $this->id_lead;
    }

    public function setIdLead(int $id_lead): static
    {
        $this->id_lead = $id_lead;
        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTime $date_creation): static
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getCategorieDemandeur(): ?int
    {
        return $this->categorie_demandeur;
    }

    public function setCategorieDemandeur(int $categorie_demandeur): static
    {
        $this->categorie_demandeur = $categorie_demandeur;
        return $this;
    }

    public function getCP(): ?string
    {
        return $this->CP;
    }

    public function setCP(string $CP): static
    {
        $this->CP = $CP;
        return $this;
    }

    public function getDepartement(): ?string
    {
        return $this->departement;
    }

    public function setDepartement(string $departement): static
    {
        $this->departement = $departement;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getActiviteDemandeur(): ?string
    {
        return $this->activite_demandeur;
    }

    public function setActiviteDemandeur(string $activite_demandeur): static
    {
        $this->activite_demandeur = $activite_demandeur;
        return $this;
    }

    public function getStatut(): ?int
    {
        return $this->statut;
    }

    public function setStatut(int $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDateReponse(): ?\DateTime
    {
        return $this->date_reponse;
    }

    public function setDateReponse(?\DateTime $date_reponse): static
    {
        $this->date_reponse = $date_reponse;
        return $this;
    }

    public function getSource(): ?int
    {
        return $this->source;
    }

    public function setSource(int $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function getInterlocuteurId(): ?int
    {
        return $this->interlocuteur_id;
    }

    public function setInterlocuteurId(int $interlocuteur_id): static
    {
        $this->interlocuteur_id = $interlocuteur_id;
        return $this;
    }

    public function getOrientationClient(): ?string
    {
        return $this->orientation_client;
    }

    public function setOrientationClient(string $orientation_client): static
    {
        $this->orientation_client = $orientation_client;
        return $this;
    }

    public function getCollectionCategory(): ?string
    {
        return $this->collection_category;
    }

    public function setCollectionCategory(string $collection_category): static
    {
        $this->collection_category = $collection_category;
        return $this;
    }
}