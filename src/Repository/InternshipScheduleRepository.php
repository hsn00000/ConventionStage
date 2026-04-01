<?php

namespace App\Repository;

use App\Entity\InternshipSchedule;
use App\Entity\Level;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InternshipSchedule>
 */
class InternshipScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InternshipSchedule::class);
    }

    /**
     * @return InternshipSchedule[]
     */
    public function findActiveForLevel(Level $level): array
    {
        return $this->createQueryBuilder('schedule')
            ->leftJoin('schedule.internshipDates', 'internshipDate')
            ->addSelect('internshipDate')
            ->andWhere('schedule.level = :level')
            ->andWhere('schedule.isActive = true')
            ->setParameter('level', $level)
            ->orderBy('schedule.name', 'ASC')
            ->addOrderBy('internshipDate.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return InternshipSchedule[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('schedule')
            ->leftJoin('schedule.level', 'level')
            ->leftJoin('schedule.internshipDates', 'internshipDate')
            ->addSelect('level', 'internshipDate')
            ->orderBy('schedule.isActive', 'DESC')
            ->addOrderBy('schedule.name', 'ASC')
            ->addOrderBy('level.levelName', 'ASC')
            ->addOrderBy('internshipDate.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
