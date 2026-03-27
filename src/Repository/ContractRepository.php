<?php

namespace App\Repository;

use App\Entity\Contract;
use App\Entity\Professor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contract>
 */
class ContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contract::class);
    }

    /**
     * @return Contract[]
     */
    public function findPendingProfessorValidation(Professor $professor): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.coordinator = :professor')
            ->andWhere('c.status = :status')
            ->setParameter('professor', $professor)
            ->setParameter('status', Contract::STATUS_VALIDATED_BY_STUDENT)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Contract[]
     */
    public function findPendingDdfValidation(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.status = :status')
            ->setParameter('status', Contract::STATUS_VALIDATED_BY_PROF)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Contract[]
     */
    public function findSignatureInProgress(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.status = :status')
            ->setParameter('status', Contract::STATUS_SIGNATURE_REQUESTED)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Contract[]
     */
    public function findSignedContracts(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.status = :status')
            ->setParameter('status', Contract::STATUS_SIGNED)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneBySignatureRequestId(string $signatureRequestId): ?Contract
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.yousignSignatureRequestId = :signatureRequestId')
            ->setParameter('signatureRequestId', $signatureRequestId)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return Contract[] Returns an array of Contract objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Contract
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
