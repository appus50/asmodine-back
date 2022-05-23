<?php

namespace Asmodine\SizeAdvisorBundle\Repository;

use Doctrine\DBAL\Statement;

/**
 * Interface NoteRepositoryInterface.
 */
interface NoteRepositoryInterface
{
    /**
     * Drop and create table.
     *
     * @return Statement
     */
    public function createTable(): Statement;

    /**
     * Calculates all datas.
     *
     * @return Statement
     */
    public function loadAll(): Statement;

    /**
     * @see AbstractAsmodineRepository::count()
     *
     * @return int
     */
    public function countRows(): int;
}
