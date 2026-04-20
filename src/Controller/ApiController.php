<?php

namespace App\Controller;

use App\Controller\ScriptController;
use App\Repository\InterlocuteurRepository;
use App\Repository\LeadArchivedRepository;
use App\Repository\LeadRepository;
use App\Security\CustomLdapUserProvider;
use App\Service\ActiveDirectoryService;
use App\Service\LeadApiFormatter;
use App\Tool\EmailFunctions;
use Doctrine\Persistence\ManagerRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Ldap\Exception\InvalidCredentialsException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Security\LdapUser;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ApiController extends AbstractController
{
    private const LDAP_DN_STRING = 'SOTHO\{user_identifier}';

    public function __construct(
        private CustomLdapUserProvider $ldapUserProvider,
        private Ldap $ldap,
        private JWTTokenManagerInterface $jwtManager,
        private LeadRepository $leadRepository,
        private LeadArchivedRepository $leadArchivedRepository,
        private InterlocuteurRepository $interlocuteurRepository,
        private ActiveDirectoryService $activeDirectoryService,
        private LeadApiFormatter $leadFormatter,
        private ScriptController $scriptController,
        private ManagerRegistry $doctrine,
        private HttpClientInterface $client,
        private ?string $testUserOverride = null,
    ) {}

    private const TOKEN_TTL_DEFAULT = 43200;      // 12 heures
    private const TOKEN_TTL_REMEMBER = 2592000;  // 30 jours

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['username'], $data['password'])) {
            return $this->json(['error' => 'username et password requis'], Response::HTTP_BAD_REQUEST);
        }

        $username = $data['username'];
        $password = $data['password'];
        $rememberMe = filter_var($data['remember_me'] ?? false, FILTER_VALIDATE_BOOLEAN);

        try {
            $user = $this->ldapUserProvider->loadUserByIdentifier($username);
        } catch (UserNotFoundException) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $dn = str_replace('{user_identifier}', $username, self::LDAP_DN_STRING);

        try {
            $this->ldap->bind($dn, $password);
        } catch (InvalidCredentialsException) {
            return $this->json(['error' => 'Identifiants invalides'], Response::HTTP_UNAUTHORIZED);
        }

        $ttl = $rememberMe ? self::TOKEN_TTL_REMEMBER : self::TOKEN_TTL_DEFAULT;
        $token = $this->jwtManager->createFromPayload($user, [
            'iat' => time(),
            'exp' => time() + $ttl,
        ]);

        $extraFields = $user->getExtraFields() ?? [];

        return $this->json([
            'message' => 'Connexion réussie',
            'token' => $token,
            'expires_in' => $ttl,
            'user' => [
                'username' => $user->getUserIdentifier(),
                'email' => $extraFields['email'] ?? null,
                'codeCommercial' => $extraFields['interlocuteur'] ?? null,
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/api/leads', name: 'api_leads', methods: ['GET'])]
    #[Route('/api/leads/{type}', name: 'api_leads_type', requirements: ['type' => 'pending|closed|processed|all'], defaults: ['type' => 'all'], methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Authentification requise')]
    public function getLeads(Request $request, string $type = 'all'): Response
    {
        $user = $this->getUser();
        if (!$user instanceof LdapUser) {
            return $this->json(['error' => 'Utilisateur invalide'], Response::HTTP_UNAUTHORIZED);
        }
        if ($this->testUserOverride !== null && $this->testUserOverride !== '') {
            try {
                $user = $this->ldapUserProvider->loadUserByIdentifier($this->testUserOverride);
            } catch (UserNotFoundException) {
                // ignore, keep real user
            }
        }

        $roles = $user->getRoles();
        $extraFields = $user->getExtraFields() ?? [];

        $myIdentifier = $extraFields['interlocuteur'] ?? null;
        $teamMembres = $extraFields['cdv'] ?? [];
        $isCDV = !empty($teamMembres) && is_array($teamMembres);

        if (in_array('ROLE_ATC', $roles, true)) {
            $leads = $this->leadRepository->findLeadsForUser($user, [], $type);
        } else {
            $filterIdentifiers = $myIdentifier !== null ? [$myIdentifier] : [];
            if ($isCDV) {
                $teamCodes = array_column($teamMembres, 'CODE');
                foreach ($teamCodes as $code) {
                    if (!in_array($code, $filterIdentifiers, true)) {
                        $filterIdentifiers[] = $code;
                    }
                }
            }
            $leads = $this->leadRepository->findLeadsByUser($filterIdentifiers, $type);
        }

        $allCommercials = $this->scriptController->getAllCommercials();
        $nomsCommerciaux = [];
        foreach ($allCommercials as $commercial) {
            $code = str_pad(trim($commercial['code'] ?? ''), 3, '0', STR_PAD_LEFT);
            $nomsCommerciaux[$code] = $commercial['nom'] ?? '';
        }

        $sort = $request->query->get('sort', 'type');
        $order = $request->query->get('order', 'asc');
        if (!in_array($sort, ['dept', 'type', 'date'])) {
            $sort = 'type';
        }

        usort($leads, function ($a, $b) use ($sort, $order) {
            if ($sort === 'dept') {
                $valA = (int) substr($a->getAdresseCP() ?? '', 0, 2);
                $valB = (int) substr($b->getAdresseCP() ?? '', 0, 2);
            } elseif ($sort === 'type') {
                $valA = $a->getCategorieDemandeur() ?? 0;
                $valB = $b->getCategorieDemandeur() ?? 0;
            } else {
                $valA = $a->getDateCreation()?->getTimestamp() ?? 0;
                $valB = $b->getDateCreation()?->getTimestamp() ?? 0;
            }
            $cmp = ($order === 'asc') ? ($valA <=> $valB) : ($valB <=> $valA);
            if ($cmp !== 0) {
                return $cmp;
            }
            $tsA = $a->getDateCreation()?->getTimestamp() ?? 0;
            $tsB = $b->getDateCreation()?->getTimestamp() ?? 0;
            return $tsB <=> $tsA;
        });

        $interlocuteurIdFromExtra = isset($extraFields['interlocuteur']) ? (int) $extraFields['interlocuteur'] : null;
        $identifier = $user->getUserIdentifier();
        $moi = $this->interlocuteurRepository->findOneBy(['identifier' => $identifier]);
        $myInterlocuteurId = $interlocuteurIdFromExtra ?: $moi?->getId();

        $teamCodes = $isCDV ? array_column($teamMembres, 'CODE') : [];
        $showTeamLeads = !empty($teamCodes) || in_array('ROLE_ATC', $roles, true);

        $myCode = is_string($myIdentifier) ? str_pad(trim($myIdentifier), 3, '0', STR_PAD_LEFT) : str_pad((string) ($myInterlocuteurId ?? ''), 3, '0', STR_PAD_LEFT);
        $myNom = $nomsCommerciaux[$myCode] ?? 'Code : ' . $myCode;

        $result = ['me' => ['code' => $myCode, 'nom' => $myNom, 'leads' => []]];

        foreach ($leads as $lead) {
            $formatted = $this->leadFormatter->formatLead($lead);
            $codeCommercial = str_pad((string) ($lead->getInterlocuteurId() ?? ''), 3, '0', STR_PAD_LEFT);
            $formatted['commercialNom'] = $nomsCommerciaux[$codeCommercial] ?? 'Code : ' . $codeCommercial;

            $interlocuteurId = $lead->getInterlocuteurId();

            if ($interlocuteurId === $myInterlocuteurId) {
                $result['me']['leads'][] = $formatted;
            } elseif ($showTeamLeads && $interlocuteurId !== null) {
                if (!isset($result[$codeCommercial])) {
                    $result[$codeCommercial] = [
                        'code' => $codeCommercial,
                        'nom' => $nomsCommerciaux[$codeCommercial] ?? 'Code : ' . $codeCommercial,
                        'leads' => [],
                    ];
                }
                $result[$codeCommercial]['leads'][] = $formatted;
            }
        }

        return $this->json($result, Response::HTTP_OK);
    }

    #[Route('/api/leads/archived', name: 'api_leads_archived', methods: ['GET'])]
    #[Route('/api/leads/archived/{type}', name: 'api_leads_archived_type', requirements: ['type' => 'pending|closed|processed|all'], defaults: ['type' => 'all'], methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Authentification requise')]
    public function getLeadsArchived(Request $request, string $type = 'all'): Response
    {
        $user = $this->getUser();
        if (!$user instanceof LdapUser) {
            return $this->json(['error' => 'Utilisateur invalide'], Response::HTTP_UNAUTHORIZED);
        }
        if ($this->testUserOverride !== null && $this->testUserOverride !== '') {
            try {
                $user = $this->ldapUserProvider->loadUserByIdentifier($this->testUserOverride);
            } catch (UserNotFoundException) {
                // ignore, keep real user
            }
        }

        $roles = $user->getRoles();
        $identifier = $user->getUserIdentifier();
        $extraFields = $user->getExtraFields() ?? [];

        $myIdentifier = $extraFields['interlocuteur'] ?? null;
        $teamMembres = $extraFields['cdv'] ?? [];
        $isCDV = !empty($teamMembres) && is_array($teamMembres);

        if (in_array('ROLE_ATC', $roles, true)) {
            $leads = $this->leadArchivedRepository->findLeadsForUser($user, [], $type);
        } else {
            $filterIdentifiers = $myIdentifier !== null ? [$myIdentifier] : [];
            if ($isCDV) {
                $teamCodes = array_column($teamMembres, 'CODE');
                foreach ($teamCodes as $code) {
                    if (!in_array($code, $filterIdentifiers, true)) {
                        $filterIdentifiers[] = $code;
                    }
                }
            }
            $leads = $this->leadArchivedRepository->findLeadsByUser($filterIdentifiers, $type);
        }

        $allCommercials = $this->scriptController->getAllCommercials();
        $nomsCommerciaux = [];
        foreach ($allCommercials as $commercial) {
            $code = str_pad(trim($commercial['code'] ?? ''), 3, '0', STR_PAD_LEFT);
            $nomsCommerciaux[$code] = $commercial['nom'] ?? '';
        }

        $sort = $request->query->get('sort', 'type');
        $order = $request->query->get('order', 'asc');
        if (!in_array($sort, ['dept', 'type', 'date'])) {
            $sort = 'type';
        }

        usort($leads, function ($a, $b) use ($sort, $order) {
            if ($sort === 'dept') {
                $valA = (int) substr($a->getCP() ?? '', 0, 2);
                $valB = (int) substr($b->getCP() ?? '', 0, 2);
            } elseif ($sort === 'type') {
                $valA = $a->getCategorieDemandeur() ?? 0;
                $valB = $b->getCategorieDemandeur() ?? 0;
            } else {
                $valA = $a->getDateCreation()?->getTimestamp() ?? 0;
                $valB = $b->getDateCreation()?->getTimestamp() ?? 0;
            }
            $cmp = ($order === 'asc') ? ($valA <=> $valB) : ($valB <=> $valA);
            if ($cmp !== 0) {
                return $cmp;
            }
            $tsA = $a->getDateCreation()?->getTimestamp() ?? 0;
            $tsB = $b->getDateCreation()?->getTimestamp() ?? 0;
            return $tsB <=> $tsA;
        });

        $interlocuteurIdFromExtra = isset($extraFields['interlocuteur']) ? (int) $extraFields['interlocuteur'] : null;
        $moi = $this->interlocuteurRepository->findOneBy(['identifier' => $identifier]);
        $myInterlocuteurId = $interlocuteurIdFromExtra ?: $moi?->getId();

        $teamCodes = $isCDV ? array_column($teamMembres, 'CODE') : [];
        $showTeamLeads = !empty($teamCodes) || in_array('ROLE_ATC', $roles, true);

        $myCode = is_string($myIdentifier) ? str_pad(trim($myIdentifier), 3, '0', STR_PAD_LEFT) : str_pad((string) ($myInterlocuteurId ?? ''), 3, '0', STR_PAD_LEFT);
        $myNom = $nomsCommerciaux[$myCode] ?? 'Code : ' . $myCode;

        $result = ['me' => ['code' => $myCode, 'nom' => $myNom, 'leads' => []]];

        foreach ($leads as $lead) {
            $formatted = $this->leadFormatter->formatLeadArchived($lead);
            $codeCommercial = str_pad((string) ($lead->getInterlocuteurId() ?? ''), 3, '0', STR_PAD_LEFT);
            $formatted['commercialNom'] = $nomsCommerciaux[$codeCommercial] ?? 'Code : ' . $codeCommercial;

            $interlocuteurId = $lead->getInterlocuteurId();

            if ($interlocuteurId === $myInterlocuteurId) {
                $result['me']['leads'][] = $formatted;
            } elseif ($showTeamLeads && $interlocuteurId !== null) {
                if (!isset($result[$codeCommercial])) {
                    $result[$codeCommercial] = [
                        'code' => $codeCommercial,
                        'nom' => $nomsCommerciaux[$codeCommercial] ?? 'Code : ' . $codeCommercial,
                        'leads' => [],
                    ];
                }
                $result[$codeCommercial]['leads'][] = $formatted;
            }
        }

        return $this->json($result, Response::HTTP_OK);
    }

    #[Route('/api/stats/orientations', name: 'api_stats_orientations', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Authentification requise')]
    public function getStatsOrientations(Request $request): Response
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $numCommercial = $data['numCommercial'] ?? null;
        $clientsInput = $data['clients'] ?? [];
        $anneeDebut = isset($data['anneeDebut']) ? (int) $data['anneeDebut'] : null;
        $anneeFin = isset($data['anneeFin']) ? (int) $data['anneeFin'] : null;

        if (!$numCommercial || !is_string($numCommercial)) {
            return $this->json(['error' => 'numCommercial requis (string)'], Response::HTTP_BAD_REQUEST);
        }
        if (!is_array($clientsInput)) {
            return $this->json(['error' => 'clients doit être un tableau ou un objet'], Response::HTTP_BAD_REQUEST);
        }

        $clientsMap = [];
        if (isset($clientsInput[0]) && is_array($clientsInput[0])) {
            $clientsMap = $clientsInput[0];
        } elseif (array_keys($clientsInput) !== range(0, count($clientsInput) - 1)) {
            $clientsMap = $clientsInput;
        } else {
            foreach ($clientsInput as $c) {
                if (is_string($c)) {
                    $code = substr(strtoupper(trim($c)), 0, 5);
                    $clientsMap[$code] = $c;
                }
            }
        }

        $codes = array_keys($clientsMap);
        $codes = array_values(array_unique(array_map(fn ($c) => substr(strtoupper(trim((string) $c)), 0, 5), $codes)));

        $numCommercial = str_pad(trim($numCommercial), 3, '0', STR_PAD_LEFT);

        $stats = $this->leadRepository->getStatsOrientationsByCommercialAndClients(
            $numCommercial,
            $codes,
            $anneeDebut,
            $anneeFin
        );

        foreach ($stats as $code => $donnees) {
            $stats[$code]['nom'] = $clientsMap[$code] ?? $code;
        }

        return $this->json($stats, Response::HTTP_OK);
    }

    #[Route('/api/leads/{id}', name: 'api_lead_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Authentification requise')]
    public function showLead(string $id): Response
    {
        $lead = $this->leadRepository->find((int) $id);
        return $this->json($lead, Response::HTTP_OK);
    }

    #[Route('/api/leads/clients/all', name: 'api_lead_clients_all', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Authentification requise')]
    public function getAllClients(): Response
    {
        $codes = $this->leadRepository->getClientsOrientesAll();
        $clients = array_map(fn (array $r) => [
            'id' => $r['id'],
            'code' => $r['code'], 
            'libelle' => $r['code']
        ], $codes);
        return $this->json($clients, Response::HTTP_OK);
    }

    #[Route('/api/leads/{numCommercial}/clients', name: 'api_lead_clients', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Authentification requise')]
    public function getClients(string $numCommercial): Response
    {
        $clients = $this->scriptController->getAllClientsByCommercial(str_pad(trim($numCommercial), 3, '0', STR_PAD_LEFT));
        return $this->json($clients, Response::HTTP_OK);
    }

    #[Route('/api/leads/commercials', name: 'api_lead_all_commercials', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Authentification requise')]
    public function getAllCommercials(): Response
    {
        $commerciaux = $this->scriptController->getAllCommercials();
        return $this->json($commerciaux, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/api/leads/{id}/close', name: 'api_lead_close', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Authentification requise')]
    public function closeLead(int $id, EntityManagerInterface $em): Response
    {
        $lead = $this->leadRepository->find($id);
        if (!$lead) {
            return $this->json(['error' => 'Lead non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $lead->setStatut('2');
        $em->flush();

        return $this->json(['message' => 'Lead clôturé'], Response::HTTP_OK);
    }

    #[Route('/api/leads/{id}/retour', name: 'api_lead_retour', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Authentification requise')]
    public function retourLead(int $id, EntityManagerInterface $em): Response
    {
        $lead = $this->leadRepository->find($id);
        if (!$lead) {
            return $this->json(['error' => 'Lead non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $lead->setStatut('0');
        $em->flush();
        return $this->json(['message' => 'Lead marqué comme Non Traité'], Response::HTTP_OK);
    }

    #[Route('/api/leads/{id}/orient', name: 'api_lead_orient', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Authentification requise')]
    public function orientLead(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);
        $clientCode = $data['client_code'];

        $lead = $this->leadRepository->find($id);
        if (!$lead) {
            return $this->json(['error' => 'Lead non trouvé'], Response::HTTP_NOT_FOUND);
        }
        if (!$clientCode) {
            return $this->json(['error' => 'Code client non trouvé'], Response::HTTP_BAD_REQUEST);
        }

        $lead->setStatut('1');
        $lead->setOrientationClient($clientCode);
        $em->flush();

        EmailFunctions::sendCollectMailToSupplier($lead->getMail());

        return $this->json(['message' => 'Lead marqué comme orienté'], Response::HTTP_OK);
    }

    #[Route('/api/leads/{id}/repondre', name: 'api_lead_repondre', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Authentification requise')]
    public function repondreLead(int $id, EntityManagerInterface $em, Request $request): Response
    {

        $data = json_decode($request->getContent(), true);
        $response = $data['message'];

        $lead = $this->leadRepository->find($id);
        if (!$lead) {
            return $this->json(['error' => 'Lead non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $lead->setMessage($response);
        $lead->setStatut('4');
        $lead->setDateReponse(new \DateTime());
        $em->flush();
        EmailFunctions::sendReponseMailToClient(
            $lead->getMail(),
            $lead->getNom(),
            $lead->getPrenom(),
            trim($response)
        );
        return $this->json(['message' => 'Réponse envoyée à ' . $lead->getMail()], Response::HTTP_OK);
    }

    #[Route('/api/leads/{id}/transfer', name: 'api_lead_transfer', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Authentification requise')]
    public function transferLead(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);
        $codeCommercial = $data['commercial_code'];
        
        $lead = $this->leadRepository->find($id);
        if (!$lead) {
            return $this->json(['error' => 'Lead non trouvé'], Response::HTTP_NOT_FOUND);
        }
        if (!$codeCommercial) {
            return $this->json(['error' => 'Code commercial non trouvé'], Response::HTTP_BAD_REQUEST);
        }
        
        $lead->setInterlocuteurId($codeCommercial);
        $lead->setStatut('0');
        $em->flush();

        EmailFunctions::sendCollectMailToSupplier($lead->getMail());

        return $this->json(['message' => 'Lead transféré au commercial ' . $codeCommercial], Response::HTTP_OK);
    }

    #[Route('/api/tickets', name: 'api_ticket_create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'Authentification requise')]
    public function createTicket(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $title = $data['title'];
        $description = $data['description'];
        $user = $this->getUser();
        $username = $user->getUserIdentifier();
        $email = $user->getExtraFields()['email'];

        $client = $this->client;
        $url = 'http://192.168.9.248:5678/webhook/tickets-leads';

        // Données à envoyer (exemple : un tableau ou un JSON)
        $data = [
            'email' => $email,
            'title' => $title,
            'description' => $description,
            'username' => $username,
        ];

        try {
            $response = $client->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => $_ENV['WEBHOOK_AUTH_TOKEN'],
                ],
                'body' => $data,
            ]);

            // Récupérer le statut HTTP
            $statusCode = $response->getStatusCode();

            // Récupérer le contenu de la réponse
            $content = $response->getContent();

            // Traiter la réponse selon tes besoins
            return new Response("Requête envoyée avec succès. Réponse : " . $content, $statusCode);
        } catch (\Exception $e) {
            // Gérer les erreurs
            return new Response("Erreur lors de l'envoi de la requête : " . $e->getMessage(), 500);
        }

        return $this->json(['message' => 'Ticket créé'], Response::HTTP_OK);
    }
}