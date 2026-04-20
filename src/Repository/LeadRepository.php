<?php

namespace App\Repository;

use App\Entity\Lead;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\DBAL\ParameterType;

/**
 * Repository gérant l'accès aux données de l'entité Lead.
 * Contient les requêtes DQL (QueryBuilder) et SQL natives pour les statistiques.
 */
class LeadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lead::class);
    }

    /**
     * Cette méthode permet de compter le nombre de leads pour une équipe de commerciaux
     * * Utilise le QueryBuilder pour filtrer par une liste d'identifiants (IN)
     * Applique des critères de filtrage dynamiques (statuts, sources, etc.)
     * @param array $identifiers Liste des codes commerciaux de l'équipe
     * @param array $criteria Filtres additionnels (champ => valeur)
     * @return int Nombre total de leads trouvés
     */
    public function countLeadsForTeam(array $identifiers, array $criteria = []): int
    {
        if (empty($identifiers)) {
            return 0;
        }

        $qb = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)');

        $qb->where('l.interlocuteur_id IN (:identifiers)')
           ->setParameter('identifiers', $identifiers);

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $qb->andWhere("l.$field IN (:$field)")
                   ->setParameter($field, $value);
            } else {
                $qb->andWhere("l.$field = :$field")
                   ->setParameter($field, $value);
            }
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Cette méthode permet de compter tous les leads en base via une requête SQL native
     * * Construit dynamiquement la clause WHERE en fonction des filtres fournis
     * Gère les paramètres sous forme de tableaux (IN) ou de valeurs simples (=)
     * @param array $filtre Tableau de critères de filtrage
     * @return int Nombre total de leads
     */
    public function countAllLeads(array $filtre = []): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $where = " WHERE 1=1"; 
        $params = [];

        foreach ($filtre as $field => $value) {
            if (is_array($value)) {
                $placeholders = [];
                foreach ($value as $i => $val) {
                    $paramName = $field . '_' . $i;
                    $placeholders[] = ':' . $paramName;
                    $params[$paramName] = $val;
                }
                $where .= " AND " . $field . " IN (" . implode(', ', $placeholders) . ")";
            } else {
                $where .= " AND " . $field . " = :" . $field;
                $params[$field] = $value;
            }
        }

        $sql = "SELECT COUNT(id) FROM lead " . $where;
        return (int) $conn->executeQuery($sql, $params)->fetchOne();
    }

    /**
     * Cette méthode permet de compter les leads par mois pour un commercial précis
     * * Groupe les résultats par numéro de mois (1 à 12)
     * @param mixed $commercial Code identifiant ou objet User
     * @param int $year L'année cible pour les statistiques
     * @return array Tableau de résultats [{monthNumber, count}, ...]
     */
    public function countLeadsByMonth($commercial, int $year): array
    {
        if ($commercial === null) { return []; }
        $code = (is_string($commercial) || is_int($commercial)) ? (string)$commercial : $commercial->getIdentifier();
        
        return $this->createQueryBuilder('l')
            ->select('MONTH(l.dateCreation) as monthNumber, COUNT(l.id) as count')
            ->where('l.interlocuteur_id = :code')
            ->andWhere('YEAR(l.dateCreation) = :year') 
            ->setParameter('code', $code)
            ->setParameter('year', $year) 
            ->groupBy('monthNumber')
            ->orderBy('monthNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cette méthode permet de compter tous les leads de la base par mois
     * @param int $year L'année cible
     * @return array Répartition mensuelle globale
     */
    public function countAllLeadsByMonth(int $year): array
    {
        return $this->createQueryBuilder('l')
            ->select('MONTH(l.dateCreation) as monthNumber, COUNT(l.id) as count')
            ->where('YEAR(l.dateCreation) = :year') 
            ->setParameter('year', $year) 
            ->groupBy('monthNumber')
            ->orderBy('monthNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cette méthode permet de compter les leads par mois pour une équipe entière
     * * Inclut également les leads sans interlocuteur assigné (NULL)
     * @param array $teamIds Liste des codes de l'équipe
     * @param int $year L'année cible
     * @return array Répartition mensuelle d'équipe
     */
    public function countLeadsByMonthForTeam(array $teamIds, int $year): array
    {
        return $this->createQueryBuilder('l')
            ->select('MONTH(l.dateCreation) as monthNumber, COUNT(l.id) as count')
            ->where('(l.interlocuteur_id IN (:team) OR l.interlocuteur_id IS NULL)')
            ->andWhere('YEAR(l.dateCreation) = :year')
            ->setParameter('team', $teamIds ?: [0])
            ->setParameter('year', $year)
            ->groupBy('monthNumber')
            ->orderBy('monthNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cette méthode utilitaire applique des filtres de statut ou de source à une requête
     * * Mappe les mots-clés (pending, processed...) vers les codes numériques en base
     * @param mixed $valeur L'instance du QueryBuilder à modifier
     * @param string $type Le type de filtre à appliquer
     */
    public function TypeFiltre($valeur, string $type): void
    {
        $statuts = ['pending' => '0', 'closed' => '2', 'processed' => '1', 'direct' => '4', 'random' => '3'];
        $sources = ['Sothoferm' => '1', 'Lumis' => '2', 'Bati' => '3'];

        if (isset($statuts[$type])) {
            $valeur->andWhere('l.statut = :s')->setParameter('s', $statuts[$type]);
        } elseif (isset($sources[$type])) {
            $valeur->andWhere('l.source = :src')->setParameter('src', $sources[$type]);
        }
    }

    /**
     * Cette méthode récupère la liste des leads pour un groupe d'utilisateurs
     * * Gère le cas particulier du statut "fermé" regroupant plusieurs codes
     * @param array $ids Liste des identifiants
     * @param string $type Type de filtre (statut ou source)
     * @return array Liste d'objets Lead
     */
    public function findLeadsByUser(array $ids, string $type): array
    {
        if (empty($ids)) { return []; }
        $a = $this->createQueryBuilder('l');
        $a->where('l.interlocuteur_id IN (:ids)')->setParameter('ids', $ids);

        if ($type === 'ferme') {
            $a->andWhere('l.statut IN (:statuts)')->setParameter('statuts', ['1', '2','3','4']);
        } else {
            $this->TypeFiltre($a, $type);
        }
        return $a->orderBy('l.dateCreation', 'DESC')->getQuery()->getResult();
    }

    /**
     * Cette méthode détermine quels leads afficher selon le rôle de l'utilisateur
     * * Si ATC et pas de filtre équipe : voit tout. Sinon : voit son périmètre équipe.
     * @param UserInterface $user L'utilisateur connecté
     * @param array $teamCodes Codes de l'équipe associée
     * @param string $type Filtre de statut/source
     */
    public function findLeadsForUser(UserInterface $user, array $teamCodes, string $type): array
    {
        $identifier = $user->getUserIdentifier();
        $roles = $user->getRoles();
        $filterIdentifiers = array_unique(array_merge([$identifier], $teamCodes));

        if (in_array('ROLE_ATC', $roles, true) && empty($teamCodes)) {
            return $this->findAllLeadsByType($type);
        }
        return $this->findLeadsByUser($filterIdentifiers, $type);
    }

    /**
     * Cette méthode interne récupère tous les leads de la base filtrés par type
     */
    private function findAllLeadsByType(string $type): array
    {
        $qb = $this->createQueryBuilder('l');
        if ($type === 'ferme') {
            $qb->andWhere('l.statut IN (:statuts)')->setParameter('statuts', ['1', '2', '3', '4']);
        } elseif ($type !== 'all') {
            $this->TypeFiltre($qb, $type);
        }
        return $qb->orderBy('l.dateCreation', 'DESC')->getQuery()->getResult();
    }

    /**
     * Cette méthode calcule le volume d'orientations par client et par mois pour un commercial
     * * Nettoie les données (TRIM, UPPER) et extrait les 5 premiers caractères du code client
     * @return array Matrice [code_client][mois] => nombre
     */
    public function countAllOrientationsByMonth($commercialId, int $annee = null): array
    {
        if ($annee === null) { $annee = (int)date('Y'); }
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT 
                LEFT(UPPER(TRIM(l.orientation_client)), 5) AS code_client,
                MONTH(l.date_creation) AS mois,
                COUNT(*) AS nb_leads
            FROM lead l
            WHERE l.interlocuteur_id = :commercialId
            AND l.statut = '1'
            AND l.orientation_client IS NOT NULL
            AND l.orientation_client != ''
            AND YEAR(l.date_creation) = :annee
            GROUP BY code_client, mois
            ORDER BY code_client ASC, mois ASC";
        
        $result = $conn->executeQuery($sql, ['commercialId' => (string)$commercialId, 'annee' => $annee])->fetchAllAssociative();
        
        $stats = [];
        foreach ($result as $row) {
            $codeClient = $row['code_client']; 
            if (!isset($stats[$codeClient])) {
                $stats[$codeClient] = array_fill(1, 12, 0);
            }
            $stats[$codeClient][(int)$row['mois']] = (int)$row['nb_leads'];
        }
        return $stats;
    }

    /**
     * Cette méthode compte le total annuel d'orientations pour un commercial
     */
    public function countAllOrientationsByYear($commercialId, int $annee = null): int
    {
        if ($annee === null) { $annee = (int)date('Y'); }
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT COUNT(*) FROM lead WHERE interlocuteur_id = :commercialId AND statut = '1' AND orientation_client IS NOT NULL AND orientation_client != '' AND YEAR(date_creation) = :annee";
        return (int)$conn->fetchOne($sql, ['commercialId' => $commercialId, 'annee' => $annee]);
    }

    /**
     * Cette méthode liste les codes et noms des clients déjà orientés par un commercial
     */
    public function getClientsOrientes($commercialId): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                LEFT(UPPER(TRIM(orientation_client)), 5) as code,
                MAX(UPPER(TRIM(orientation_client))) as nom_complet
            FROM lead
            WHERE interlocuteur_id = :commercialId
            AND statut = '1'
            AND orientation_client IS NOT NULL 
            AND orientation_client != ''
            GROUP BY LEFT(UPPER(TRIM(orientation_client)), 5)
            ORDER BY nom_complet ASC";
        return $conn->executeQuery($sql, ['commercialId' => (string) $commercialId])->fetchAllAssociative();
    }

    /**
     * Cette méthode récupère le Top X des clients les plus sollicités par un commercial
     */
    public function getTopOrientationsClient($commercial, int $limit = 10): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT 
                LEFT(UPPER(TRIM(l.orientation_client)), 5) AS code_client,
                COUNT(*) AS nb_orientations
            FROM lead l
            WHERE l.interlocuteur_id = :commercial
            AND l.statut = '1'
            AND l.orientation_client IS NOT NULL
            AND l.orientation_client != ''
            GROUP BY code_client
            ORDER BY nb_orientations DESC
            LIMIT :limit";
        return $conn->executeQuery($sql, ['commercial' => (string)$commercial, 'limit' => $limit], ['limit' => ParameterType::INTEGER])->fetchAllAssociative();
    }

    /**
     * Cette méthode récupère le Top global des orientations (tous commerciaux confondus)
     * * Utilise des expressions régulières pour extraire proprement les codes numériques ou alphabétiques
     */
    public function getTopOrientationsClientAll(int $limit = 10): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT 
                CASE 
                    WHEN l.orientation_client REGEXP '^[0-9]+'
                    THEN REGEXP_SUBSTR(l.orientation_client, '^[0-9]+')
                    WHEN l.orientation_client REGEXP '^[A-Z]+'
                    THEN REGEXP_SUBSTR(l.orientation_client, '^[A-Z]+')
                    ELSE TRIM(l.orientation_client)
                END AS code_client,
                COUNT(DISTINCT l.id) AS nb_orientations
            FROM lead l
            WHERE l.statut = '1'
            AND l.orientation_client IS NOT NULL
            AND l.orientation_client != ''
            AND TRIM(l.orientation_client) != ''
            GROUP BY code_client
            ORDER BY nb_orientations DESC
            LIMIT :limit
        ";
        
        return $conn->executeQuery(
            $sql, 
            ['limit' => $limit], 
            ['limit' => ParameterType::INTEGER]
        )->fetchAllAssociative();
    }

    /**
     * Cette méthode compte les orientations mensuelles pour un couple (commercial, client) spécifique
     */
    public function countOrientationsByClientByMonth($commercialId, string $codeClient, int $annee = null): array
    {
        if ($annee === null) { $annee = (int)date('Y'); }
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT MONTH(l.date_creation) AS mois, COUNT(*) AS nb_leads
            FROM lead l
            WHERE l.interlocuteur_id = :commercialId
            AND LEFT(UPPER(TRIM(l.orientation_client)), 5) = :codeClient
            AND l.statut = '1'
            AND YEAR(l.date_creation) = :annee
            GROUP BY MONTH(l.date_creation)
            ORDER BY mois ASC";
        
        $result = $conn->executeQuery($sql, ['commercialId' => $commercialId, 'codeClient' => substr(strtoupper(trim($codeClient)), 0, 5), 'annee' => $annee])->fetchAllAssociative();
        $stats = array_fill(1, 12, 0);
        foreach ($result as $row) { $stats[(int)$row['mois']] = (int)$row['nb_leads']; }
        return $stats;
    }

    /**
     * Cette méthode compte le total annuel d'orientations pour un couple (commercial, client) spécifique
     */
    public function countOrientationsByClientByYear($commercialId, string $codeClient, int $annee = null): int
    {
        if ($annee === null) { $annee = (int)date('Y'); }
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT COUNT(*) FROM lead WHERE interlocuteur_id = :commercialId AND LEFT(UPPER(TRIM(orientation_client)), 5) = :codeClient AND statut = '1' AND YEAR(date_creation) = :annee";
        return (int)$conn->fetchOne($sql, ['commercialId' => $commercialId, 'codeClient' => substr(strtoupper(trim($codeClient)), 0, 5), 'annee' => $annee]);
    }


    /**
     * Cette méthode identifie les leads en attente (statut 0) depuis trop longtemps pour un commercial
     * * Utilisé par le RelanceController pour l'envoi d'emails automatiques
     * @param \DateTime $dateLimit Seuil temporel (ex: 24h)
     * @param string $commercialCode Code du commercial à relancer
     * @return array Liste des leads à traiter en priorité
     */
    public function findLeadsNonTraitesDepuisPourCommercial(\DateTime $dateLimit, string $commercialCode): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.statut = :statut')
            ->andWhere('l.dateCreation <= :dateLimit')
            ->andWhere('l.interlocuteur_id = :commercial')
            ->setParameter('statut', '0')
            ->setParameter('dateLimit', $dateLimit)
            ->setParameter('commercial', $commercialCode)
            ->orderBy('l.dateCreation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cette méthode liste l'intégralité des clients orientés dans la base
     * * Utilisé par l'ATC pour avoir une vue d'ensemble sans filtre commercial
     */
    public function getClientsOrientesAll(): array
{
    $conn = $this->getEntityManager()->getConnection();

    $sql = "
        SELECT
            LEFT(UPPER(TRIM(l.orientation_client)), 5) as code,
            MAX(UPPER(TRIM(l.orientation_client))) as nom_complet
        FROM lead l
        WHERE l.statut = '1'
        AND l.orientation_client IS NOT NULL
        AND l.orientation_client != ''
        GROUP BY code
        ORDER BY nom_complet ASC
    ";

    $result = $conn->fetchAllAssociative($sql);

    $clients = [];
    foreach ($result as $row) {
        $clients[$row['code']] = $row['nom_complet'];
    }

    return $clients;
}

    /**
     * Récupère les statistiques des leads orientés vers les clients par un commercial,
     * par mois et par année. Pour les années passées : janvier à décembre.
     * Pour l'année en cours : janvier au mois actuel.
     *
     * @param string   $numCommercial Code du commercial (ex: "006")
     * @param array    $clients       Liste des codes clients à inclure
     * @param int|null $anneeDebut    Première année (défaut: année courante - 1)
     * @param int|null $anneeFin      Dernière année (défaut: année courante)
     * @return array [codeClient => [annee => [mois => nb_leads]]]
     */
    public function getStatsOrientationsByCommercialAndClients(
        string $numCommercial,
        array $clients,
        ?int $anneeDebut = null,
        ?int $anneeFin = null
    ): array {
        if (empty($clients)) {
            return [];
        }

        $now = new \DateTime();
        $anneeDebut = $anneeDebut ?? ($now->format('Y') - 1);
        $anneeFin = $anneeFin ?? (int) $now->format('Y');
        $moisCourant = (int) $now->format('n');

        $conn = $this->getEntityManager()->getConnection();

        $clientsNormalises = array_map(fn (string $c) => substr(strtoupper(trim($c)), 0, 5), $clients);
        $clientsNormalises = array_values(array_unique($clientsNormalises));
        $placeholders = implode(',', array_fill(0, count($clientsNormalises), '?'));

        $sql = "
            SELECT 
                LEFT(UPPER(TRIM(l.orientation_client)), 5) AS code_client,
                YEAR(l.date_creation) AS annee,
                MONTH(l.date_creation) AS mois,
                COUNT(*) AS nb_leads
            FROM lead l
            WHERE l.interlocuteur_id = ?
            AND l.statut = '1'
            AND l.orientation_client IS NOT NULL
            AND l.orientation_client != ''
            AND LEFT(UPPER(TRIM(l.orientation_client)), 5) IN ($placeholders)
            AND YEAR(l.date_creation) >= ?
            AND YEAR(l.date_creation) <= ?
            GROUP BY LEFT(UPPER(TRIM(l.orientation_client)), 5), YEAR(l.date_creation), MONTH(l.date_creation)
            ORDER BY code_client, annee, mois
        ";

        $params = array_merge([$numCommercial], $clientsNormalises, [$anneeDebut, $anneeFin]);

        $result = $conn->executeQuery($sql, $params)->fetchAllAssociative();

        $stats = [];
        foreach ($clientsNormalises as $code) {
            $stats[$code] = [];
            for ($a = $anneeDebut; $a <= $anneeFin; $a++) {
                $maxMois = ($a < (int) $now->format('Y')) ? 12 : $moisCourant;
                $stats[$code][(string) $a] = array_fill(1, $maxMois, 0);
            }
        }

        foreach ($result as $row) {
            $code = $row['code_client'];
            $annee = (string) $row['annee'];
            $mois = (int) $row['mois'];
            $nb = (int) $row['nb_leads'];

            if (!isset($stats[$code])) {
                continue;
            }
            $maxMois = ((int) $annee < (int) $now->format('Y')) ? 12 : $moisCourant;
            if ($mois <= $maxMois && isset($stats[$code][$annee][$mois])) {
                $stats[$code][$annee][$mois] = $nb;
            }
        }

        foreach ($stats as $code => $donnees) {
            $total = 0;
            $nbMois = 0;
            foreach ($donnees as $annee => $moisData) {
                foreach ($moisData as $nb) {
                    $total += $nb;
                    $nbMois++;
                }
            }
            $stats[$code]['total'] = $total;
            $stats[$code]['moyenne'] = $nbMois > 0 ? round($total / $nbMois, 2) : 0;
        }

        return $stats;
    }
}