<?php

namespace App\Controller;

use App\Entity\Lead;
use App\Entity\Interlocuteur;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Ldap\Security\LdapUser;
use App\Service\ActiveDirectoryService;
use App\Tool\EmailFunctions;

final class ScriptController extends AbstractController
{    
    public function __construct(private ActiveDirectoryService $activeDirectoryService,private ManagerRegistry $doctrine,EntityManagerInterface $em,) {
        $this->em=$em;
    }


    #[Route('/scLript', name: 'app_script')]
    public function index(): Response
    {
        return $this->render('scripterp/index.html.twig');
    }

   #[Route('/script/run', name: 'app_script_run', methods: ['GET'])]
    public function execute(Request $request): Response
    {
        $leads = $this->recupLeadSotho();
        
        $nbLeads = count($leads);
        
        $this->addFlash('success', "$nbLeads nouveaux leads ont été importés avec succès depuis OVH.");

        return $this->redirectToRoute('app_script');
    }


    /**
     * Récupère les équipes pour un manager (via LDAP).
     * Retourne la structure [identifier => ['membres' => [['code' => ...], ...]]].
     */
    public function getTeamsData(?LdapUser $user): array
    {
        if (!$user instanceof LdapUser) {
            return [];
        }

        $identifier = $user->getUserIdentifier();
        $roles = $user->getRoles();

        if (!in_array('ROLE_MANAGER', $roles, true)) {
            return [];
        }

        $extraFields = $user->getExtraFields() ?? [];
        $myDn = $extraFields['DN'] ?? null;
        if (!$myDn) {
            return [];
        }

        $teamUsers = $this->activeDirectoryService->getUsersByManager($myDn);
        $membres = array_map(
            fn($u) => ['code' => $u->getUserIdentifier()],
            $teamUsers
        );

        return [$identifier => ['membres' => $membres]];
    }
    
    /**
     * Cette méthode permet de récuperer tous les commerciaux
     * 
     * Récupère la connexion à la base oracle
     * Prépare la requête SQL
     * Execute la requête pour récupérer tous les commerciaux
     * Retourne un tableau avec colonnes définis
     * @param ManagerRegistry $doctrine permet de récupérer la connexion à la Base Oracle
     * @return array Retourne une liste de commerciaux avec leurs infos
     */
    
    
    public function getAllCommercials(): array
    {
            $conn = $this->doctrine->getConnection('oracle');

            $sql = "SELECT 
                        representants.PERCOMM_COD AS CODE_REPRESENTANT,
                        infos.EMAIL               AS EMAIL,
                        infos.NOM                 AS NOM
                    FROM 
                        ERP_SOT.FDV_PERCOMM representants,
                        ERP_SOT.GEN_ADRESSE infos
                    WHERE 
                        infos.ADRESSE_ID = representants.ADRESSE_ID
                        AND infos.EMAIL NOT LIKE '% %'
                    ORDER BY 
                        representants.PERCOMM_COD";

            $rows = $conn->executeQuery($sql)->fetchAllAssociative();

            return array_map(fn($row) => [
                'code' => $row['CODE_REPRESENTANT'], 
                'nom'  => $row['NOM'],               
                'mail' => $row['EMAIL'],              
            ], $rows);


            return [];
        
    }

       /**
 * Cette méthode permet de récupérer tous les clients d'un commercial depuis l'ERP Oracle
 * 
 * Exécute une requête SQL sur la base Oracle pour récupérer les clients actifs
 * Filtre par code commercial (PERCOMM_COD)
 * Récupère le code client, le libellé (nom + ville), le département et l'email
 * Retourne un tableau associatif avec les informations des clients
 * 
 * @param string $percommCod le code du commercial (format '001', '002', etc.)
 * @return array tableau de clients avec code, libelle, departement, email
 */
public function getAllClientsByCommercial(string $percommCod): array
{
    $conn = $this->doctrine->getConnection('oracle');

    $sql = "SELECT DISTINCT
                ERP_SOT.CLI_CLIENT.CLIENT_COD AS CLIENT,
                gen_adresse_client.NOM || ' - ' || gen_adresse_client.VILLE_COD AS CLIENTDES,
                gen_adresse_client.DEPARTEMENT_COD,
                gen_adresse_client.EMAIL AS EMAIL
            FROM
                ERP_SOT.CLI_CLIENT,
                ERP_SOT.CLI_CLIMQ,
                ERP_SOT.GEN_STATUTDAT,
                ERP_SOT.GEN_ADRESSE gen_adresse_client,
                ERP_SOT.CLI_CLIADR,
                TERP_SOT.AAD_BI_CLF_CLIENT LibCLF_CLIENT,
                ARGOS_SOT.CLIENT,
                ERP_SOT.FDV_PERCOMM,
                ERP_SOT.FDV_PERCOMM fdv_sect,
                ARGOS_SOT.SOCIETE s,
                ERP_SOT.PAR_DEPARTEMENT
            WHERE
                ERP_SOT.PAR_DEPARTEMENT.DEPARTEMENT_COD(+) = gen_adresse_client.DEPARTEMENT_COD
                AND ERP_SOT.CLI_CLIENT.ENREG_ID = ERP_SOT.GEN_STATUTDAT.ENREG_ID
                AND ERP_SOT.CLI_CLIENT.CLIENT_COD = ERP_SOT.CLI_CLIMQ.CLIENT_COD
                AND ERP_SOT.CLI_CLIENT.CLIENT_COD = ERP_SOT.CLI_CLIADR.CLIENT_COD
                AND ERP_SOT.CLI_CLIADR.ADRESSE_ID = gen_adresse_client.ADRESSE_ID
                AND ERP_SOT.CLI_CLIADR.CLIADR_COD = '000'
                AND ERP_SOT.CLI_CLIADR.CLIENT_COD = LibCLF_CLIENT.CLIENT
                AND ERP_SOT.CLI_CLIENT.CLIENT_COD = ARGOS_SOT.CLIENT.CLI_COD(+)
                AND s.SOC_LIB LIKE '%SOTHOFERM%'
                AND s.SOC = '01'
                AND LibCLF_CLIENT.REP_ACTUEL = ERP_SOT.FDV_PERCOMM.PERCOMM_COD
                AND ERP_SOT.FDV_PERCOMM.PERCOMM_COD = fdv_sect.PERCOMM_COD
                AND ARGOS_SOT.CLIENT.SOC = s.SOC
                AND ERP_SOT.GEN_STATUTDAT.STATUT_COD IN ('C', 'P')
                AND (ERP_SOT.GEN_STATUTDAT.FIN_DAT IS NULL OR ERP_SOT.GEN_STATUTDAT.FIN_DAT >= SYSDATE)
                AND LibCLF_CLIENT.REP_ACTUEL = :percomm
            ORDER BY CLIENTDES";

    $rows = $conn->executeQuery($sql, ['percomm' => $percommCod])->fetchAllAssociative();

    return array_map(fn($row) => [
        'code' => $row['CLIENT'],
        'libelle' => $row['CLIENTDES'],
        'departement' => $row['DEPARTEMENT_COD'],
        'email' => $row['EMAIL'] ?? null,
    ], $rows);
}

/**
 * Cette méthode permet de retourner les commerciaux de sa team si il est CDV
 * 
 * On récupère la connexion à la base oracle
 * On prépare la requête SQL pour aller chercher les infos dans l'ERP
 * On exécute la requête SQL en mettant un bind sur $percommCod pour avoir le code en parametre et on la place dans $membre
 * On retourne les membres du CDV
 * @param ManagerRegistry $doctrine permet de récupérer la connexion à la Base Oracle
 * @param string $percommCod le code du commercial de la team du CDV
 * @return une liste de commerciaux appartenant à la team du CDV
 */
    public function isCDV(string $percommCod): array
{
    $connexion = $this->doctrine->getConnection('oracle');
    
    $requete = "SELECT 
                    TRIM(reps.PERCOMM_COD) as CODE,
                    TRIM(infos.NOM) as NOM
                FROM 
                    ERP_SOT.FDV_PERCOMM reps
                JOIN 
                    ERP_SOT.GEN_ADRESSE infos ON infos.ADRESSE_ID = reps.ADRESSE_ID
                WHERE 
                    TRIM(infos.NOM2) = :code
                ORDER BY 
                    infos.NOM ASC";

    $membres = $connexion->executeQuery($requete, ['code' => trim($percommCod)])->fetchAllAssociative();

    return $membres;
}

    /**
     * Récupère les nouveaux leads depuis la base OVH et les synchronise avec l'ERP (Oracle).
     * 
     * On regarde le dernier ID importé en base locale.
     * On extrait des nouveaux IDs de posts depuis la base OVH
     * Pour chaque ID : on map les données pour les Leads
     * On affecte le lead au commercial
     * On met à jour l'index de lecture local.
     * @param ManagerRegistry $doctrine Gère les connexions (Local, OVH, Oracle)
     * @param EntityManagerInterface $em Gère les entités
     * @return Lead[] Liste des leads importés
     */
    
public function recupLeadSotho(): array
{
    $connLocal = $this->doctrine->getConnection('default');
    $connOvh = $this->doctrine->getConnection('ovh');

    $sqlIndex = "SELECT Value FROM tparametre WHERE Name = 'INDEX_SOTHO2021' LIMIT 1";
    $lastIndex = $connLocal->fetchOne($sqlIndex);

    $sqlPosts = "SELECT ID FROM tjdt_posts WHERE post_type IN ('lead', 'leads_resellers') AND ID > :lastIndex ORDER BY ID ASC";
    $postIds = $connOvh->fetchFirstColumn($sqlPosts, ['lastIndex' => $lastIndex]);

    $listeLeadsSotho = [];
    $lastId = $lastIndex;

    foreach ($postIds as $postId) {
        $sqlMeta = "SELECT meta_key, meta_value FROM tjdt_postmeta WHERE post_id = :postId AND meta_key NOT LIKE '\_%'";
        $metas = $connOvh->fetchAllKeyValue($sqlMeta, ['postId' => $postId]);
        
        $cp = trim($metas['ml_code_postal'] ?? '');
        $dep = substr($cp, 0, 2);
        
        $infoOracle = $this->getCommByDept($dep);
        
        if (empty($infoOracle) || !isset($infoOracle['COMMERCIAL'])) {
            $lastId = $postId;
            continue;
        }
        
        $codeCommercial = trim($infoOracle['COMMERCIAL']);
        
        if (empty($codeCommercial)) {
            $lastId = $postId;
            continue;
        }
        $lead = new Lead();     
        $lead->setSource('1'); 
        $lead->setDateCreation(new \DateTime());
        $lead->setStatut('0'); 
        $lead->setDepartement($dep);
        $lead->setInterlocuteurId($codeCommercial);

        if (isset($metas['ml_nom'])) $lead->setNom(trim($metas['ml_nom']));
        if (isset($metas['ml_prenom'])) $lead->setPrenom(trim($metas['ml_prenom']));
        if (isset($metas['ml_email'])) $lead->setMail(trim($metas['ml_email']));
        if (isset($metas['ml_telephone'])) $lead->setTel(trim($metas['ml_telephone']));
        if (isset($metas['ml_code_postal'])) $lead->setAdresseCP(trim($metas['ml_code_postal']));
        if (isset($metas['ml_ville'])) $lead->setAdresseVille(trim($metas['ml_ville']));
        if (isset($metas['ml_projet_message'])) $lead->setMessage(trim($metas['ml_projet_message']));
        if (isset($metas['ml_activite'])) $lead->setActiviteDemandeur(trim($metas['ml_activite']));

        if (isset($metas['ml_category_collection'])) {
            $catData = @unserialize($metas['ml_category_collection']);
            if (is_array($catData) && isset($catData[0])) {
                $lead->setCollectionCategory((string) $catData[0]);
            } else {
                $lead->setCollectionCategory($metas['ml_category_collection']);
            }
        } else {
            $lead->setCollectionCategory('');
        }

        if (!empty($metas['ml_societe'])) {
            $lead->setNomSociete(trim($metas['ml_societe']));
            $lead->setCategorieDemandeur(0);
        } else {
            $lead->setNomSociete('');
            $lead->setCategorieDemandeur(1);
        }

        $this->em->persist($lead);
        $listeLeadsSotho[] = $lead;
        $lastId = $postId; 
    }
    

  
    if ($lastId > $lastIndex) {
        $this->em->flush();
        $sqlUpd = "UPDATE tparametre SET Value = :val WHERE Name = 'INDEX_SOTHO2021'";
        $connLocal->executeStatement($sqlUpd, ['val' => $lastId]);

        //Mettre mail du commercial
        //EmailFunctions::sendCollectMailToSupplier("victor.moraud2004@gmail.com");

    }
    return $listeLeadsSotho;
}


    /**
     * Cette méthode permet de récupérer les codes commerciaux (ATC et Commercial) associés à un département
     * * Initialise la connexion à la base de données Oracle via Doctrine
     * Exécute une requête SQL avec jointure entre les tables de départements et de gestion commerciale
     * Filtre les résultats par le code département fourni en paramètre
     * @param string $dpt le code du département (ex: "75", "38")
     * @return array un tableau associatif contenant DEPARTEMENT_COD, ATC et COMMERCIAL, ou vide si non trouvé
    */
    public function getCommByDept(string $dpt): array
    {
        $conn = $this->doctrine->getConnection('oracle');

        $sql = "SELECT 
                    departement.DEPARTEMENT_COD, 
                    OUTILS_GEN_COM.COM AS ATC, 
                    departement.PERCOMM_COD as COMMERCIAL
                FROM 
                    OUTILS_SOT.GEN_COM OUTILS_GEN_COM, 
                    ERP_SOT.PAR_DEPARTEMENT departement 
                WHERE 
                    departement.ENREG_ID = OUTILS_GEN_COM.ENREG_ID 
                    AND departement.DEPARTEMENT_COD = :dpt";

        $result = $conn->executeQuery($sql, ['dpt' => trim($dpt)])->fetchAssociative();
        return $result ?: [];
    }

    /**
     * Cette méthode permet de récupérer le code identifiant (ID) d'un opérateur à partir de son email
     * * Initialise la connexion à la base de données Oracle
     * Effectue une jointure entre la table des représentants (FDV_PERCOMM) et celle des adresses/emails (GEN_ADRESSE)
     * Récupère l'ID correspondant au champ PERCOMM_COD pour l'utilisateur identifié par son mail
     * @param string $email l'adresse email de l'opérateur à rechercher
     * @return string|null le code identifiant de l'opérateur ou null si aucune correspondance n'est trouvée
    */
    public function getCodeOperateur(string $email)
    {
        $conn = $this->doctrine->getConnection('oracle');

        $sql = "SELECT representants.PERCOMM_COD as ID,representants.PERCOMM_LIB as nom,infos.EMAIL as email 
                  FROM 
                  ERP_SOT.FDV_PERCOMM representants, 
                  ERP_SOT.GEN_ADRESSE infos 
                  WHERE 
                  infos.ADRESSE_ID = representants.ADRESSE_ID  and infos.EMAIL=:email";

        $codeOperateur = $conn->executeQuery($sql, ['email' => $email])->fetchAssociative();

        return $codeOperateur['ID'] ?? null;
    }
}

