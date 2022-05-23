<?php

namespace Asmodine\AdminBundle\Repository;

interface SynonymAssociationRepositoryInterface
{
    /**
     * Return nb of elements without association.
     *
     * @return int
     */
    public function getNbRowsWithoutAssociation(): int;

    /**
     * Associate with synonyms.
     */
    public function runSynonymAssociation(): void;

    /**
     * Returns the name of the table to which to apply the synonyms.
     *
     * @return string
     */
    public function getSynonymAssociationTable(): string;

    /**
     * Returns the name of the column to which to apply the synonyms.
     *
     * @return string
     */
    public function getSynonymAssociationIdColumn(): string;

    /**
     * Count all elements in table.
     *
     * @return int
     */
    public function getNbRows(): int;
}
