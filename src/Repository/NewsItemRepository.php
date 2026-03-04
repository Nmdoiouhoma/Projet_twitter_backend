<?php

namespace App\Repository;

use App\Entity\NewsItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsItem>
 */
class NewsItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsItem::class);
    }

    public function findPublishedOrderedByDate(?int $limit = null, ?int $offset = null): array
{
    $qb = $this->createQueryBuilder('n');
    $qb
        ->andWhere('n.isPublished = :published')
        ->setParameter('published', true)
        ->orderBy('n.publishedAt', 'DESC') // Le plus récent en premier
    ;
    // ... logique de pagination ...
    return $qb->getQuery()->getResult();
}

public function findPopularPublishedLast3Days(?int $limit = null, ?int $offset = null): array
{
    $qb = $this->createQueryBuilder('n');
    $threeDaysAgo = new \DateTimeImmutable('-3 days');

    $qb
        ->andWhere('n.isPublished = :published')
        ->setParameter('published', true)
        ->andWhere('n.publishedAt >= :threeDaysAgo')
        ->setParameter('threeDaysAgo', $threeDaysAgo)
        ->orderBy('n.likesCount', 'DESC') // Le plus liké en premier
        // Vous pourriez aussi vouloir trier par publishedAt comme tri secondaire si les likes sont égaux
        // ->addOrderBy('n.publishedAt', 'DESC')
    ;
    // ... logique de pagination ...
    return $qb->getQuery()->getResult();
}


public function findNewsItemsForUserFollowing(\App\Entity\User $currentUser, ?int $limit = null, ?int $offset = null): array
{
    // Ceci suppose que votre entité User a une propriété 'following' qui est une Collection d'Utilisateurs
    // Si vous avez une entité 'Follow' séparée, la requête devra être ajustée.
    $qb = $this->createQueryBuilder('n');
    $qb
        ->innerJoin('n.author', 'u') // Jointure avec l'entité User (auteur)
        ->where($qb->expr()->in('u.id', ':following')) // Vérifie si l'ID de l'auteur est dans la liste des utilisateurs suivis
        ->setParameter('following', $currentUser->getFollowing()->map(fn($user) => $user->getId())->toArray())
        ->andWhere('n.isPublished = :published')
        ->setParameter('published', true)
        ->orderBy('n.publishedAt', 'DESC'); // Ordre chronologique pour les posts des utilisateurs suivis

    // ... logique de pagination ...
    return $qb->getQuery()->getResult();
}

//    /**
//     * @return NewsItem[] Returns an array of NewsItem objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('n.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?NewsItem
//    {
//        return $this->createQueryBuilder('n')
//            ->andWhere('n.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }


    public function save(NewsItem $entity, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($entity);

        if ($flush) {
            $em->flush();
        }
    }

    public function remove(NewsItem $entity, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->remove($entity);

        if ($flush) {
            $em->flush();
        }
    }
}
