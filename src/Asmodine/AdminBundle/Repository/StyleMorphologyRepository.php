<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\AdminBundle\Model\StyleMorphology;
use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;

/**
 * Class StyleMorphologyRepository.
 */
class StyleMorphologyRepository extends AbstractAsmodineRepository
{
    /**
     * Insert new Color Morphotype.
     *
     * @param int             $asmodineStyleId Asmodine Color Id
     * @param StyleMorphology $styleMorphology
     */
    public function insert(int $asmodineStyleId, StyleMorphology $styleMorphology): void
    {
        $sql = 'INSERT INTO `back_style_morphology` '
            .'(`style_asmodine_id`, `size`, `morphoprofile`, `morpho_weight`, `note`) '
            .'VALUES (:style_id, :size, :morphoprofile, :weight, :note)';

        $params = [
            'style_id' => $asmodineStyleId,
            'size' => $styleMorphology->getSize(),
            'morphoprofile' => $styleMorphology->getMorphoprofile(),
            'weight' => $styleMorphology->getWeight(),
            'note' => $styleMorphology->getNote(),
        ];
        $this->execute($sql, $params);
    }
}
