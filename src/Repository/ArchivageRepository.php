<?php

namespace App\Repository;

use App\Entity\Archivage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Archivage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Archivage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Archivage[]    findAll()
 * @method Archivage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArchivageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Archivage::class);
    }

    // /**
    //  * @return Archivage[] Returns an array of Archivage objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Archivage
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
