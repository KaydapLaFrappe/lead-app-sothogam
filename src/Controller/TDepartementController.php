<?php

namespace App\Controller;

use App\Entity\TDepartement;
use App\Form\TDepartementType;
use App\Repository\TDepartementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur dédié à la gestion des secteurs géographiques (Départements).
 *
 * Ce contrôleur permet de gérer la table de référence des départements. 
 * Ces données sont cruciales car elles servent de pivot pour l'affectation 
 * des interlocuteurs et le ciblage des opérations commerciales.
 */
#[Route('/t/departement')]
final class TDepartementController extends AbstractController
{
    /**
     * Affiche la liste des départements configurés dans le système.
     *
     * Cette fonction récupère l'ensemble des départements via le repository. 
     * Elle permet aux administrateurs de visualiser les codes postaux 
     * enregistrés et les relations existantes avec les autres entités du système.
     *
     * @param TDepartementRepository $tDepartementRepository Le repository pour accéder aux entités TDepartement.
     *
     * @return Response La réponse HTTP contenant le rendu de l'index des départements.
     */
    #[Route(name: 'app_t_departement_index', methods: ['GET'])]
    public function index(TDepartementRepository $tDepartementRepository): Response
    {
        return $this->render('t_departement/index.html.twig', [
            't_departements' => $tDepartementRepository->findAll(),
        ]);
    }

    /**
     * Gère la création d'un nouveau secteur départemental.
     *
     * Cette fonction permet d'ajouter un nouveau code postal ou département 
     * à la base de données. Elle traite le formulaire de saisie :
     * - Initialisation d'une nouvelle entité TDepartement.
     * - Analyse de la requête et validation des contraintes d'intégrité.
     * - Enregistrement définitif via l'EntityManager.
     *
     * @param Request $request L'objet requête contenant les données soumises.
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités pour la persistance.
     *
     * @return Response La vue du formulaire ou une redirection vers l'index après création.
     */
    #[Route('/new', name: 'app_t_departement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tDepartement = new TDepartement();
        $form = $this->createForm(TDepartementType::class, $tDepartement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tDepartement);
            $entityManager->flush();

            return $this->redirectToRoute('app_t_departement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('t_departement/new.html.twig', [
            't_departement' => $tDepartement,
            'form' => $form,
        ]);
    }

    /**
     * Affiche la fiche détaillée d'un département.
     *
     * Cette fonction permet de consulter les informations spécifiques d'un 
     * département (ID, Code Postal) ainsi que, potentiellement, la liste 
     * des entités liées (Interlocuteurs rattachés, Opérations en cours).
     *
     * @param TDepartement $tDepartement L'instance du département sélectionné.
     *
     * @return Response La réponse HTTP avec le rendu détaillé.
     */
    #[Route('/{id}', name: 'app_t_departement_show', methods: ['GET'])]
    public function show(TDepartement $tDepartement): Response
    {
        return $this->render('t_departement/show.html.twig', [
            't_departement' => $tDepartement,
        ]);
    }

    /**
     * Permet la modification des attributs d'un département.
     *
     * Cette fonction gère la mise à jour des informations d'un département existant. 
     * Elle est principalement utilisée pour corriger un code postal ou mettre 
     * à jour les métadonnées associées.
     *
     * @param Request $request L'objet requête pour traiter le formulaire d'édition.
     * @param TDepartement $tDepartement L'entité département à modifier.
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités pour valider les changements.
     *
     * @return Response La vue d'édition ou une redirection après succès du flush.
     */
    #[Route('/{id}/edit', name: 'app_t_departement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TDepartement $tDepartement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TDepartementType::class, $tDepartement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_t_departement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('t_departement/edit.html.twig', [
            't_departement' => $tDepartement,
            'form' => $form,
        ]);
    }

    /**
     * Supprime un département du système de référence.
     *
     * Cette fonction exécute la suppression d'un département après une 
     * vérification stricte du jeton CSRF pour garantir l'origine de la demande.
     * Attention : La suppression peut échouer ou entraîner des cascades 
     * si des Interlocuteurs ou des Opérations sont encore liés à ce département.
     *
     * @param Request $request L'objet requête contenant le token de sécurité.
     * @param TDepartement $tDepartement Le département à supprimer.
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités.
     *
     * @return Response Une redirection vers l'index des départements.
     */
    #[Route('/{id}', name: 'app_t_departement_delete', methods: ['POST'])]
    public function delete(Request $request, TDepartement $tDepartement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tDepartement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tDepartement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_t_departement_index', [], Response::HTTP_SEE_OTHER);
    }
}