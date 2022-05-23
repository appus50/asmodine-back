<?php

namespace Asmodine\AdminBundle\DTO;

/**
 * Class CatalogBrandDTO.
 */
class CatalogBrandDTO
{
    /**
     * @var int
     */
    public $catalogId;

    /**
     * @var int
     */
    public $brandId;

    /**
     * @var string
     */
    public $configuration;

    /**
     * @var \DateTime
     */
    public $importedAt;

    /**
     * CatalogDTO constructor.
     *
     * @param array $datas
     */
    public function __construct(array $datas)
    {
        $this->catalogId = $datas['catalog_id'];
        $this->brandId = $datas['brand_id'];
        $this->configuration = $datas['configuration'];
        if (!is_null($datas['imported_at'])) {
            $this->importedAt = \DateTime::createFromFormat('Y-m-d H:i:s', $datas['imported_at']);
        }
    }
}
