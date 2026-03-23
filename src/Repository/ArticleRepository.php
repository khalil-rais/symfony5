<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\User;

/**
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * @return Article[]
     */
    public function findAllPublishedOrderedByNewest()
    {
        return $this->addIsPublishedQueryBuilder()
            ->leftJoin('a.tags', 't')
            ->addSelect('t')
            ->orderBy('a.publishedAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?Article
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    private function addIsPublishedQueryBuilder(QueryBuilder $qb = null)
    {
        return $this->getOrCreateQueryBuilder($qb)
            ->andWhere('a.publishedAt IS NOT NULL');
    }

    private function getOrCreateQueryBuilder(QueryBuilder $qb = null)
    {
        return $qb ?: $this->createQueryBuilder('a');
    }

    /**
     * @return Article[]
     */
    public function findAllPublishedLastWeekByAuthor(User $author): array
    {
        /*
            Open up ArticleRepository and add a new method for this -
            findAllPublishedLastWeekByAuthor() - with a single argument: the User object.
            This will return an array of articles:
            let's advertise that above.
            The query itself is pretty simple:
            return $this->createQueryBuilder() with ->andWhere('a.author = :author)
            to limit to only this author -
            we'll set the :author parameter in a second - then ->andWhere('a.publishedAt > :week_ago').
            For the placeholders, call setParameter() to set author to the $author variable,
            and ->setParameter() again to set week_ago to a new \DateTime('-1 week').
            Finish with the normal ->getQuery() and ->getResult().
         */
        return $this->createQueryBuilder('a')
            ->andWhere('a.author = :author')
            ->andWhere('a.publishedAt > :week_ago')
            ->setParameter('author', $author)
            ->setParameter('week_ago', new \DateTime('-1 week'))
            ->getQuery()
            ->getResult();
    }
}
