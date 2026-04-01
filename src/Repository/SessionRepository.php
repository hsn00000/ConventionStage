<?php

namespace App\Repository;

use App\Entity\Level;
use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Session>
 */
class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    /**
     * @return Session[]
     */
    public function findActiveForLevel(Level $level): array
    {
        return $this->createQueryBuilder('session')
            ->leftJoin('session.sessionDates', 'sessionDate')
            ->addSelect('sessionDate')
            ->andWhere('session.level = :level')
            ->andWhere('session.isActive = true')
            ->setParameter('level', $level)
            ->orderBy('session.name', 'ASC')
            ->addOrderBy('sessionDate.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Session[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('session')
            ->leftJoin('session.level', 'level')
            ->leftJoin('session.sessionDates', 'sessionDate')
            ->addSelect('level', 'sessionDate')
            ->orderBy('session.isActive', 'DESC')
            ->addOrderBy('session.name', 'ASC')
            ->addOrderBy('level.levelName', 'ASC')
            ->addOrderBy('sessionDate.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Session[] Returns an array of Session objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Session
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
