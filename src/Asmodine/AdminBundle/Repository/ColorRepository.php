<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\AdminBundle\Model\Color;
use Asmodine\CommonBundle\DTO\ColorDTO;
use Asmodine\CommonBundle\Exception\NotFoundEntityException;

/**
 * Class ColorRepository.
 */
class ColorRepository extends AbstractSynonymAssociationRepository
{
    /**
     * Insert new color.
     *
     * @param Color $color
     */
    public function insert(Color $color): void
    {
        if (is_null($color->getFilterSlug())) {
            $sql = 'INSERT INTO `back_color_asmodine` '
                .'(`name`, `hexa`) '
                .'VALUES (:colorname, :hexa)';

            $this->execute($sql, ['colorname' => $color->getName(), 'hexa' => $color->getHexa()]);
        }

        if (!is_null($color->getFilterSlug())) {
            $sql = 'INSERT INTO `back_color_asmodine` '
                .'(`name`, `hexa`, `color_filter_id`) '
                .'VALUES (:colorname, :hexa, (SELECT id FROM back_color_filter_asmodine WHERE slug = :slug))';

            $this->execute($sql, ['colorname' => $color->getName(), 'hexa' => $color->getHexa(), 'slug' => $color->getFilterSlug()]);
        }
    }

    /**
     * Find Color with its name.
     *
     * @param string $name
     *
     * @return ColorDTO
     *
     * @throws NotFoundEntityException
     */
    public function findOneByName(string $name): ColorDTO
    {
        $sql = 'SELECT * FROM `back_color_asmodine` WHERE `name` = :color_name';
        $stmt = $this->execute($sql, ['color_name' => $name]);
        if ($row = $stmt->fetch()) {
            return new ColorDTO($row);
        }
        throw new NotFoundEntityException('back_color_asmodine', 'name = '.$name);
    }

    /**
     * Associate with synonyms.
     */
    public function runSynonymAssociation(): void
    {
        $conditions = [
            ['where' => 's.priority >= 10', 'on' => "LOWER(i.name) LIKE CONCAT('%', s.value, '%')"],
            ['where' => 's.priority <= 10', 'on' => 'LOWER(i.name) = s.value'],
            ['where' => '1=1', 'on' => "LOWER(i.name) LIKE CONCAT(s.value, '%')"],
            ['where' => '1=1', 'on' => "LOWER(i.name) REGEXP CONCAT('^([a-zèêéëàùçô]+ )+(', s.value, ')')"],
        ];
        $tmpTable = 'tmp_back_synonym_color';

        $updateFun = function ($cond) use ($tmpTable) {
            $this->dropTable($tmpTable);

            // Search for matches
            $this->execute(
                "CREATE TEMPORARY TABLE `{$tmpTable}` "
                .' (INDEX `priority_idx` (`priority`), INDEX `ids_idx` (`external_id`, `id`))'
                .' AS SELECT c.priority AS max_priority, c.* FROM ( '
                .'    SELECT s.priority, s.external_id, i.id '
                ."      FROM `{$this->getSynonymAssociationTable()}` AS i "
                .'      LEFT JOIN `back_utils_synonyms` AS s '
                ."      ON {$cond['on']} WHERE {$cond['where']} AND s.type = 'color' AND i.color_asmodine_id IS NULL "
                .'      GROUP BY s.priority, s.external_id, i.id'
                .'      ORDER BY s.priority DESC) AS c'
                .'    GROUP BY c.external_id, c.id'
            );

            $this->findBestMatch($tmpTable);
            $this->updateSynonymAssociationTable($tmpTable);
            $this->dropTable($tmpTable, true);
        };
        array_map($updateFun, $conditions);
    }

    /**
     * Returns the name of the table to which to apply the synonyms.
     *
     * @return string
     */
    public function getSynonymAssociationTable(): string
    {
        return 'back_color_brand';
    }

    /**
     * Returns the name of the column to which to apply the synonyms.
     *
     * @return string
     */
    public function getSynonymAssociationIdColumn(): string
    {
        return 'color_asmodine_id';
    }
}
