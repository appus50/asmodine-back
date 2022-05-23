<?php

namespace Asmodine\AdminBundle\DTO;

/**
 * Class CatalogDTO.
 */
class CatalogDTO
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $origin;

    /**
     * @var string
     */
    public $slug;

    /**
     * @var bool
     */
    public $enabled;

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
        $this->id = $datas['id'];
        $this->name = $datas['name'];
        $this->origin = $datas['origin'];
        $this->slug = $datas['slug'];
        $this->enabled = $datas['enabled'];
        $this->configuration = $datas['configuration'];
        if (!is_null($datas['imported_at'])) {
            $this->importedAt = \DateTime::createFromFormat('Y-m-d H:i:s', $datas['imported_at']);
        }
    }
}
