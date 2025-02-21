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
