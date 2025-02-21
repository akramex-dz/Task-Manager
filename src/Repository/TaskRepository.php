<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function searchByFilters(array $filters, string $sortField = 'id', string $sortDirection = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('t');

        // Appliquer les filtres de recherche
        if (!empty($filters['title'])) {
            $qb->andWhere('LOWER(t.title) LIKE :title')
                ->setParameter('title', '%' . strtolower($filters['title']) . '%');
        }

        if (!empty($filters['dueDate'])) {
            $qb->andWhere('t.dueDate = :dueDate')
                ->setParameter('dueDate', new \DateTime($filters['dueDate']));
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $filters['status']);
        }

        // Ajouter le tri par le champ spécifié
        $qb->orderBy('t.' . $sortField, $sortDirection);

        return $qb->getQuery()->getResult();
    }


    /**
     * Search tasks by title.
     *
     * @param string|null $query
     * @return Task[]
     */
    public function searchByTitle(?string $query): array
    {
        $qb = $this->createQueryBuilder('t');

        if ($query) {
            $qb->where('LOWER(t.title) LIKE :query')
                ->setParameter('query', '%' . strtolower($query) . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all tasks sorted by creation date.
     */
    public function findAllOrderedByDate()
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
