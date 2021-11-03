<?php
namespace Service;
use Doctrine\ORM\Tools\Pagination\Paginator;
class Paginate
{
    public function make(int $pageSize,$query,$thisPage): array
    {
        $paginator = new Paginator($query);
        $totalItems = count($paginator);
        $pagesCount = ceil($totalItems / $pageSize);
        $paginator
            ->getQuery()
            ->setFirstResult($pageSize * ($thisPage-1)) // set the offset
            ->setMaxResults($pageSize);
        return [$paginator,$pagesCount];
    }
}