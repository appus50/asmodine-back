<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\CommonBundle\DTO\BrandDTO;
use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;

/**
 * Class SizeGuideBodyPartRepository.
 */
class SizeGuideBodyPartRepository extends AbstractAsmodineRepository
{
    /**
     * Insert new SizeGuideBodyPart.
     *
     * @param BrandDTO $brandDTO
     * @param string   $type
     * @param int      $typeId
     * @param array    $datas
     */
    public function insert(?BrandDTO $brandDTO, string $type, int $typeId, array $datas): void
    {
        $values = array_values($datas);
        $values = array_map(
            function ($v) {
                return $v ? 'TRUE' : 'FALSE';
            },
            $values
        );

        $sql = 'INSERT INTO `back_size_guide_body_part` '
            .'(`brand_id`, `type`, `type_id`, `'.implode('`, `', array_keys($datas)).'`) '
            .'VALUES (:brand_id, :type, :type_id, '
            .implode(', ', $values).')';

        $params = [
            'brand_id' => is_null($brandDTO) ? 0 : $brandDTO->id,
            'type' => $type,
            'type_id' => $typeId,
        ];

        $this->execute($sql, $params);
    }
}
