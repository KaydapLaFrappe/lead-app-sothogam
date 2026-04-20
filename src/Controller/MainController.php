<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\LeadRepository;
use App\Repository\InterlocuteurRepository;
use App\Service\ActiveDirectoryService;
use App\Controller\ScriptController;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Contrôleur principal de l'application gérant le tableau de bord et les statistiques d'orientations.
 */
final class MainController extends AbstractController
{
    private ScriptController $scriptController;

    /**
     * Constructeur du contrôleur
     * * @param ScriptController $scriptController Injection du contrôleur de scripts pour l'accès aux données externes
     */
    public function __construct(ScriptController $scriptController){
        $this->scriptController=$scriptController;
    }

    /**
     * Cette méthode permet d'afficher le tableau de bord principal (Dashboard)
     * * Vérifie l'authentification de l'utilisateur
     * Récupère les identifiants et les droits (ATC, CDV) via les champs extra de l'utilisateur
     * Gère les filtres par commercial pour les vues globales ou d'équipe
     * Calcule les statistiques de leads par mois pour l'année en cours
     * Récupère le Top 5 des orientations clients selon le profil utilisateur
     * Construit une cartographie des clients et de leurs libellés
     * Prépare la liste des commerciaux disponibles pour le filtrage
     * @param LeadRepository $leadRepository pour les requêtes sur les statistiques de leads
     * @param InterlocuteurRepository $interlocuteurRepo pour les données d'interlocuteurs
     * @param Request $request pour récupérer les filtres de recherche
     * @return Response la vue du dashboard avec les données formatées pour les graphiques
     */
    #[Route('/', name: 'app_main')]
    public function dash(LeadRepository $leadRepository, InterlocuteurRepository $interlocuteurRepo, Request $request): Response 
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $myIdentifier = $user->getExtraFields()['interlocuteur'];
        $teamMembres = $user->getExtraFields()['cdv'] ?? [];
        $isATC = $user->getExtraFields()['atc'] ?? false; 
        $isCDV = !empty($teamMembres) && is_array($teamMembres);
        
        $filterCommercial = $request->query->get('filterCommercial');
        $mesLeads =0;

        $moisNoms = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        $statsParMois = array_fill(1, 12, 0);
        $anneeEnCours = (int)date('Y'); 

        if ($isATC) {
            if ($filterCommercial && $filterCommercial !== 'all') {
                $result = $leadRepository->countLeadsByMonth($filterCommercial, $anneeEnCours);
            } else {
                $result = $leadRepository->countAllLeadsByMonth($anneeEnCours);
            }
        } 
        elseif ($isCDV) {
            if ($filterCommercial && $filterCommercial !== 'all') {
                $result = $leadRepository->countLeadsByMonth($filterCommercial, $anneeEnCours);
            } else {
                $teamCodes = array_column($teamMembres, 'CODE');
                $teamCodes[] = $myIdentifier;
                $result = $leadRepository->countLeadsByMonthForTeam($teamCodes, $anneeEnCours);
            }
        } 
        else {
            $result = $leadRepository->countLeadsByMonth($myIdentifier, $anneeEnCours);
        }

        if (isset($result)) {
            foreach ($result as $row) {
                $statsParMois[$row['monthNumber']] = (int)$row['count'];
            }
        }

        $labels = array_values($moisNoms);
        $data = array_values($statsParMois);
        
        if ($isATC) {
            if ($filterCommercial && $filterCommercial !== 'all') {
                $mesOrientations = $leadRepository->getTopOrientationsClient($filterCommercial, 5);
            } else {
                $mesOrientations = $leadRepository->getTopOrientationsClientAll(5);
            }
        } elseif ($isCDV && $filterCommercial && $filterCommercial !== 'all') {
            $teamCodes = array_column($teamMembres, 'CODE');
            if (in_array($filterCommercial, $teamCodes) || $filterCommercial === $myIdentifier) {
                $mesOrientations = $leadRepository->getTopOrientationsClient($filterCommercial, 5);
            } else {
                $mesOrientations = $leadRepository->getTopOrientationsClient($myIdentifier, 5);
            }
        } else {
            $mesOrientations = $leadRepository->getTopOrientationsClient($myIdentifier, 5);
        }

        if ($isATC) {
            $allCommercials = $this->scriptController->getAllCommercials();
            $clientsMap = [];
            foreach ($allCommercials as $commercial) {
                $code = str_pad(trim($commercial['code']), 3, '0', STR_PAD_LEFT);
                $clients = $this->scriptController->getAllClientsByCommercial($code);
                foreach ($clients as $client) {
                    $clientsMap[$client['code']] = $client['libelle'];
                }
            }
        } else {
            $commercialToUse = ($isCDV && $filterCommercial && $filterCommercial !== 'all') ? $filterCommercial : $myIdentifier;
            $allClients = $this->scriptController->getAllClientsByCommercial($commercialToUse);
            $clientsMap = [];
            foreach ($allClients as $client) {
                $clientsMap[$client['code']] = $client['libelle'];
            }
        }
        
        foreach ($mesOrientations as &$orientation) {
            $orientation['nom_client'] = $clientsMap[$orientation['code_client']] ?? 'Client Spécial';
        }
        
        $commerciauxDisponibles = [];
        if ($isATC) {
            $allCommercials = $this->scriptController->getAllCommercials();
            foreach ($allCommercials as $commercial) {
                $code = str_pad(trim($commercial['code']), 3, '0', STR_PAD_LEFT);
                $commerciauxDisponibles[$code] = $commercial['nom'];
            }
        } elseif ($isCDV) {
            $commerciauxDisponibles[$myIdentifier] = 'BIG BOSS';
            foreach ($teamMembres as $membre) {
                $commerciauxDisponibles[$membre['CODE']] = $membre['NOM'];
            }
        }

        return $this->render('main/index.html.twig', [
            'labels' => json_encode($labels),
            'data' => json_encode($data),
            'isCDV' => $isCDV,
            'isATC' => $isATC,
            'mesOrientations' => $mesOrientations,
            'filterCommercial' => $filterCommercial,
            'mesLeads' => $mesLeads,
            'commerciauxDisponibles' => $commerciauxDisponibles,
        ]);
    }

    /**
     * Cette méthode permet d'afficher les statistiques détaillées des orientations par client
     * * Récupère les informations de l'utilisateur connecté et ses droits d'accès
     * Détermine le mode d'affichage (Unique, Comparatif ou Tous les clients)
     * Gère la récupération des clients disponibles selon le périmètre du commercial ou de l'ATC
     * Synchronise les codes clients avec leurs libellés via le ScriptController
     * Calcule les volumes d'orientations mensuels et annuels selon le mode choisi
     * Permet la comparaison de données entre deux années civiles
     * Prépare la liste des commerciaux pour le filtrage hiérarchique (CDV/ATC)
     * @param Request $request pour récupérer les paramètres de filtrage et de mode
     * @param LeadRepository $leadRepository pour les statistiques d'orientations en base
     * @param InterlocuteurRepository $interlocuteurRepository pour la gestion des données interlocuteurs
     * @return Response la vue détaillée des orientations avec les statistiques calculées
    */
    #[Route('/stats/orientations-client', name: 'app_stats_orientations_client')]
    public function orientationsClient(Request $request, LeadRepository $leadRepository, InterlocuteurRepository $interlocuteurRepository): Response 
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $myIdentifier = $user->getExtraFields()['interlocuteur'];
        $isATC = $user->getExtraFields()['atc'] ?? false;
        $teamMembres = $user->getExtraFields()['cdv'] ?? [];
        $isCDV = !empty($teamMembres) && is_array($teamMembres);

        $mode = $request->query->get('mode', 'single');
        $annee = $request->query->get('annee', date('Y'));
        $codeClient = $request->query->get('client');
        $selectedClients = $request->query->all('clients') ?? [];
        $compareYear = $request->query->get('compareYear', false);
        $filterCommercial = $request->query->get('filterCommercial');

        if (count($selectedClients) > 5) {
            $selectedClients = array_slice($selectedClients, 0, 5);
        }

    $commercialToUse = $myIdentifier;
    
    if ($isATC && $filterCommercial && $filterCommercial !== 'all') {
        $commercialToUse = $filterCommercial;
    } elseif ($isCDV && $filterCommercial && $filterCommercial !== 'all') {
        $teamCodes = array_column($teamMembres, 'CODE');
        if (in_array($filterCommercial, $teamCodes) || $filterCommercial === $myIdentifier) {
            $commercialToUse = $filterCommercial;
        }
    }

    if ($isATC && (!$filterCommercial || $filterCommercial === 'all')) {
        $clientsDisponibles = $leadRepository->getClientsOrientesAll();
        $clientsDisponibles = array_column($clientsDisponibles, 'nom_complet', 'code');
    } else {
        $clientsDisponibles = $leadRepository->getClientsOrientes($commercialToUse);
        $clientsDisponibles = array_column($clientsDisponibles, 'nom_complet', 'code');
    }

    $clientsMap = [];
    if ($isATC && (!$filterCommercial || $filterCommercial === 'all')) {
        $allCommercials = $this->scriptController->getAllCommercials();
        foreach ($allCommercials as $commercial) {
            $code = str_pad(trim($commercial['code']), 3, '0', STR_PAD_LEFT);
            $clients = $this->scriptController->getAllClientsByCommercial($code);
            foreach ($clients as $client) {
                if (isset($clientsDisponibles[$client['code']])) {
                    $clientsMap[$client['code']] = $client['libelle'];
                    $clientsDisponibles[$client['code']] = $client['libelle'];
                }
            }
        }
    } else {
        $allClients = $this->scriptController->getAllClientsByCommercial($commercialToUse);
        foreach ($allClients as $client) {
            if (isset($clientsDisponibles[$client['code']])) {
                $clientsMap[$client['code']] = $client['libelle'];
                $clientsDisponibles[$client['code']] = $client['libelle'];
            }
        }
    }

        if (empty($clientsMap)) {
            $clientsMap = $clientsDisponibles;
        }

        $statsMois = [];
        $statsMoisPrecedent = [];
        $totalAnnee = 0;
        $totalAnneePrecedente = 0;
        $nomClient = '';
        $statsParClient = [];
        $compareStats = [];

        if ($mode === 'all') {
            $totalAnnee = $leadRepository->countAllOrientationsByYear($commercialToUse, (int)$annee);
            $statsParClientBrut = $leadRepository->countAllOrientationsByMonth($commercialToUse, (int)$annee);

            foreach ($statsParClientBrut as $code => $stats) {
                $total = 0;
                for ($i = 1; $i <= 12; $i++) {
                    $total += $stats[$i] ?? 0;
                }
                
                if ($total > 0) {
                    $statsParClient[$code] = $stats;
                }
            }
            
        } elseif ($mode === 'compare' && !empty($selectedClients)) {
            foreach ($selectedClients as $code) {
                $stats = $leadRepository->countOrientationsByClientByMonth($commercialToUse, $code, (int)$annee);
                $total = $leadRepository->countOrientationsByClientByYear($commercialToUse, $code, (int)$annee);
                
                if ($total > 0) {
                    $compareStats[] = [
                        'code' => $code,
                        'nom' => $clientsMap[$code] ?? $code,
                        'stats' => $stats,
                        'total' => $total
                    ];
                    $totalAnnee += $total;
                }
            }
            
        } elseif ($mode === 'single' && $codeClient) {
            $statsMois = $leadRepository->countOrientationsByClientByMonth($commercialToUse, $codeClient, (int)$annee);
            $totalAnnee = $leadRepository->countOrientationsByClientByYear($commercialToUse, $codeClient, (int)$annee);
            $nomClient = $clientsMap[$codeClient] ?? $codeClient;
            
            if ($compareYear) {
                $anneePrecedente = (int)$annee - 1;
                $statsMoisPrecedent = $leadRepository->countOrientationsByClientByMonth($commercialToUse, $codeClient, $anneePrecedente);
                $totalAnneePrecedente = $leadRepository->countOrientationsByClientByYear($commercialToUse, $codeClient, $anneePrecedente);
            }
        }

        $commerciauxDisponibles = [];
        if ($isATC) {
            $allCommercials = $this->scriptController->getAllCommercials();
            foreach ($allCommercials as $commercial) {
                $code = str_pad(trim($commercial['code']), 3, '0', STR_PAD_LEFT);
                $commerciauxDisponibles[$code] = $commercial['nom'];
            }
        } elseif ($isCDV) {
            $commerciauxDisponibles[$myIdentifier] = 'Moi';
            foreach ($teamMembres as $membre) {
                $commerciauxDisponibles[$membre['CODE']] = $membre['NOM'];
            }
        }

        $moisNoms = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        return $this->render('main/orientations_client.html.twig', [
            'clientsDisponibles' => $clientsDisponibles,
            'mode' => $mode,
            'codeClient' => $codeClient,
            'selectedClients' => $selectedClients,
            'nomClient' => $nomClient,
            'annee' => $annee,
            'statsMois' => $statsMois,
            'statsMoisPrecedent' => $statsMoisPrecedent,
            'totalAnnee' => $totalAnnee,
            'totalAnneePrecedente' => $totalAnneePrecedente,
            'moisNoms' => $moisNoms,
            'statsParClient' => $statsParClient,
            'compareStats' => $compareStats,
            'compareYear' => $compareYear,
            'isATC' => $isATC,
            'isCDV' => $isCDV,
            'commerciauxDisponibles' => $commerciauxDisponibles,
            'filterCommercial' => $filterCommercial,
            'clientsMap' => $clientsMap,  
        ]);
    }
}