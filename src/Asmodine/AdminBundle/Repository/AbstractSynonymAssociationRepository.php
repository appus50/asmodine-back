<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;
use Doctrine\DBAL\Statement;

abstract class AbstractSynonymAssociationRepository extends AbstractAsmodineRepository implements SynonymAssociationRepositoryInterface
{
    /**
     * Return nb of entity without association.
     *
     * @return int
     */
    public function getNbRowsWithoutAssociation(): int
    {
        $statement = $this->execute(
            'SELECT COUNT(id) AS nb'
            .' FROM '.$this->getSynonymAssociationTable()
            .' USE INDEX (PRIMARY)'
            .' WHERE '.$this->getSynonymAssociationIdColumn().' IS NULL'
        );
        $row = $statement->fetch();

        return $row['nb'];
    }

    /**
     * Count all elements in table.
     *
     * @return int
     */
    public function getNbRows(): int
    {
        return $this->count($this->getSynonymAssociationTable(), true);
    }

    /**
     * Search for the best match.
     *
     * @param string $tableName
     */
    protected function findBestMatch(string $tableName): void
    {
        // Update Max Priority
        $this->execute(
            "UPDATE `{$tableName}` AS t0 INNER JOIN ("
            .'  SELECT id, MAX(priority) AS max_priority'
            ."  FROM `{$tableName}`"
            .'  GROUP BY id'
            .' ) AS t1'
            .' ON t0.id = t1.id'
            .' SET t0.max_priority = t1.max_priority'
        );
        // Keep only max priority
        $this->execute("DELETE FROM `{$tableName}` WHERE priority != max_priority");

        $this->dropTable($tableName.'_step1');
        $this->dropTable($tableName.'_step2');
        // Keep only a pair of value id,external_id
        $this->execute(
            "CREATE TEMPORARY TABLE `{$tableName}_step1` "
            .' (INDEX `full_idx` (`id`, `external_id`),  INDEX `id_idx` (`id`))'
            .'  SELECT `id`, `external_id`'
            ."  FROM  `{$tableName}`"
            .'  GROUP BY `id`, `external_id`'
        );
        // Get and delete indeterminate values
        $this->execute(
            "CREATE TEMPORARY TABLE `{$tableName}_step2`"
            .' (INDEX `id_idx` (`id`))'
            .'  SELECT `id`'
            ."  FROM  `{$tableName}_step1`"
            .'  GROUP BY `id`'
            .'  HAVING COUNT(*) > 1'
        );
        $this->execute("DELETE FROM `{$tableName}` WHERE id IN (SELECT id FROM `{$tableName}_step2`)");
        $this->dropTable($tableName.'_step1');
        $this->dropTable($tableName.'_step2');
    }

    /**
     * Update Brand Table with synonyms found.
     *
     * @param string $tmpTableName
     *
     * @return Statement
     */
    protected function updateSynonymAssociationTable(string $tmpTableName): Statement
    {
        return $this->execute(
            "UPDATE `{$this->getSynonymAssociationTable()}` AS i SET `{$this->getSynonymAssociationIdColumn()}` = ( "
            ."   SELECT t.external_id FROM `{$tmpTableName}` as t"
            .'   WHERE t.id = i.id'
            .'   GROUP BY t.id'
            .") WHERE i.{$this->getSynonymAssociationIdColumn()} IS NULL"
        );
    }
}
