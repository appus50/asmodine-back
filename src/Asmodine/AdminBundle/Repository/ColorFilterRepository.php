<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\AdminBundle\Model\ColorFilter;
use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;

/**
 * Class ColorFilterRepository.
 */
class ColorFilterRepository extends AbstractAsmodineRepository
{
    /**
     * Insert new color.
     *
     * @param ColorFilter $color
     */
    public function insert(ColorFilter $color): void
    {
        $sql = 'INSERT INTO `back_color_filter_asmodine` '
            .'(`slug`, `name`) '
            .'VALUES (:slug, :colorname)';

        $this->execute($sql, ['slug' => $color->getSlug(), 'colorname' => $color->getName()]);
    }
}
