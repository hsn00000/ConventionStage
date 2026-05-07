<?php

namespace App\Repository;

use App\Entity\Professor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Professor>
 */
class ProfessorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Professor::class);
    }

    /**
     * @return Professor[]
     */
    public function findApproved(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isApprovedByAdmin = true')
            ->orderBy('p.lastname', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Professor[]
     */
    public function findPendingApproval(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isApprovedByAdmin = false')
            ->andWhere('p.isVerified = true')
            ->orderBy('p.lastname', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
