<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\CommonBundle\DTO\BrandDTO;
use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;

/**
 * Class SizeBrandRepository.
 */
class SizeBrandRepository extends AbstractAsmodineRepository
{
    /**
     * insert New Size Brand.
     *
     * @param BrandDTO $brandDTO
     * @param string   $name
     */
    public function insert(BrandDTO $brandDTO, string $name): void
    {
        $sql = 'INSERT IGNORE INTO `back_size_brand` '
            .'(`brand_id`, `name`) '
            .'VALUES (:brand_id, :size_name)';

        $params = [
            'brand_id' => $brandDTO->id,
            'size_name' => trim($name),
        ];

        $this->execute($sql, $params);
    }
}
