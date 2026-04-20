<?php

namespace App\Controller;

use App\Entity\Operation;
use App\Form\OperationType;
use App\Repository\OperationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur gérant le cycle de vie des opérations.
 *
 * Ce contrôleur permet d'administrer les opérations commerciales ou techniques.
 * Il assure la liaison entre les entités Operation, les formulaires de saisie
 * et la base de données via Doctrine.
 */
#[Route('/operation')]
final class OperationController extends AbstractController
{
    /**
     * Affiche la liste globale des opérations enregistrées.
     *
     * Cette fonction récupère l'ensemble des instances d'opérations présentes en base 
     * via le Repository. Elle permet de visualiser rapidement :
     * - Le nom de l'opération
     * - La période de validité (dates de début et de fin)
     * - Le département rattaché
     *
     * @param OperationRepository $operationRepository Le repository dédié aux opérations.
     *
     * @return Response La réponse HTTP contenant le rendu de la liste.
     */
    #[Route(name: 'app_operation_index', methods: ['GET'])]
    public function index(OperationRepository $operationRepository): Response
    {
        return $this->render('operation/index.html.twig', [
            'operations' => $operationRepository->findAll(),
        ]);
    }

    /**
     * Gère la création et l'enregistrement d'une nouvelle opération.
     *
     * Cette fonction instancie une nouvelle entité Operation et génère le formulaire 
     * correspondant. Elle traite la réception des données :
     * - Hydratation de l'entité avec les données saisies
     * - Validation de la conformité du formulaire
     * - Persistance des données en base via l'EntityManager
     *
     * @param Request $request L'objet requête contenant les paramètres POST.
     * @param EntityManagerInterface $entityManager L'interface de gestion de la persistance.
     *
     * @return Response La vue du formulaire ou une redirection vers l'index en cas de succès.
     */
    #[Route('/new', name: 'app_operation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $operation = new Operation();
        $form = $this->createForm(OperationType::class, $operation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($operation);
            $entityManager->flush();

            return $this->redirectToRoute('app_operation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('operation/new.html.twig', [
            'operation' => $operation,
            'form' => $form,
        ]);
    }

    /**
     * Affiche les informations détaillées d'une opération sélectionnée.
     *
     * Utilise l'identifiant (ID) passé dans l'URL pour injecter directement 
     * l'instance de l'entité Operation. Cette vue est utilisée pour consulter 
     * les attributs spécifiques sans modification.
     *
     * @param Operation $operation L'instance de l'opération injectée automatiquement.
     *
     * @return Response La réponse HTTP avec le rendu des détails.
     */
    #[Route('/{id}', name: 'app_operation_show', methods: ['GET'])]
    public function show(Operation $operation): Response
    {
        return $this->render('operation/show.html.twig', [
            'operation' => $operation,
        ]);
    }

    /**
     * Permet la modification d'une opération existante.
     *
     * Cette fonction récupère l'entité correspondante et présente un formulaire 
     * pré-rempli. Lors de la validation :
     * - Les modifications sont détectées par l'Unit of Work de Doctrine
     * - La base de données est mise à jour lors du flush
     *
     * @param Request $request L'objet requête pour le traitement du formulaire.
     * @param Operation $operation L'opération à modifier.
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités.
     *
     * @return Response La vue d'édition ou une redirection après mise à jour.
     */
    #[Route('/{id}/edit', name: 'app_operation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Operation $operation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OperationType::class, $operation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_operation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('operation/edit.html.twig', [
            'operation' => $operation,
            'form' => $form,
        ]);
    }

    /**
     * Supprime une opération de la base de données.
     *
     * Cette fonction sécurise la suppression en vérifiant l'existence et la 
     * validité d'un jeton CSRF. Si le jeton concorde avec l'ID de l'opération :
     * - L'entité est marquée pour suppression
     * - L'ordre DELETE est exécuté en base de données
     *
     * @param Request $request L'objet requête contenant le jeton de sécurité.
     * @param Operation $operation L'opération à supprimer.
     * @param EntityManagerInterface $entityManager Le gestionnaire d'entités.
     *
     * @return Response Une redirection vers la liste des opérations.
     */
    #[Route('/{id}', name: 'app_operation_delete', methods: ['POST'])]
    public function delete(Request $request, Operation $operation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$operation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($operation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_operation_index', [], Response::HTTP_SEE_OTHER);
    }
}