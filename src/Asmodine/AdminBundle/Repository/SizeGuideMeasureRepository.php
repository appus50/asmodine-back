<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\CommonBundle\DTO\BrandDTO;
use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;

/**
 * Class SizeGuideMeasureRepository.
 */
class SizeGuideMeasureRepository extends AbstractAsmodineRepository
{
    /**
     * Insert New SizeGuideMeasure.
     *
     * @param BrandDTO $brandDTO
     * @param string   $sizeName
     * @param string   $type
     * @param int      $typeId
     * @param array    $datas
     */
    public function insert(BrandDTO $brandDTO, string $sizeName, string $type, int $typeId, array $datas): void
    {
        $values = array_values($datas);
        $values = array_map(function ($v) {
            return is_null($v) ? 'NULL' : intval($v * 10); // cm => mm
        }, $values);

        $sql = 'INSERT INTO `back_size_guide_measure` '
            .'(`brand_size_id`, `type`, `type_id`, `'.implode('`, `', array_keys($datas)).'`) '
            .'VALUES ((SELECT id FROM back_size_brand WHERE brand_id = :brand_id AND NAME = :size_name), :type, :type_id, '
            .implode(', ', $values).')';

        $params = [
            'brand_id' => $brandDTO->id,
            'size_name' => trim($sizeName),
            'type' => $type,
            'type_id' => $typeId,
        ];
        $this->execute($sql, $params);
    }
}
