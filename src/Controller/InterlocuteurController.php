<?php

namespace App\Controller;

use App\Entity\Interlocuteur;
use App\Form\InterlocuteurType;
use App\Repository\InterlocuteurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur responsable de la gestion des interlocuteurs.
 * * Ce contrôleur permet d'effectuer les opérations CRUD (Création, Lecture, Mise à jour, Suppression)
 * sur les entités Interlocuteur. Il interagit avec le repository dédié et l'EntityManager
 * pour la persistance des données.
 */
#[Route('/interlocuteur')]
final class InterlocuteurController extends AbstractController
{
    /**
     * Affiche la liste complète des interlocuteurs enregistrés.
     *
     * Cette fonction interroge la base de données pour récupérer l'intégralité des 
     * fiches interlocuteurs. Elle transmet ensuite cette collection à la vue Twig
     * pour un affichage sous forme de tableau ou de liste.
     *
     * @param InterlocuteurRepository $interlocuteurRepository Le repository pour accéder aux données des interlocuteurs.
     *
     * @return Response La réponse HTTP contenant le rendu Twig de la liste des interlocuteurs.
     */
    #[Route(name: 'app_interlocuteur_index', methods: ['GET'])]
    public function index(InterlocuteurRepository $interlocuteurRepository): Response
    {
        return $this->render('interlocuteur/index.html.twig', [
            'interlocuteurs' => $interlocuteurRepository->findAll(),
        ]);
    }

    /**
     * Gère la création d'un nouvel interlocuteur.
     *
     * Cette fonction initialise une nouvelle instance de l'entité Interlocuteur et génère
     * le formulaire associé. Elle traite la soumission du formulaire (POST) :
     * - Vérifie la validité des données saisies.
     * - Persiste le nouvel objet en base de données.
     * - Redirige vers la liste des interlocuteurs en cas de succès.
     * Sinon, elle affiche le formulaire de création.
     *
     * @param Request $request L'objet requête contenant les données du formulaire.
     * @param EntityManagerInterface $entityManager L'interface de gestion des entités pour la sauvegarde.
     *
     * @return Response La réponse HTTP avec le formulaire ou la redirection après succès.
     */
    #[Route('/new', name: 'app_interlocuteur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $interlocuteur = new Interlocuteur();
        $form = $this->createForm(InterlocuteurType::class, $interlocuteur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($interlocuteur);
            $entityManager->flush();

            return $this->redirectToRoute('app_interlocuteur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('interlocuteur/new.html.twig', [
            'interlocuteur' => $interlocuteur,
            'form' => $form,
        ]);
    }

    /**
     * Affiche les détails d'un interlocuteur spécifique.
     *
     * Grâce au ParamConverter de Symfony, l'identifiant passé dans l'URL est 
     * automatiquement converti en une instance de l'entité Interlocuteur.
     * La fonction renvoie la vue de consultation détaillée.
     *
     * @param Interlocuteur $interlocuteur L'instance de l'interlocuteur récupérée via l'ID.
     *
     * @return Response La réponse HTTP avec le rendu de la fiche détaillée.
     */
    #[Route('/{id}', name: 'app_interlocuteur_show', methods: ['GET'])]
    public function show(Interlocuteur $interlocuteur): Response
    {
        return $this->render('interlocuteur/show.html.twig', [
            'interlocuteur' => $interlocuteur,
        ]);
    }

    /**
     * Gère la modification d'un interlocuteur existant.
     *
     * Cette fonction permet de mettre à jour les informations d'un interlocuteur.
     * Elle pré-remplit le formulaire avec les données actuelles de l'entité.
     * En cas de soumission valide, les modifications sont synchronisées avec la 
     * base de données via un "flush".
     *
     * @param Request $request L'objet requête contenant les modifications.
     * @param Interlocuteur $interlocuteur L'instance de l'interlocuteur à modifier.
     * @param EntityManagerInterface $entityManager L'interface pour enregistrer les modifications.
     *
     * @return Response La réponse HTTP avec le formulaire d'édition ou la redirection.
     */
    #[Route('/{id}/edit', name: 'app_interlocuteur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Interlocuteur $interlocuteur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(InterlocuteurType::class, $interlocuteur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_interlocuteur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('interlocuteur/edit.html.twig', [
            'interlocuteur' => $interlocuteur,
            'form' => $form,
        ]);
    }

    /**
     * Supprime un interlocuteur de la base de données.
     *
     * Cette fonction traite la suppression d'un enregistrement. Par mesure de sécurité,
     * elle vérifie la validité du jeton CSRF envoyé pour prévenir les attaques 
     * de type Cross-Site Request Forgery. Si le jeton est valide, l'entité est 
     * supprimée et les changements sont confirmés en base de données.
     *
     * @param Request $request L'objet requête contenant le jeton CSRF.
     * @param Interlocuteur $interlocuteur L'instance de l'interlocuteur à supprimer.
     * @param EntityManagerInterface $entityManager L'interface pour effectuer la suppression.
     *
     * @return Response Une redirection vers la liste des interlocuteurs.
     */
    #[Route('/{id}', name: 'app_interlocuteur_delete', methods: ['POST'])]
    public function delete(Request $request, Interlocuteur $interlocuteur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$interlocuteur->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($interlocuteur);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_interlocuteur_index', [], Response::HTTP_SEE_OTHER);
    }
}