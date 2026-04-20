<?php

namespace App\Entity;

use App\Repository\LeadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LeadRepository::class)]
class Lead
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $interlocuteur_id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateRelance = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = "0";

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(length: 255)]
    private ?string $source = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Operation $operation = null;

    #[ORM\Column]
    private ?int $categorie_demandeur = null;

    #[ORM\Column(length: 255)]
    private ?string $nom_societe = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse_ligne1 = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse_CP = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse_ville = null;

    #[ORM\Column(length: 255, nullable :true)]
    private ?string $activite_demandeur = null;

    #[ORM\Column(length: 255)]
    private ?string $mail = null;

    #[ORM\Column(length: 255)]
    private ?string $tel = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $date_reponse = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $orientation_client = null;

    #[ORM\Column(length: 255)]
    private ?string $collection_category = null;

    #[ORM\Column(length: 255)]
    private ?string $numero_genere = null;

    #[ORM\Column]
    private ?int $envoye_se = null;

    #[ORM\Column]
    private ?int $id_batiproduit = null;

    #[ORM\Column(length: 255)]
    private ?string $departement = null;

    /**
     * Constructeur de l'entité Lead
     * Initialise le statut par défaut à 'Non Traité'
     */
    public function __construct()
    {
        $this->statut = '0';
    }

    /**
     * Récupère l'identifiant unique du lead
     * @return int|null L'ID du lead
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Récupère l'identifiant de l'interlocuteur assigné au lead
     * @return int|null L'ID de l'interlocuteur
     */
    public function getInterlocuteurId(): ?int
    {
        return $this->interlocuteur_id;
    }

    /**
     * Définit l'identifiant de l'interlocuteur assigné au lead
     * @param int|null $interlocuteur_id L'ID de l'interlocuteur
     * @return static L'objet Lead pour chaînage
     */
    public function setInterlocuteurId(?int $interlocuteur_id): static
    {
        $this->interlocuteur_id = $interlocuteur_id;
        return $this;
    }

    /**
     * Récupère l'objet Interlocuteur complet (non implémenté, retourne toujours null)
     * @return Interlocuteur|null L'objet Interlocuteur ou null
     */
    public function getInterlocuteur(): ?Interlocuteur
    {
        return null;
    }

    /**
     * Définit l'interlocuteur en extrayant son ID
     * @param Interlocuteur|null $interlocuteur L'objet Interlocuteur
     * @return static L'objet Lead pour chaînage
     */
    public function setInterlocuteur(?Interlocuteur $interlocuteur): static
    {
        $this->interlocuteur_id = $interlocuteur?->getId();
        return $this;
    }

    /**
     * Récupère la date de création du lead
     * @return \DateTime|null La date de création
     */
    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    /**
     * Définit la date de création du lead
     * @param \DateTime $dateCreation La date de création
     * @return static L'objet Lead pour chaînage
     */
    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    /**
     * Récupère la date de relance du lead
     * @return \DateTime|null La date de relance
     */
    public function getDateRelance(): ?\DateTime
    {
        return $this->dateRelance;
    }

    /**
     * Définit la date de relance du lead
     * @param \DateTime|null $dateRelance La date de relance
     * @return static L'objet Lead pour chaînage
     */
    public function setDateRelance(?\DateTime $dateRelance): static
    {
        $this->dateRelance = $dateRelance;
        return $this;
    }

    /**
     * Récupère le statut du lead (Non Traité, Orienté, Fermé, etc.)
     * @return string|null Le statut du lead
     */
    public function getStatut(): ?string
    {
        return $this->statut;
    }

    /**
     * Définit le statut du lead
     * @param string $statut Le nouveau statut
     * @return static L'objet Lead pour chaînage
     */
    public function setStatut(string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    /**
     * Récupère le message du lead (demande du client)
     * @return string|null Le contenu du message
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Définit le message du lead
     * @param string $message Le contenu du message
     * @return static L'objet Lead pour chaînage
     */
    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Récupère la source du lead (Sothoferm, Lumis, Bati, etc.)
     * @return string|null La source du lead
     */
    public function getSource(): ?string
    {
        return $this->source;
    }

    /**
     * Définit la source du lead
     * @param string $source La source du lead
     * @return static L'objet Lead pour chaînage
     */
    public function setSource(string $source): static
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Récupère l'opération liée au lead
     * @return Operation|null L'objet Operation
     */
    public function getOperation(): ?Operation
    {
        return $this->operation;
    }

    /**
     * Définit l'opération liée au lead
     * @param Operation|null $operation L'objet Operation
     * @return static L'objet Lead pour chaînage
     */
    public function setOperation(?Operation $operation): static
    {
        $this->operation = $operation;
        return $this;
    }

    /**
     * Récupère la catégorie du demandeur (0 = Professionnel, 1 = Particulier)
     * @return int|null La catégorie du demandeur
     */
    public function getCategorieDemandeur(): ?int
    {
        return $this->categorie_demandeur;
    }

    /**
     * Définit la catégorie du demandeur
     * @param int $categorie_demandeur La catégorie (0 ou 1)
     * @return static L'objet Lead pour chaînage
     */
    public function setCategorieDemandeur(int $categorie_demandeur): static
    {
        $this->categorie_demandeur = $categorie_demandeur;
        return $this;
    }

    /**
     * Récupère le nom de la société du demandeur
     * @return string|null Le nom de la société
     */
    public function getNomSociete(): ?string
    {
        return $this->nom_societe;
    }

    /**
     * Définit le nom de la société du demandeur
     * @param string $nom_societe Le nom de la société
     * @return static L'objet Lead pour chaînage
     */
    public function setNomSociete(string $nom_societe): static
    {
        $this->nom_societe = $nom_societe;
        return $this;
    }

    /**
     * Récupère le nom du demandeur
     * @return string|null Le nom
     */
    public function getNom(): ?string
    {
        return $this->nom;
    }

    /**
     * Définit le nom du demandeur
     * @param string $nom Le nom
     * @return static L'objet Lead pour chaînage
     */
    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    /**
     * Récupère le prénom du demandeur
     * @return string|null Le prénom
     */
    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    /**
     * Définit le prénom du demandeur
     * @param string $prenom Le prénom
     * @return static L'objet Lead pour chaînage
     */
    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    /**
     * Récupère la première ligne de l'adresse
     * @return string|null L'adresse ligne 1
     */
    public function getAdresseLigne1(): ?string
    {
        return $this->adresse_ligne1;
    }

    /**
     * Définit la première ligne de l'adresse
     * @param string $adresse_ligne1 L'adresse ligne 1
     * @return static L'objet Lead pour chaînage
     */
    public function setAdresseLigne1(string $adresse_ligne1): static
    {
        $this->adresse_ligne1 = $adresse_ligne1;
        return $this;
    }

    /**
     * Récupère le code postal
     * @return string|null Le code postal
     */
    public function getAdresseCP(): ?string
    {
        return $this->adresse_CP;
    }

    /**
     * Définit le code postal
     * @param string $adresse_CP Le code postal
     * @return static L'objet Lead pour chaînage
     */
    public function setAdresseCP(string $adresse_CP): static
    {
        $this->adresse_CP = $adresse_CP;
        return $this;
    }

    /**
     * Récupère la ville
     * @return string|null La ville
     */
    public function getAdresseVille(): ?string
    {
        return $this->adresse_ville;
    }

    /**
     * Définit la ville
     * @param string $adresse_ville La ville
     * @return static L'objet Lead pour chaînage
     */
    public function setAdresseVille(string $adresse_ville): static
    {
        $this->adresse_ville = $adresse_ville;
        return $this;
    }

    /**
     * Récupère l'activité du demandeur
     * @return string|null L'activité
     */
    public function getActiviteDemandeur(): ?string
    {
        return $this->activite_demandeur;
    }

    /**
     * Définit l'activité du demandeur
     * @param string $activite_demandeur L'activité
     * @return static L'objet Lead pour chaînage
     */
    public function setActiviteDemandeur(string $activite_demandeur): static
    {
        $this->activite_demandeur = $activite_demandeur;
        return $this;
    }

    /**
     * Récupère l'adresse email du demandeur
     * @return string|null L'email
     */
    public function getMail(): ?string
    {
        return $this->mail;
    }

    /**
     * Définit l'adresse email du demandeur
     * @param string $mail L'email
     * @return static L'objet Lead pour chaînage
     */
    public function setMail(string $mail): static
    {
        $this->mail = $mail;
        return $this;
    }

    /**
     * Récupère le numéro de téléphone du demandeur
     * @return string|null Le téléphone
     */
    public function getTel(): ?string
    {
        return $this->tel;
    }

    /**
     * Définit le numéro de téléphone du demandeur
     * @param string $tel Le téléphone
     * @return static L'objet Lead pour chaînage
     */
    public function setTel(string $tel): static
    {
        $this->tel = $tel;
        return $this;
    }

    /**
     * Récupère la date de réponse au lead
     * @return \DateTime|null La date de réponse
     */
    public function getDateReponse(): ?\DateTime
    {
        return $this->date_reponse;
    }

    /**
     * Définit la date de réponse au lead
     * @param \DateTime|null $date_reponse La date de réponse
     * @return static L'objet Lead pour chaînage
     */
    public function setDateReponse(?\DateTime $date_reponse): static
    {
        $this->date_reponse = $date_reponse;
        return $this;
    }

    /**
     * Récupère le code client vers lequel le lead a été orienté
     * @return string|null Le code client d'orientation
     */
    public function getOrientationClient(): ?string
    {
        return $this->orientation_client;
    }

    /**
     * Définit le code client vers lequel orienter le lead
     * @param string $orientation_client Le code client
     * @return static L'objet Lead pour chaînage
     */
    public function setOrientationClient(string $orientation_client): static
    {
        $this->orientation_client = $orientation_client;
        return $this;
    }

    /**
     * Récupère la catégorie de collection du lead
     * @return string|null La catégorie de collection
     */
    public function getCollectionCategory(): ?string
    {
        return $this->collection_category;
    }

    /**
     * Définit la catégorie de collection du lead
     * @param string $collection_category La catégorie
     * @return static L'objet Lead pour chaînage
     */
    public function setCollectionCategory(string $collection_category): static
    {
        $this->collection_category = $collection_category;
        return $this;
    }

    /**
     * Récupère le numéro généré automatiquement pour le lead
     * @return string|null Le numéro généré
     */
    public function getNumeroGenere(): ?string
    {
        return $this->numero_genere;
    }

    /**
     * Définit le numéro généré pour le lead
     * @param string $numero_genere Le numéro généré
     * @return static L'objet Lead pour chaînage
     */
    public function setNumeroGenere(string $numero_genere): static
    {
        $this->numero_genere = $numero_genere;
        return $this;
    }

    /**
     * Récupère le statut d'envoi au service export (0 = non envoyé, 1 = envoyé)
     * @return int|null Le statut d'envoi
     */
    public function getEnvoyeSe(): ?int
    {
        return $this->envoye_se;
    }

    /**
     * Définit le statut d'envoi au service export
     * @param int $envoye_se Le statut (0 ou 1)
     * @return static L'objet Lead pour chaînage
     */
    public function setEnvoyeSe(int $envoye_se): static
    {
        $this->envoye_se = $envoye_se;
        return $this;
    }

    /**
     * Récupère l'identifiant Batiproduit associé au lead
     * @return int|null L'ID Batiproduit
     */
    public function getIdBatiproduit(): ?int
    {
        return $this->id_batiproduit;
    }

    /**
     * Définit l'identifiant Batiproduit du lead
     * @param int $id_batiproduit L'ID Batiproduit
     * @return static L'objet Lead pour chaînage
     */
    public function setIdBatiproduit(int $id_batiproduit): static
    {
        $this->id_batiproduit = $id_batiproduit;

        return $this;
    }

    /**
     * Récupère le code département du lead
     * @return string|null Le code département
     */
    public function getDepartement(): ?string
    {
        return $this->departement;
    }

    /**
     * Définit le code département du lead
     * @param string $departement Le code département
     * @return static L'objet Lead pour chaînage
     */
    public function setDepartement(string $departement): static
    {
        $this->departement = $departement;

        return $this;
    }
}