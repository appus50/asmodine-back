<?php

namespace Asmodine\AdminBundle\Repository;

/**
 * Class StyleRepository.
 */
class StyleRepository extends AbstractSynonymAssociationRepository
{
    /**
     * Associate with synonyms.
     * Search synonyms in name and in description (by model and by product).
     */
    public function runSynonymAssociation(): void
    {
        $tables = ['model', 'product'];
        $fields = ['name', 'description_short', 'description'];

        $associationFunc = function ($table) use ($fields) {
            array_walk($fields, [$this, 'associateSynonym'], $table);
        };
        array_map($associationFunc, $tables);
    }

    /**
     * Associate with model or product and field.
     *
     * @param string $field
     * @param int    $key   (unsed)
     * @param string $table
     */
    private function associateSynonym(string $field, int $key, string $table): void
    {
        $tmpTable = 'tmp_back_synonym_style';
        $this->dropTable($tmpTable, true);

        $sql = "CREATE TEMPORARY TABLE `{$tmpTable}` "
            .' (INDEX `priority_idx` (`priority`), INDEX `ids_idx` (`external_id`, `id`), INDEX `id_idx` (`id`))'
            .' AS SELECT c.priority AS max_priority, c.* FROM ( '
            ."SELECT synonym.priority, synonym.external_id, model.id FROM `{$this->getSynonymAssociationTable()}` AS model ";
        if ('model' == $table) {
            $sql .= "LEFT JOIN `back_utils_synonyms` AS synonym ON LOWER({$table}.{$field}) LIKE CONCAT('%', synonym.value, '%') ";
        }
        if ('product' == $table) {
            $sql .= 'INNER JOIN `back_product` AS product ON product.model_id = model.id ';
            $sql .= "LEFT JOIN `back_utils_synonyms` AS synonym ON LOWER({$table}.{$field}) LIKE CONCAT('%', synonym.value, '%') ";
        }
        $sql .= ' INNER JOIN `back_style_category_asmodine` AS style ON synonym.external_id = style.id '
            .'    INNER JOIN `back_category_asmodine` AS category_a ON style.category_asmodine_id = category_a.id '
            ."    WHERE synonym.type ='style' "
            .'      AND model.style_id IS NULL '
            ."      AND {$table}.{$field} IS NOT NULL"
            .'      AND model.category_id IN (SELECT category_b.id FROM `back_category_brand` AS category_b WHERE category_b.category_asmodine_id = category_a.id) '
            .'    GROUP BY synonym.priority, synonym.external_id, model.id'
            .'    ORDER BY synonym.priority DESC) AS c'
            .'  GROUP BY c.external_id, c.id';
        $this->execute($sql);
        $this->findBestMatch($tmpTable);
        $this->updateSynonymAssociationTable($tmpTable);
        $this->dropTable($tmpTable, true);
    }

    /**
     * Returns the name of the table to which to apply the synonyms.
     *
     * @return string
     */
    public function getSynonymAssociationTable(): string
    {
        return 'back_model';
    }

    /**
     * Returns the name of the column to which to apply the synonyms.
     *
     * @return string
     */
    public function getSynonymAssociationIdColumn(): string
    {
        return 'style_id';
    }
}
