<?php

namespace Asmodine\SizeAdvisorBundle\Repository;

use Asmodine\CommonBundle\Model\Morphotype\Morphotype;
use Doctrine\DBAL\Statement;

/**
 * Class NoteColorRepository.
 */
class NoteColorRepository extends AbstractNoteRepository
{
    /**
     *  Drop and create table.
     *
     * @return Statement
     */
    public function createTable(): Statement
    {
        $this->dropTable('advisor_note_color');

        return $this->execute(
            "CREATE TABLE `advisor_note_color` (
                `product_id` INT UNSIGNED NOT NULL,
                `morphotype` ENUM('".implode("','", Morphotype::getSlugs())."') NOT NULL,
                `note` TINYINT(1) NOT NULL,
                                                
                PRIMARY KEY (`product_id`, `morphotype`),
                INDEX `morphotype_idx` (`morphotype`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    /**
     *  Calculates all datas.
     *
     * @return Statement
     */
    public function loadAll(): Statement
    {
        return $this->execute(
            'INSERT IGNORE INTO `advisor_note_color` (`product_id`, `morphotype`, `note`)
                SELECT p.id, c.morphotype, c.note
                FROM `back_product` AS p
                INNER JOIN `back_color_brand` AS b ON p.color_id = b.id
                INNER JOIN `back_color_morphotype` AS c ON b.color_asmodine_id = c.color_asmodine_id
                WHERE b.color_asmodine_id IS NOT NULL AND p.is_enabled_auto = TRUE AND p.is_enabled_manual = true
      '
        );
    }

    /**
     * @see AbstractAsmodineRepository::count()
     *
     * @return int
     */
    public function countRows(): int
    {
        return $this->count('advisor_note_color', true, ['product_id', 'morphotype']);
    }
}
