<?php

namespace App\Repository;

use App\Entity\LeadArchived;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<LeadArchived>
 */
class LeadArchivedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LeadArchived::class);
    }

    /**
     * Applique les filtres de statut sur le QueryBuilder.
     * * Cette méthode centralise la correspondance entre les types textuels (pending, closed...) 
     * et les codes numériques stockés en base de données.
     * @param string $type Le libellé du type de filtre
     */
    private function applyTypeFiltre($qb, string $type): void
    {
        $statuts = [
            'pending'   => 0,
            'closed'    => 2,
            'processed' => 1,
            'direct'    => 4,
            'random'    => 3,
        ];

        if ($type === 'ferme') {
            $qb->andWhere('l.statut IN (:statuts)')
               ->setParameter('statuts', [1, 2, 3, 4]);
        } elseif (isset($statuts[$type])) {
            $qb->andWhere('l.statut = :s')
               ->setParameter('s', $statuts[$type]);
        }
    }

    /**
     * Recherche les leads archivés pour une liste d'identifiants commerciaux donnés.
     * * @param array $ids Liste des codes commerciaux (identifiants)
     * @param string $type Type de filtre de statut (pending, ferme, etc.)
     * @return array Liste des leads archivés trouvés
     */
    public function findLeadsByUser(array $ids, string $type): array
    {
        if (empty($ids)) {
            return [];
        }

        $qb = $this->createQueryBuilder('l')
            ->where('l.interlocuteur_id IN (:ids)')
            ->setParameter('ids', $ids);

        if ($type === 'ferme') {
            $qb->andWhere('l.statut IN (:statuts)')
               ->setParameter('statuts', [1, 2, 3, 4]);
        } elseif ($type !== 'all') {
            $this->applyTypeFiltre($qb, $type);
        }

        return $qb->orderBy('l.date_creation', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function findLeadsForUser(UserInterface $user, array $teamCodes, string $type): array
    {
        $identifier = $user->getUserIdentifier();
        $roles = $user->getRoles();
        
        $filterIdentifiers = [$identifier];
        
        foreach ($teamCodes as $code) {
            if (!in_array($code, $filterIdentifiers, true)) {
                $filterIdentifiers[] = $code;
            }
        }

        if (in_array('ROLE_ATC', $roles, true) && empty($teamCodes)) {
            return $this->findAllLeadsByType($type);
        }

        return $this->findLeadsByUser($filterIdentifiers, $type);
    }

    /**
     * Récupère l'intégralité des leads archivés sans filtre d'interlocuteur
     * * @param string $type Le filtre de statut à appliquer
     * @return array Liste de tous les leads archivés filtrés par statut
     */
    private function findAllLeadsByType(string $type): array
    {
        $qb = $this->createQueryBuilder('l');

        if ($type === 'ferme') {
            $qb->andWhere('l.statut IN (:statuts)')
               ->setParameter('statuts', [1, 2, 3, 4]);
        } elseif ($type !== 'all') {
            $this->applyTypeFiltre($qb, $type);
        }

        return $qb->orderBy('l.date_creation', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}