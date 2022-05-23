<?php

namespace Asmodine\AdminBundle\Service;

use Asmodine\AdminBundle\DTO\CatalogDTO;
use Asmodine\AdminBundle\Model\Brand;
use Asmodine\AdminBundle\Model\Catalog\Configuration as CatalogConfiguration;
use Asmodine\AdminBundle\Model\CatalogBrand\Configuration as CatalogBrandConfiguration;
use Asmodine\AdminBundle\Model\CatalogBrand\Configuration\SimpleFilter;
use Asmodine\AdminBundle\Repository\CatalogBrandRepository;
use Asmodine\AdminBundle\Repository\ModelRepository;
use Asmodine\CommonBundle\Exception\NullException;
use JMS\Serializer\Serializer;
use Psr\Log\LoggerInterface;

/**
 * Class ImportBrandService.
 */
class ImportBrandService
{
    /**
     * @var CatalogBrandRepository
     */
    private $catalogBrandRepository;

    /**
     * @var ModelRepository
     */
    private $modelRepository;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Brand
     */
    private $brand;

    /**
     * @var CatalogDTO
     */
    private $catalogDTO;

    /**
     * Name of Temporary table.
     *
     * @var string
     */
    private $tmpTable;

    /**
     * @var CatalogConfiguration
     */
    private $catalogConfiguration;

    /**
     * ImportBrandService constructor.
     *
     * @param CatalogBrandRepository $catalogBrandRepository
     * @param ModelRepository        $modelRepository
     * @param Serializer             $serializer
     * @param LoggerInterface        $logger
     */
    public function __construct(CatalogBrandRepository $catalogBrandRepository, ModelRepository $modelRepository, Serializer $serializer, LoggerInterface $logger)
    {
        $this->catalogBrandRepository = $catalogBrandRepository;
        $this->modelRepository = $modelRepository;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @param Brand|null      $brand
     * @param CatalogDTO|null $catalogDTO
     *
     * @return ImportBrandService
     *
     * @throws NullException
     */
    public function init(?Brand $brand, ?CatalogDTO $catalogDTO): self
    {
        if (!is_null($brand)) {
            $this->brand = $brand;
        }
        if (!is_null($catalogDTO)) {
            $this->catalogDTO = $catalogDTO;
        }

        if (is_null($this->brand) || is_null($this->catalogDTO)) {
            throw new NullException('Brand and/or Catalog DTO are null in ImportBrandService');
        }

        if (!is_null($brand) || !is_null($catalogDTO)) {
            $this->tmpTable = 'tmp_back_'.str_replace('-', '_', $this->brand->getSlug()).'_'.str_replace('-', '_', $this->catalogDTO->slug);
            $this->catalogConfiguration = $this->serializer->deserialize($this->catalogDTO->configuration, CatalogConfiguration::class, 'json');
        }

        return $this;
    }

    /**
     * Copy Formatted Catalog.
     *
     * @param Brand|null      $brand
     * @param CatalogDTO|null $catalogDTO
     *
     * @return int
     */
    public function copyFormattedCatalog(?Brand $brand = null, ?CatalogDTO $catalogDTO = null): int
    {
        $this->init($brand, $catalogDTO);
        $catalogTable = 'catalog_'.$this->catalogDTO->origin.'_'.str_replace('-', '_', $this->catalogDTO->slug);

        $this->catalogBrandRepository->copyTable($catalogTable, $this->tmpTable);

        return $this->catalogBrandRepository->countRows($this->tmpTable);
    }

    /**
     * Executes Action.
     *
     * @see CatalogBrandConfiguration\Action
     *
     * @param CatalogBrandConfiguration\Action $action
     * @param Brand|null                       $brand
     * @param CatalogDTO|null                  $catalogDTO
     *
     * @return ImportBrandService
     */
    public function executeAction(CatalogBrandConfiguration\Action $action, ?Brand $brand = null, ?CatalogDTO $catalogDTO = null): self
    {
        $this->init($brand, $catalogDTO);
        $this->catalogBrandRepository->executeAction($this->tmpTable, $action);

        return $this;
    }

    /**
     * Executes Simple Filter.
     *
     * @see SimpleFilter
     *
     * @param SimpleFilter    $filter
     * @param Brand|null      $brand
     * @param CatalogDTO|null $catalogDTO
     *
     * @return ImportBrandService
     */
    public function executeSimpleFilter(SimpleFilter $filter, ?Brand $brand = null, ?CatalogDTO $catalogDTO = null): self
    {
        $this->init($brand, $catalogDTO);
        $this->catalogBrandRepository->executeSimpleFilter($this->tmpTable, $filter);

        return $this;
    }

    /**
     * Clean incomplete row.
     *
     * @param Brand|null      $brand
     * @param CatalogDTO|null $catalogDTO
     *
     * @return ImportBrandService
     */
    public function removeIncompleteRow(?Brand $brand = null, ?CatalogDTO $catalogDTO = null): self
    {
        $this->init($brand, $catalogDTO);
        $this->catalogBrandRepository->removeIncompleteRow($this->tmpTable);

        return $this;
    }

    /**
     * Generate Product and Model Table .
     *
     * @param CatalogBrandConfiguration $catalogBrandConfig
     * @param Brand|null                $brand
     * @param CatalogDTO|null           $catalogDTO
     *
     * @return ImportBrandService
     */
    public function createModelAndProductTables(CatalogBrandConfiguration $catalogBrandConfig, ?Brand $brand = null, ?CatalogDTO $catalogDTO = null): self
    {
        $this->init($brand, $catalogDTO);

        $this->catalogBrandRepository->createModelAndProductTables($this->tmpTable);
        if ($this->catalogConfiguration->isProductLine()) {
            $this->catalogBrandRepository->populateWithProduct($this->tmpTable, $this->brand, $catalogBrandConfig);
        }
        if ($this->catalogConfiguration->isModelLine()) {
            $this->catalogBrandRepository->populateWithModel($this->tmpTable, $this->brand, $catalogBrandConfig);
        }

        return $this;
    }

    /**
     * @param Brand|null      $brand
     * @param CatalogDTO|null $catalogDTO
     *
     * @return ImportBrandService
     */
    public function updateAsmodineTables(?Brand $brand = null, ?CatalogDTO $catalogDTO = null): self
    {
        $this->init($brand, $catalogDTO);

        $this->catalogBrandRepository->manageAttributes($this->tmpTable.'_model', $this->tmpTable.'_product');
        $this->catalogBrandRepository->updateAsmodineModelAndProduct($this->brand->getId(), $this->tmpTable.'_model', $this->tmpTable.'_product');
        $this->catalogBrandRepository->updateAsmodineImage($this->tmpTable, $this->catalogConfiguration->isModelLine());
        $this->modelRepository->createSlug();

        return $this;
    }

    /**
     * Removes temporary tables.
     */
    public function clean(): self
    {
        // TODO Remove Model In Product associate with "nobody" category
        $this->catalogBrandRepository->dropImportTables($this->tmpTable);

        return $this;
    }

    /**
     * Get Nb Rows on temporary table.
     *
     * @param Brand|null      $brand
     * @param CatalogDTO|null $catalogDTO
     *
     * @return int
     */
    public function getNbRows(?Brand $brand = null, ?CatalogDTO $catalogDTO = null): int
    {
        $this->init($brand, $catalogDTO);

        return $this->catalogBrandRepository->countRows($this->tmpTable);
    }

    /**
     * Get Nb Rowns on temporay model and product tables.
     *
     * @param Brand|null      $brand
     * @param CatalogDTO|null $catalogDTO
     *
     * @return array
     */
    public function getNbProductModelRows(?Brand $brand = null, ?CatalogDTO $catalogDTO = null): array
    {
        $this->init($brand, $catalogDTO);

        return [
            'model' => $this->catalogBrandRepository->countRows($this->tmpTable.'_model'),
            'product' => $this->catalogBrandRepository->countRows($this->tmpTable.'_product'),
        ];
    }
}
