<?php

namespace App\Controller;

use App\Entity\Lead;
use App\Form\LeadType;
use App\Repository\LeadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\InterlocuteurRepository;
use App\Tool\EmailFunctions;
use App\Service\ActiveDirectoryService;
use Doctrine\Persistence\ManagerRegistry;
use App\Controller\ScriptController;
use App\Entity\Interlocuteur;
use App\Entity\LeadArchived;



#[Route('/lead')]
final class LeadController extends AbstractController
{
    
    private LeadRepository $leadRepository;
    private InterlocuteurRepository $interlocuteurRepository;
    private ClientRepository $client;
    private ScriptController $scriptController;
    private EntityManagerInterface $em;
       
    public function __construct(LeadRepository $leadRepository,InterlocuteurRepository $interlocuteurRepository,ScriptController $scriptController,EntityManagerInterface $em) {
        
        $this->leadRepository = $leadRepository;
        $this->interlocuteurRepository = $interlocuteurRepository;
        $this->scriptController=$scriptController;
        $this->em=$em;

    }

/**
 * Gère l'affichage de la liste des leads avec un système de filtrage par périmètre (Personnel / Équipe).
 * * - Vérification de la session utilisateur et redirection si non connecté.
 * -  Import automatique des leads depuis la source externe (Sotho).
 * - Récupération de l'identifiant commercial et des informations CDV/ATC (Chef de Vente et Assistant Commercial) via les extraFields.
 * - Gestion du périmètre d'affichage via le paramètre 'teamOrMe' :
 * - 'me'   : Filtre uniquement les leads de l'utilisateur connecté.
 * - 'team' : Filtre les leads des membres de l'équipe (exclut l'utilisateur connecté).
 * - 'all'  : Fusionne les leads de l'utilisateur et de toute son équipe.
 * - Construction de la liste des codes commerciaux (identifiers) pour la requête Repository.
 * - Récupération des leads via findLeadsByUser() selon le statut demandé (pending, ferme).
 * - Mapping entre les codes Oracle et les noms réels des commerciaux.
 * - Application d'un tri manuel sur les colonnes : Département (CP), Type ou Date de création.
 * - Envoie des données au Twig (leads, type de vue, membres équipe, statut CDV).
 * * @param Request $request Contient les paramètres de tri (sort, order) et de périmètre (teamOrMe).
 * @param ManagerRegistry $doctrine Pour la gestion des connexions à la base de données.
 * @param string $type Le statut des leads à afficher (par défaut 'pending').
 * * @return Response La vue index.html.twig avec les leads filtrés et triés.
 */

#[Route('/leads/{type}', name: 'app_lead_index', defaults: ['type' => 'pending'], methods: ['GET'])]
public function index(Request $request, string $type = 'pending'): Response 
{
    $user = $this->getUser();
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }
    
    $leadsImport = $this->scriptController->recupLeadSotho();
    
    
    $myIdentifier = $user->getExtraFields()['interlocuteur'];
    $test = $this->scriptController->getAllClientsByCommercial($myIdentifier);
    //dd($test);
    $teamMembres = $user->getExtraFields()['cdv'] ?? [];
    $isCDV = !empty($teamMembres) && is_array($teamMembres);
    $isATC = $user->getExtraFields()['atc'] ?? false;
        
    $teamOrMe = $request->query->get('teamOrMe', 'me');
    $filterCommercial = $request->query->get('filterCommercial');

    
    $teamCodes = [];
    if ($isCDV) {
        $teamCodes = array_column($teamMembres, 'CODE');
    }
    
    if ($teamOrMe === 'team' && !$isCDV && !$isATC) {
        $teamOrMe = 'me';
    }

    $filterIdentifiers = [];

    if ($isATC) {
        if ($filterCommercial && $filterCommercial !== 'all') {
            $filterIdentifiers = [$filterCommercial];
        } else {
            $allCommercials = $this->scriptController->getAllCommercials();
            $filterIdentifiers = [];
            foreach ($allCommercials as $commercial) {
                $code = str_pad(trim($commercial['code']), 3, '0', STR_PAD_LEFT);
                $filterIdentifiers[] = $code;
            }
        }
    } elseif ($teamOrMe === 'team' && is_array($teamMembres)) {
        if ($filterCommercial && $filterCommercial !== 'all') {
            $teamCodes = array_column($teamMembres, 'CODE');
            if (in_array($filterCommercial, $teamCodes) && $filterCommercial !== $myIdentifier) {
                $filterIdentifiers = [$filterCommercial];
            } else {
                $filterIdentifiers = array_column($teamMembres, 'CODE');
                $filterIdentifiers = array_filter($filterIdentifiers, fn($code) => $code !== $myIdentifier);
            }
        } else {
            $filterIdentifiers = array_column($teamMembres, 'CODE');
            $filterIdentifiers = array_filter($filterIdentifiers, fn($code) => $code !== $myIdentifier);
        }
    } elseif ($teamOrMe === 'all' && is_array($teamMembres)) {
        if ($filterCommercial && $filterCommercial !== 'all') {
            $teamCodes = array_column($teamMembres, 'CODE');
            $allCodes = array_merge($teamCodes, [$myIdentifier]);
            
            if (in_array($filterCommercial, $allCodes)) {
                $filterIdentifiers = [$filterCommercial];
            } else {
                $filterIdentifiers = $allCodes;
            }
        } else {
            $filterIdentifiers = array_column($teamMembres, 'CODE');
            if (!in_array($myIdentifier, $filterIdentifiers)) {
                $filterIdentifiers[] = $myIdentifier;
            }
        }
    } else {
        $filterIdentifiers = [$myIdentifier];
    }

    $leads = $this->leadRepository->findLeadsByUser($filterIdentifiers, $type);

    $allCommercials = $this->scriptController->getAllCommercials();
    $nomsCommerciaux = [];
    foreach ($allCommercials as $commercial) {
        $code = str_pad(trim($commercial['code']), 3, '0', STR_PAD_LEFT);
        $nomsCommerciaux[$code] = $commercial['nom'];
    }

    foreach ($leads as $lead) {
        $codeCommercial = str_pad((string)$lead->getInterlocuteurId(), 3, '0', STR_PAD_LEFT);
        $lead->commercialNom = $nomsCommerciaux[$codeCommercial] ?? 'Code : ' . $codeCommercial;
    }

    $sort = $request->query->get('sort');
    $order = $request->query->get('order', 'asc');
    
    if ($sort && in_array($sort, ['dept', 'type', 'date'])) {
        usort($leads, function($a, $b) use ($sort, $order) {
            if ($sort === 'dept') {
                $valA = (int) substr($a->getAdresseCP() ?? '', 0, 2);
                $valB = (int) substr($b->getAdresseCP() ?? '', 0, 2);
            } elseif ($sort === 'type') {
                $valA = $a->getCategorieDemandeur();
                $valB = $b->getCategorieDemandeur();
            } elseif ($sort === 'date') {
                $valA = $a->getDateCreation() ? $a->getDateCreation()->getTimestamp() : 0;
                $valB = $b->getDateCreation() ? $b->getDateCreation()->getTimestamp() : 0;
            }
            return ($order === 'asc') ? ($valA <=> $valB) : ($valB <=> $valA);
        });
    }

    $commerciauxDisponibles = [];
    if ($isATC) {
        $commerciauxDisponibles = $nomsCommerciaux;
    } elseif ($isCDV) {
        foreach ($teamMembres as $membre) {
            $commerciauxDisponibles[$membre['CODE']] = $membre['NOM'];
        }
    }

    return $this->render('lead/index.html.twig', [
        'leads'           => $leads,
        'currentType'     => $type,
        'teamOrMe'        => $teamOrMe,
        'teamMembres'     => $teamMembres,
        'isCDV'           => $isCDV,
        'isATC'           => $isATC,
        'nomsCommerciaux' => $nomsCommerciaux,
        'filterCommercial' => $filterCommercial,
        'commerciauxDisponibles' => $commerciauxDisponibles,
    ]);
}



/**
 * Cette méthode permet de transférer un lead à un commercial avec envoi de mail au commercial ciblé
 * 
 * On récupère l'identifiant du commercial sélectionné dans la vue
 * On attribue le lead à l'identifiant du commercial sélectionné
 * On attribue le statut "Non Traité" au lead
 * On récupère le mail du commercial depuis Oracle en utilisant son code PERCOMM_COD
 * On envoie un email de notification au commercial avec la méthode sendCollectMailToSupplier
 * On redirige vers la page de détail du lead avec un message de confirmation
 * @param Lead $lead le lead qu'on veut transferer
 * @param Request $request pour récupérer la donnée du formulaire
 * @param EntityManagerInterface $em La Base de donnée pour sauvegarder les changements
 */

#[Route('/{id}/transfer', name: 'app_lead_transfer', methods: ['POST'])]
public function transferLeads(Lead $lead,Request $request): Response {

    $percommCod = (int) ltrim($request->request->get('identifier', ''), '0');

    if (!$percommCod) {
        $this->addFlash('error', 'Veuillez sélectionner un commercial.');
        return $this->redirectToRoute('app_lead_show', ['id' => $lead->getId()]);
    }

    $percommCode = str_pad($percommCod, 3, '0', STR_PAD_LEFT);

    $lead->setInterlocuteurId($percommCode);
    $lead->setStatut('0');
    $this->em->flush();

    $commerciaux = $this->scriptController->getAllCommercials();
    $mailCommercial = null;

    foreach ($commerciaux as $commercial) {
        if (isset($commercial['code']) && trim($commercial['code']) === $percommCode) {
            $mailCommercial = $commercial['mail'];
            break;
        }
    }
    if ($mailCommercial) {
        EmailFunctions::sendCollectMailToSupplier("victor.moraud2004@gmail.com");
        $this->addFlash('success', "Lead transféré au commercial n°{$percommCode}.");
    } else {
        $this->addFlash('warning', "Lead transféré (n°{$percommCode}), mais aucun email trouvé pour l'envoi.");
    }

    return $this->redirectToRoute('app_lead_show', ['id' => $lead->getId()]);
}


/**
 * Cette méthode permet d'écrire une réponse dans le lead et ensuite envoyé la réponse par mail au client
 * 
 * Récupère la donnée 'reponse' via un formulaire
 * On attribut le message qu'on écrit dans le champs 'reponse' 
 * On attribut la date de reponse
 * On sauvegarde en BDD
 * Envoie de mail au client avec la reponse
 * Redirection vers la fiche detail du lead
 * @param Lead $lead le lead qu'on veut proposer une reponse
 * @param Request $request pour récupérer la donnée du formulaire
 * @param EntityManagerInterface $em La Base de donnée pour sauvegarder les changements
 */


#[Route('/lead/{id}/repondre', name: 'app_lead_repondre', methods: ['GET', 'POST'])]
public function ReponseLead(Lead $lead,Request $request): Response {

    $reponse = $request->request->get('reponse');

    $lead->setMessage($lead->getMessage(). "\n\n---\nRéponse du " . date('d/m/Y à H:i') . " :\n". trim($reponse));
    $lead->setDateReponse(new \DateTime());
    $lead->setStatut('4');
    $this->em->flush();
        EmailFunctions::sendReponseMailToDemandeur($lead->getMail(),$lead->getNom(),$lead->getPrenom(),trim($reponse)
        );

        $this->addFlash('success', 'Réponse envoyée à ' . $lead->getMail());
        


    return $this->redirectToRoute('app_lead_show', ['id' => $lead->getId()]);
}

/**
 * Cette méthode permet de mettre un lead au statut 'Orienté vers un client'
 * 
 * Recupere la donnée du formulaire
 * Attribut le statut 'Orienté vers un client'
 * Attribut au le lead le client selectionné
 * On sauvegarde en BDD
 * Redirection vers la fiche detail du lead
 * @param Lead $lead le lead qu'on veut orienté vers un client
 * @param Request $request pour récupérer la donnée du formulaire
 * @param EntityManagerInterface $em La Base de donnée pour sauvegarder les changements
*/

#[Route('/{id}/orient', name: 'app_lead_orient', methods: ['POST'])]
public function orient(Lead $lead, Request $request, InterlocuteurRepository $interlocuteurRepo): Response 
{
    $clientCode = $request->request->get('client_code');

    if (!$clientCode) {
        $this->addFlash('erreur', 'Veuillez sélectionner un client.');
        return $this->redirectToRoute('app_lead_show', ['id' => $lead->getId()]);
    }

    $interlocuteurId = $lead->getInterlocuteurId();
    $codeCommercial = str_pad((string)$interlocuteurId, 3, '0', STR_PAD_LEFT);
    
    $allClients = $this->scriptController->getAllClientsByCommercial($codeCommercial);
    
    $clientEmail = null;
    $clientNom = null;
    
    foreach ($allClients as $client) {
    if (trim($client['code']) === trim($clientCode)) {
        $clientEmail = !empty($client['email']) ? $client['email'] : null;
        $clientNom = $client['libelle'] ?? $clientCode;
        break;
    }
}
    
    if (!$clientEmail) {
        $this->addFlash('erreur', 'Email du client introuvable dans l\'ERP.');
        return $this->redirectToRoute('app_lead_show', ['id' => $lead->getId()]);
    }

    $lead->setStatut('1'); 
    $lead->setOrientationClient($clientCode);
    
    $this->em->flush();
    
    $leadData = [
        'nom'       => $lead->getNom(),      
        'prenom'    => $lead->getPrenom(),
        'telephone' => $lead->getTel(),
        'email'     => $lead->getMail(),
        'cp'        => $lead->getAdresseCP(),
        'ville'     => $lead->getAdresseVille(),   
        'message'   => $lead->getMessage(),
    ];

    EmailFunctions::sendMailToCustomer($clientEmail, $leadData);
    
    $this->addFlash('success', 'Lead orienté vers ' . $clientNom . ' et email envoyé à ' . $clientEmail);

    return $this->redirectToRoute('app_lead_show', ['id' => $lead->getId()]);
}


/**
 * Cette méthode permet de mettre un lead au statut 'Fermé'
 * 
 * Attribut le statut fermé au lead
 * @param Lead $lead le lead qu'on veut fermé
 * @param EntityManagerInterface $em La Base de donnée pour sauvegarder les changements
*/



#[Route('/lead/close/{id}', name: 'app_lead_close', methods: ['POST', 'GET'])]
    public function close(Lead $lead): Response
    {
        $lead->setStatut('2');
        
        $this->em->flush();

        $this->addFlash('succès', 'Le lead a été marqué comme fermé.');

        return $this->redirectToRoute('app_lead_index');
    }

    /**
     * Cette méthode permet d'archivé un lead
     * 
     * On instancie un learArchivé
     * on set toutes les informations du lead qu'on veut archivé
     * on pousse en BDD
     * on supprime le lead qui est la table lead pour le mettre dans la table archivé
     */
    
    #[Route('/lead/archived/{id}', name: 'app_lead_archived', methods: ['POST', 'GET'])]
    public function archived(Lead $lead, EntityManagerInterface $em): Response
    {
        $archive = new LeadArchived();
        $archive->setIdLead($lead->getId());
        $archive->setDateCreation($lead->getDateCreation());
        $archive->setCP($lead->getAdresseCP());
        $archive->setDepartement($lead->getDepartement());
        $archive->setActiviteDemandeur($lead->getCategorieDemandeur());
        $archive->setMessage($lead->getMessage());
        $archive->setStatut($lead->getStatut());
        $archive->setDateReponse($lead->getDateReponse());
        $archive->setCategorieDemandeur($lead->getCategorieDemandeur());
        $archive->setSource($lead->getSource());
        $archive->setInterlocuteurId($lead->getInterlocuteurId());
        $archive->setOrientationClient($lead->getOrientationClient() ?? '');
        $archive->setCollectionCategory($lead->getCollectionCategory() ?? '');

        $this->em->persist($archive);
        $this->em->remove($lead);
        $this->em->flush();

        $this->addFlash('success', 'Le lead a été archivé');
        return $this->redirectToRoute('app_lead_index');
    }

    /**
     * Cette méthode permet de remettre un lead en non traité en cas d'erreur
     * on met le statut à 0 (non traité)
     * on sauvegarde en BDD
    */
   
    #[Route('/lead/retour/{id}', name: 'app_lead_retour', methods: ['POST', 'GET'])]
    public function retour(Lead $lead): Response
    {
        $lead->setStatut('0');
        
        $this->em->flush();

        $this->addFlash('succès', 'Le lead a été marqué comme Non Traité.');

        return $this->redirectToRoute('app_lead_index');
    }
     

/**
 * Cette méthode permet d'afficher le detail de la fiche lead
 * 
 * Recupere l'id de l'interlocuteur du lead
 * Recupere le type du lead (Non Traité, Orienté vers un client ...)
 * Recupere le nom du commercial ou on récupère la liste des commerciaux
 * Recupere les clients par commericiaux
 * Affiche toutes les données par rapport au lead
 * @param Lead $lead le lead qu'on veut voir le détail
 * @param ManagerRegistry $doctrine pour utiliser la base Oracle
 * @param Request $request pour récupérer des informations
 * @param ScriptController $scriptController pour utiliser les méthodes de ce controller


*/

 #[Route('/{id}', name: 'app_lead_show', methods: ['GET'])]
public function show(Lead $lead,Request $request): Response {

    $teamOrMe = $request->query->get('teamOrMe', 'me');
    $filterCommercial = $request->query->get('filterCommercial');

    $commercialNom   = null;
    $interlocuteurId = $lead->getInterlocuteurId();
    $currentType = $request->query->get('type', 'all');

    if ($interlocuteurId) {
        $interlocuteur = $this->interlocuteurRepository->find($interlocuteurId);

        if ($interlocuteur) {
            $commercialNom = $interlocuteur->getNom();
        } else {
            $commerciaux = $this->scriptController->getAllCommercials();
            foreach ($commerciaux as $c) {
                if ($c['code'] === str_pad((string) $interlocuteurId, 3, '0', STR_PAD_LEFT)) {
                    $commercialNom = '[' . $c['code'] . '] ' . $c['nom'];
                    
                }
            }
            $commercialNom = $commercialNom ?? 'Code ERP : ' . $interlocuteurId;
        }
    }
    $clients = [];
    if ($interlocuteurId) {
        $percommCod = str_pad((string) $interlocuteurId, 3, '0', STR_PAD_LEFT);
        $clients    = $this->scriptController->getAllClientsByCommercial($percommCod);
    }

    return $this->render('lead/show.html.twig', [
        'lead'           => $lead,
        'commercialNom'  => $commercialNom,
        'clients'        => $clients,
        'commerciaux'    => $this->scriptController->getAllCommercials(),
        'interlocuteurs' => $this->interlocuteurRepository->findAll(),
        'currentType' => $currentType,
        'teamOrMe' =>$teamOrMe,
        'filterCommercial' => $filterCommercial,
    ]);
}
    

/**Cette méthode permet de supprimer un lead 
*/
    
#[Route('/{id}', name: 'app_lead_delete', methods: ['POST'])]
    public function delete(Request $request, Lead $lead): Response
    {
        if ($this->isCsrfTokenValid('delete'.$lead->getId(), $request->getPayload()->getString('_token'))) {
            $this->em->remove($lead);
            $this->em->flush();
        }

        return $this->redirectToRoute('app_lead_index', [], Response::HTTP_SEE_OTHER);
    }
   

}
