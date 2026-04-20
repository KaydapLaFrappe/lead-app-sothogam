<?php

namespace App\Controller;

use App\Repository\LeadRepository;
use App\Controller\ScriptController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Tool\EmailFunctions;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Contrôleur gérant les processus de relance des leads non traités.
 */
class RelanceController extends AbstractController
{
    private ScriptController $scriptController;

    /**
     * Constructeur du contrôleur
     * @param ScriptController $scriptController Injection du contrôleur de scripts pour l'accès aux données des commerciaux
     */
    public function __construct(ScriptController $scriptController)
    {
        $this->scriptController = $scriptController;
    }
    
    /**
     * Cette méthode permet d'envoyer un email de relance au commercial connecté
     * * Récupère l'identifiant de l'interlocuteur via les champs extra de l'utilisateur
     * Identifie les leads non traités depuis plus de 24 heures pour ce commercial spécifique
     * Récupère les coordonnées (nom et email) du commercial via le service externe
     * Déclenche l'envoi d'un email récapitulatif via les fonctions d'emailing
     * Gère les messages flash de succès ou d'erreur selon le résultat de l'opération
     * @param LeadRepository $leadRepository pour filtrer les leads en attente de traitement
     * @param EntityManagerInterface $em pour la gestion de la persistance des données (date de relance)
     * @return Response redirection vers la page principale avec le statut de l'envoi
    */
    #[Route('/relance/leads', name: 'app_relance_leads')]
    public function relanceLeads(LeadRepository $leadRepository, EntityManagerInterface $em): Response 
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $myIdentifier = $user->getExtraFields()['interlocuteur'];
        
        $dateLimit = new \DateTime('-24 hours');
        $leadsARelancer = $leadRepository->findLeadsNonTraitesDepuisPourCommercial($dateLimit, $myIdentifier);
        
        if (empty($leadsARelancer)) {
            $this->addFlash('info', 'Aucun de vos leads à relancer pour le moment.');
            return $this->redirectToRoute('app_main');
        }

        /* Logique de vérification de doublon de relance journalière (commentée en attente d'activation)
        $dejaRelance = false;
        foreach ($leadsARelancer as $lead) {
            if ($lead->getDateRelance() && $lead->getDateRelance()->format('Y-m-d') === date('Y-m-d')) {
                $dejaRelance = true;
                break;
            }
        }

        if ($dejaRelance) {
            $this->addFlash('info', 'Une relance a déjà été envoyée aujourd\'hui.');
            return $this->redirectToRoute('app_main');
        }
        */

        $allCommercials = $this->scriptController->getAllCommercials();
        $commercialInfo = null;
        
        foreach ($allCommercials as $commercial) {
            $code = str_pad(trim($commercial['code']), 3, '0', STR_PAD_LEFT);
            if ($code === $myIdentifier) {
                $commercialInfo = [
                    'email' => $commercial['mail'],
                    'nom' => $commercial['nom']
                ];
                break;
            }
        }

        if (!$commercialInfo || empty($commercialInfo['email'])) {
            $this->addFlash('error', 'Impossible de récupérer vos informations de contact.');
            return $this->redirectToRoute('app_main');
        }

        try {
            EmailFunctions::sendRelanceToCommercial(
                $commercialInfo['email'],
                $commercialInfo['nom'],
                $leadsARelancer
            );
            
            /* Mise à jour de la date de relance en base (commentée en attente d'activation)
            foreach ($leadsARelancer as $lead) {
                $lead->setDateRelance(new \DateTime());
                $em->persist($lead);
            }
            $em->flush();
            */
            
            $this->addFlash('success', sprintf('Email de relance envoyé pour %d lead(s)', count($leadsARelancer)));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email');
        }

        return $this->redirectToRoute('app_main');
    }
}