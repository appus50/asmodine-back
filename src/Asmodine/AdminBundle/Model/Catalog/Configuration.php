<?php

namespace Asmodine\AdminBundle\Model\Catalog;

use Asmodine\CommonBundle\Annotation\IncludeSQL;
use Asmodine\AdminBundle\Model\Catalog;
use Asmodine\AdminBundle\Model\Catalog\Configuration\Manual;
use Asmodine\AdminBundle\Model\Catalog\Configuration\Column\Asmodine as ColumnAsmodine;
use Asmodine\AdminBundle\Model\Catalog\Configuration\Column\Catalog as ColumnCatalog;
use Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate\AffiliateInterface;
use Asmodine\CommonBundle\Exception\ModelException;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Configuration.
 */
class Configuration
{
    const CSV_DELIMITER = 'csv_delimiter';
    const CSV_ENCLOSURE = 'csv_enclosure';
    const CSV_ESCAPE = 'csv_escape';

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $url;

    /**
     * @var AffiliateInterface
     *
     * @Serializer\Type("Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate\AbstractAffiliate")
     */
    private $affiliate;

    /**
     * @var Manual
     *
     * @Serializer\Type("Asmodine\AdminBundle\Model\Catalog\Configuration\Manual")
     */
    private $manual;

    /**
     * @var array
     *
     * @Serializer\Type("array")
     */
    private $csv;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $origin;

    /**
     * @var bool
     *
     * @Serializer\Type("boolean")
     */
    private $model;

    /**
     * @var array
     *
     * @Serializer\Type("array<string,Asmodine\AdminBundle\Model\Catalog\Configuration\Column\Catalog>")
     */
    private $columns;

    /**
     * Configuration constructor.
     *
     * @param string $origin
     */
    public function __construct($origin)
    {
        $this->origin = $origin;
        $this->model = true;
        $this->affiliate = null;
        $this->manual = null;
    }

    /**
     * Get URL of CSV.
     *
     * @return null|string
     *
     * @throws ModelException
     */
    public function getUrl(): ?string
    {
        if (strlen($this->url) > 10) {
            return $this->url;
        }

        if (in_array($this->origin, Catalog::getOrigins()) && Catalog::ORIGIN_MANUAL !== $this->origin) {
            $configAffiliate = $this->getAffiliateConfiguration();

            return $configAffiliate->buildUrl();
        }

        return null;
    }

    /**
     * Get 3 parameters of CSV.
     *
     * @return array
     *
     * @throws ModelException
     */
    public function getCSVConfig(): array
    {
        if (Catalog::ORIGIN_MANUAL == $this->origin && is_null($this->csv)) {
            return [
                self::CSV_DELIMITER => ';',
                self::CSV_ENCLOSURE => '"',
                self::CSV_ESCAPE => '\\',
            ];
        }
        if (Catalog::ORIGIN_MANUAL == $this->origin) {
            return $this->csv;
        }

        $affiliateConfig = $this->getAffiliateConfiguration();

        return $affiliateConfig->getCSVConfig();
    }

    /**
     * Set Manual Config.
     *
     * @param Manual $configManual
     *
     * @return Configuration
     */
    public function setManualConfiguration(Manual $configManual): self
    {
        $this->origin = Catalog::ORIGIN_MANUAL;
        $this->manual = $configManual;

        return $this;
    }

    /**
     * Set Affiliate Config.
     *
     * @param string             $origin
     * @param AffiliateInterface $affiliate
     *
     * @return Configuration
     *
     * @throws ModelException
     */
    public function setAffiliateConfiguration(AffiliateInterface $affiliate, string $origin): self
    {
        $this->checkAffiliateName($origin);
        $this->origin = $origin;
        $this->affiliate = $affiliate;

        return $this;
    }

    /**
     * Get or create Affiliate Config.
     *
     * @return AffiliateInterface|null
     *
     * @throws ModelException
     */
    public function getAffiliateConfiguration(): ?AffiliateInterface
    {
        $this->checkAffiliateName($this->origin);
        if (is_null($this->affiliate)) {
            $className = 'Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate\\'.ucfirst($this->origin);
            $this->affiliate = new $className();
        }

        return $this->affiliate;
    }

    /**
     * Get or create Manual Config.
     *
     * @return Manual|null
     */
    public function getManualConfiguration(): ?Manual
    {
        return $this->manual;
    }

    /**
     * Return all connection between catalog and Asmodine columns.
     *
     * @return array
     */
    public function getColumns(): array
    {
        if (is_null($this->columns)) {
            $this->columns = [];
        }

        return $this->columns;
    }

    /**
     * Format SQL SELECT.
     *
     * @return array
     */
    public function getFormatSQLColumns(): array
    {
        $columns = $this->getColumns();
        foreach ($columns as $asmodine => $catalog) {
            $columns[$asmodine] = $this->formatSQLColumn($catalog, ColumnAsmodine::MODEL_ID == $asmodine);
        }

        return $columns;
    }

    /**
     * Return format select with concat_ws if there is more than one element.
     *
     * @param ColumnCatalog $column
     * @param bool          $isModelId
     *
     * @return string
     * @IncludeSQL
     */
    private function formatSQLColumn(ColumnCatalog $column, bool $isModelId): string
    {
        if (1 == $column->count()) {
            return $column->getContent(0)->getSQL();
        }

        $formatContents = [];
        for ($i = 0; $i < $column->count(); ++$i) {
            $formatContents[] = $column->getContent($i)->getSQL();
        }
        $formatSQL = 'CONCAT('.implode(', ', $formatContents).')';

        if (!$isModelId || $column->count() <= 1) {
            return $formatSQL;
        }

        // If ModelId And No specific id of model in catalog
        $colBrand = $this->getAsmodineColumn(ColumnAsmodine::BRAND);
        $sqlString = 'MD5('.$formatSQL.')';
        if (is_null($colBrand)) {
            return $sqlString;
        }

        return 'CONCAT(LOWER(REPLACE('.$colBrand->getContent(0)->getSQL().", ' ','')), '-', $sqlString)";
    }

    /**
     * Set Column Config.
     *
     * @param ColumnAsmodine $asmodineColumn
     * @param ColumnCatalog  $catalogColumn
     *
     * @return Configuration
     */
    public function setColumn(ColumnAsmodine $asmodineColumn, ColumnCatalog $catalogColumn): self
    {
        $this->columns[$asmodineColumn->getName()] = $catalogColumn;

        return $this;
    }

    /**
     * @return bool
     */
    public function isModelLine(): bool
    {
        return $this->model;
    }

    /**
     * @return bool
     */
    public function isProductLine(): bool
    {
        return !$this->model;
    }

    /**
     * If catalog contains one model per line.
     *
     * @return Configuration
     */
    public function setIsModelLine(): self
    {
        $this->model = true;

        return $this;
    }

    /**
     * If catalog contains one product per line.
     *
     * @return Configuration
     */
    public function setIsProductLine(): self
    {
        $this->model = false;

        return $this;
    }

    /**
     * Check if the affiliate name is valid.
     *
     * @param string $origin
     *
     * @throws ModelException
     */
    private function checkAffiliateName(string $origin): void
    {
        $origins = Catalog::getOrigins();
        if (!in_array($origin, $origins) || Catalog::ORIGIN_MANUAL == $origin) {
            throw new ModelException($origin.' is not a valid affiliate service.');
        }
    }

    /**
     * Get SpecificColumn.
     *
     * @param string $asmodineColumn
     *
     * @return ColumnCatalog|null
     */
    private function getAsmodineColumn(string $asmodineColumn): ?ColumnCatalog
    {
        $columns = $this->getColumns();
        foreach ($columns as $asmodine => $catalog) {
            if ($asmodine == $asmodineColumn) {
                return $catalog;
            }
        }

        return null;
    }
}
