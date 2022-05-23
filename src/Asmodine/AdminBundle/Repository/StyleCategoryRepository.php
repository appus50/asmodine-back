<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\AdminBundle\Model\Style;
use Asmodine\CommonBundle\DTO\StyleDTO;
use Asmodine\CommonBundle\Exception\NotFoundEntityException;
use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;

/**
 * Class StyleCategoryRepository.
 */
class StyleCategoryRepository extends AbstractAsmodineRepository
{
    /**
     * Insert new style.
     *
     * @param Style $style
     */
    public function insert(int $asmodineCategoryId, Style $style): void
    {
        $sql = 'INSERT INTO `back_style_category_asmodine` '
            .'(`category_asmodine_id`, `name`, `enabled`) '
            .'VALUES (:category_id, :stylename, :enabled)';

        $params = [
            'category_id' => $asmodineCategoryId,
            'stylename' => $style->getName(),
            'enabled' => $style->enabled(),
        ];
        $this->execute($sql, $params);
    }

    /**
     * Find Style with its name.
     *
     * @param string $name
     *
     * @return StyleDTO
     *
     * @throws NotFoundEntityException
     */
    public function findOneByName(int $asmodineCategoryId, string $name): StyleDTO
    {
        $sql = 'SELECT * FROM `back_style_category_asmodine` WHERE `category_asmodine_id` = :category_id AND `name` = :style_name';
        $stmt = $this->execute($sql, ['category_id' => $asmodineCategoryId, 'style_name' => $name]);
        if ($row = $stmt->fetch()) {
            return new StyleDTO($row);
        }
        throw new NotFoundEntityException(
            'back_style_asmodine',
            'name = '.$name.' AND category_asmodine_id = '.$asmodineCategoryId
        );
    }
}
