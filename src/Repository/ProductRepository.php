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
    public function paginatedSearch($term, $order, $page)
    {
        $offset = ($page - 1) * 5;

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.brand', 'brand')
            ->orderBy('p.model', $order)
            ->setFirstResult($offset)
            ->setMaxResults(5);
        if ($term != 'all') {
            $qb->where('brand.name LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        }
        /*
        return $qb;
        #return $this->paginate($qb, $limit, $offset);
        */

        $query = $qb->getQuery();
        return $query->getResult();
    }

    /**
     * @return int Returns the number of unpaginated products search
     */
    public function unpaginatedSearchCount(String $term)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('count(p.id)')
            ->leftJoin('p.brand', 'brand');
        if ($term != 'all') {
            $qb->where('brand.name LIKE :term')
                ->setParameter('term', '%' . $term . '%');
        }

        $query = $qb->getQuery();
        return (int) $query->getSingleScalarResult();
    }
}
