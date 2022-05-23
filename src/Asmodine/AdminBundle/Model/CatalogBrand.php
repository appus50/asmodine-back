<?php

namespace Asmodine\AdminBundle\Model;

use Asmodine\AdminBundle\Model\CatalogBrand\Configuration;

/**
 * Class CatalogBrand.
 */
class CatalogBrand
{
    /**
     * @var Catalog
     */
    private $catalog;

    /**
     * @var Brand
     */
    private $brand;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * CatalogBrand constructor.
     *
     * @param Catalog       $catalog
     * @param Brand         $brand
     * @param Configuration $configuration
     */
    private function __construct(Catalog $catalog, Brand $brand, Configuration $configuration)
    {
        $this->catalog = $catalog;
        $this->brand = $brand;
        $this->configuration = $configuration;
    }

    /**
     * CatalogBrand constructor.
     *
     * @param Catalog $catalog
     * @param Brand   $brand
     *
     * @return CatalogBrand
     */
    public static function create(Catalog $catalog, Brand $brand)
    {
        $configuration = new Configuration();
        $instance = new self($catalog, $brand, $configuration);

        return $instance;
    }

    /**
     * Get CatalogBrand Configuration.
     *
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * Set Configuration.
     *
     * @param Configuration $configuration
     *
     * @return CatalogBrand
     */
    public function setConfiguration(Configuration $configuration): self
    {
        $this->configuration = $configuration;

        return $this;
    }
}
