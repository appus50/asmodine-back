<?php

namespace Asmodine\SizeAdvisorBundle\Repository;

use Asmodine\CommonBundle\Model\Morphoprofile\Morphoprofile;
use Asmodine\CommonBundle\Model\Morphoprofile\Size;
use Asmodine\CommonBundle\Model\Morphoprofile\Weight;
use Doctrine\DBAL\Statement;

/**
 * Class NoteStyleRepository.
 */
class NoteStyleRepository extends AbstractNoteRepository
{
    /**
     *  Drop and create table.
     *
     * @return Statement
     */
    public function createTable(): Statement
    {
        $this->dropTable('advisor_note_style');

        return $this->execute(
            "CREATE TABLE `advisor_note_style` (
                `product_id` INT UNSIGNED NOT NULL,
                `size` ENUM('".implode("','", Size::getSlugs())."') NOT NULL,
                `morphoprofile` ENUM('".implode("','", Morphoprofile::getSlugs())."') NOT NULL,
                `morpho_weight` ENUM('".implode("','", Weight::getSlugs())."') NOT NULL,
                `note` TINYINT(1) NOT NULL,
                                                
                PRIMARY KEY (`product_id`, `size`, `morphoprofile`, `morpho_weight`),
                INDEX `morpho_idx` (`size`,`morphoprofile`,`morpho_weight`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    /**
     * Calculates all datas.
     *
     * @return Statement
     */
    public function loadAll(): Statement
    {
        return $this->execute(
            'INSERT IGNORE INTO `advisor_note_style` (`product_id`, `size`,  `morphoprofile`, `morpho_weight`, `note`)
              SELECT p.id, s.size, s.morphoprofile, s.morpho_weight, s.note
              FROM `back_product` AS p
              INNER JOIN `back_model` AS m ON p.model_id = m.id
              INNER JOIN `back_style_morphology` AS s ON m.style_id = s.style_asmodine_id
              WHERE m.style_id IS NOT NULL AND m.is_enabled_auto = TRUE AND m.is_enabled_manual = true'
        );
    }

    /**
     * @see AbstractAsmodineRepository::count()
     *
     * @return int
     */
    public function countRows(): int
    {
        return $this->count('advisor_note_style', true, ['product_id', 'size', 'morphoprofile', 'morpho_weight']);
    }
}
