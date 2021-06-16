<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    // /**
    //  * @return Product[] Returns an array of Product objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Product
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * @return Product[] Returns an array of Product objects
     */
    public function paginatedSearch($keyword, $order, $limit, $page)
    {
        $offset = ($page - 1) * $limit;

        $qBuilder = $this->createQueryBuilder('p')
            ->leftJoin('p.brand', 'brand')
            ->orderBy('p.model', $order)
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        if ($keyword != 'all') {
            $qBuilder->where('brand.name LIKE :keyword')
                ->setParameter('keyword', '%' . $keyword . '%');
        }

        $query = $qBuilder->getQuery();
        return $query->getResult();
    }

    /**
     * @return int Returns the number of unpaginated products search
     */
    public function unpaginatedSearchCount(String $keyword)
    {
        $qBuilder = $this->createQueryBuilder('p')
            ->select('count(p.id)')
            ->leftJoin('p.brand', 'brand');
        if ($keyword != 'all') {
            $qBuilder->where('brand.name LIKE :keyword')
                ->setParameter('keyword', '%' . $keyword . '%');
        }

        $query = $qBuilder->getQuery();
        return (int) $query->getSingleScalarResult();
    }
}
