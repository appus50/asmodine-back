<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\AdminBundle\Model\ColorMorphotype;
use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;

/**
 * Class ColorMorphotypeRepository.
 */
class ColorMorphotypeRepository extends AbstractAsmodineRepository
{
    /**
     * Insert new Color Morphotype.
     *
     * @param int             $asmodineColorId Asmodine Color Id
     * @param ColorMorphotype $colorMorphotype
     */
    public function insert(int $asmodineColorId, ColorMorphotype $colorMorphotype): void
    {
        $sql = 'INSERT INTO `back_color_morphotype` '
            .'(`color_asmodine_id`, `morphotype`, `note`) '
            .'VALUES (:color_id, :morphotype, :note)';

        $params = [
            'color_id' => $asmodineColorId,
            'morphotype' => $colorMorphotype->getMorphotype(),
            'note' => $colorMorphotype->getNote(),
        ];

        $this->execute($sql, $params);
    }
}
