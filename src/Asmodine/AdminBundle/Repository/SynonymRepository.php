<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;

/**
 * Class SynonymRepository.
 */
class SynonymRepository extends AbstractAsmodineRepository
{
    /**
     * Basic Insert.
     *
     * @param string   $type
     * @param string   $columnOriginalName
     * @param string   $originalName
     * @param string   $synonymValue
     * @param int|null $synonymPriority
     */
    public function insert(string $type, string $columnOriginalName, string $originalName, string $synonymValue, int $synonymPriority): void
    {
        if ('style' == $type) {
            $type = 'style_category';
        }
        $sql = 'INSERT IGNORE INTO `back_utils_synonyms` '
            .'(`type`, `external_id`, `value`, `priority`) '
            .'VALUES (:type, (SELECT `id` FROM `back_'.$type.'_asmodine` WHERE `'.$columnOriginalName.'` = :original_name), :synonym_value, :synonym_priority)';

        $params = [
            'type' => $type,
            'original_name' => $originalName,
            'synonym_value' => $synonymValue,
            'synonym_priority' => $synonymPriority,
        ];
        $this->execute($sql, $params);
    }

    /**
     * Style Insert.
     *
     * @param string $styleName
     * @param int    $categoryId
     * @param string $synonymValue
     * @param int    $synonymPriority
     */
    public function insertStyle(string $styleName, int $categoryId, string $synonymValue, int $synonymPriority): void
    {
        $sql = 'INSERT IGNORE INTO `back_utils_synonyms` '
            .'(`type`, `external_id`, `value`, `priority`) '
            .'VALUES (:type, (SELECT `id` FROM `back_style_category_asmodine` WHERE `name` = :style_name AND `category_asmodine_id` = :category_id), :synonym_value, :synonym_priority)';

        $params = [
            'type' => 'style',
            'style_name' => $styleName,
            'category_id' => $categoryId,
            'synonym_value' => strtolower($synonymValue),
            'synonym_priority' => $synonymPriority,
        ];
        $this->execute($sql, $params);
    }
}
