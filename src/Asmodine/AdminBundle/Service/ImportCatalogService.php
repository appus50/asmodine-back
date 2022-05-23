<?php

namespace Asmodine\AdminBundle\Service;

use Asmodine\AdminBundle\Repository\CatalogRepository;
use Asmodine\CommonBundle\Annotation\IncludeSQL;
use Asmodine\AdminBundle\Model\Catalog;
use Asmodine\AdminBundle\Model\Catalog\Configuration;
use Asmodine\AdminBundle\Repository\CatalogImportRepository;
use Asmodine\CommonBundle\Exception\NullException;
use Asmodine\CommonBundle\Util\FileUtils;
use Buzz\Browser;
use Psr\Log\LoggerInterface;
use Symfony\Component\Intl\Exception\NotImplementedException;

/**
 * Class ImportCatalogService.
 */
class ImportCatalogService
{
    /**
     * @var Browser
     */
    private $browser;

    /**
     * @var CatalogRepository
     */
    private $catalogRepository;

    /**
     * @var CatalogImportRepository
     */
    private $catalogImportRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $csvFolder;

    /**
     * @var string
     */
    private $url;

    /**
     * ImportCatalogService constructor.
     *
     * @param Browser                 $browser
     * @param CatalogRepository       $catalogRepository
     * @param CatalogImportRepository $catalogImportRepository
     * @param string                  $projectDir
     * @param LoggerInterface         $logger
     */
    public function __construct(Browser $browser, CatalogRepository $catalogRepository, CatalogImportRepository $catalogImportRepository, string $projectDir, LoggerInterface $logger)
    {
        $this->browser = $browser;
        $this->catalogRepository = $catalogRepository;
        $this->catalogImportRepository = $catalogImportRepository;
        $this->csvFolder = $projectDir.'/var/files/catalog/';
        $this->logger = $logger;
    }

    /**
     * Load Catalog from URL.
     *
     * @param Catalog $catalog
     *
     * @return FileUtils
     *
     * @throws \Asmodine\CommonBundle\Exception\FileException
     * @throws \Asmodine\CommonBundle\Exception\ModelException
     */
    public function importFromURL(Catalog $catalog): FileUtils
    {
        $this->logger->debug('Import Catalog From URL');
        $configuration = $catalog->getConfiguration();
        if (Catalog::ORIGIN_MANUAL == $catalog->getOrigin()) {
            $config = $configuration->getManualConfiguration();
            $csvFile = $this->getCSVFile($catalog, $config->getFileExtension(), $config->getArchiveFormat());
            $this->url = $config->getPath();
        } else {
            $config = $configuration->getAffiliateConfiguration();
            $csvFile = $this->getCSVFile($catalog, $config->getFileExtension(), $config->getArchiveFormat());
            $this->url = $config->buildUrl();
        }
        $this->logger->debug('  - Catalog : '.$catalog->getName());
        $this->logger->debug('  - Origin : '.$catalog->getOrigin());
        $this->logger->debug('  - URL : '.$this->url);
        $this->logger->debug('  - File : '.$csvFile->getFilename());
        $datas = $this->browser->get($this->url);
        $csvFile->saveDatas($datas->getContent());

        if (Catalog::ARCHIVE_FORMAT_GZ == $config->getArchiveFormat()) {
            $csvFile->gunzip(true);
        }
        if (Catalog::ARCHIVE_FORMAT_ZIP == $config->getArchiveFormat()) {
          $csvFile->unzip1File(true);
        }

        return $csvFile;
    }

    /**
     * Copy File in specific folder.
     *
     * @param Catalog $catalog
     * @param string  $path
     *
     * @return FileUtils
     *
     * @throws \Asmodine\CommonBundle\Exception\FileException
     */
    public function importFromFile(Catalog $catalog, string $path): FileUtils
    {
        $extension = '';
        $archiveFormat = '';
        if (false !== '.csv') {
            $extension = Catalog::EXT_CSV;
        }
        if (false !== '.gz') {
            $archiveFormat = Catalog::ARCHIVE_FORMAT_GZ;
        }
        if (false !== '.zip') {
            $archiveFormat = Catalog::ARCHIVE_FORMAT_ZIP;
        }

        $csvFile = $this->getCSVFile($catalog, $extension, $archiveFormat);
        $csvFile->copyFrom($path);
        if (Catalog::ARCHIVE_FORMAT_GZ == $archiveFormat) {
            $csvFile->gunzip(true);
        }
        if (Catalog::ARCHIVE_FORMAT_ZIP == $archiveFormat) {
            $csvFile->unzip1File(true);
        }

        return $csvFile;
    }

    /**
     * Create SQL Table and load CSV.
     *
     * @param Catalog   $catalog
     * @param FileUtils $file
     *
     * @return int
     *
     * @throws \Asmodine\CommonBundle\Exception\FileException
     * @throws \Asmodine\CommonBundle\Exception\ModelException
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function createTableTemporary(Catalog $catalog, FileUtils $file): int
    {
        $tableName = $this->getTableName($catalog, false);
        if (!is_null($catalog->getConfiguration()->getManualConfiguration()) && Catalog::EXT_XML == $catalog->getConfiguration()->getManualConfiguration()->getFileExtension()) {
            $columns = $file->getXMLTags(1000000);
            $this->catalogImportRepository->createTable($tableName, $columns['rows']);
            $this->catalogImportRepository->loadXML($tableName, $file->getRealpath(), $columns['line'], $columns['rows']);
        } else {
            $csvConfig = $catalog->getConfiguration()->getCSVConfig();
            $columnsName = $file->getCSVDatas($csvConfig[Configuration::CSV_DELIMITER], $csvConfig[Configuration::CSV_ENCLOSURE], $csvConfig[Configuration::CSV_ESCAPE], 1);
            $this->catalogImportRepository->createTable($tableName, $columnsName[0]);
            $this->catalogImportRepository->loadCSV(
                $tableName,
                $file->getRealpath(),
                $csvConfig[Configuration::CSV_DELIMITER],
                $csvConfig[Configuration::CSV_ENCLOSURE],
                $csvConfig[Configuration::CSV_ESCAPE]
            );
        }

        return $this->catalogImportRepository->countRows($tableName);
    }

    /**
     * Format Table load from CSV.
     *
     * @param Catalog $catalog
     *
     * @return int
     *
     * @throws NullException
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function formatTableTemporary(Catalog $catalog): int
    {
        $asmodineColumns = Configuration\Column\Asmodine::getSQLColumns();
        $asmodineColumnsName = Configuration\Column\Asmodine::getColumnsKeys();
        $formatCatalogColumns = $catalog->getConfiguration()->getFormatSQLColumns();

        if (0 == count($formatCatalogColumns)) {
            throw  new NullException('Catalog '.$catalog->getName().' - '.$catalog->getOrigin().' is not or incorrectly configured !');
        }
        array_walk($formatCatalogColumns, $this->applyCastToFormattedColumns());

        $catalogTableName = $this->getTableName($catalog, false);
        $formatTableName = $this->getTableName($catalog, true);
        $this->catalogImportRepository->createFormatTable($formatTableName, $asmodineColumns);
        $this->catalogImportRepository->insertDatasInFormatTable($formatTableName, $catalogTableName, $asmodineColumnsName, $formatCatalogColumns);

        return $this->catalogImportRepository->countRows($formatTableName);
    }

    /**
     * Clean Table (Delete required column).
     *
     * @param Catalog $catalog
     *
     * @return int
     *
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function cleanFormatTableTemporary(Catalog $catalog): int
    {
        $requiredColumns = Configuration\Column\Asmodine::getRequiredColumns();
        $formatTableName = $this->getTableName($catalog, true);
        $this->catalogImportRepository->removeNullValueIn($formatTableName, array_keys($requiredColumns));
        $this->catalogRepository->setImported($catalog);

        return $this->catalogImportRepository->countRows($formatTableName);
    }

    /**
     * Remove the unformatted table.
     *
     * @param Catalog $catalog
     *
     * @return string
     */
    public function dropImportTable(Catalog $catalog): string
    {
        $catalogTableName = $this->getTableName($catalog, false);
        $this->catalogImportRepository->dropTable($catalogTableName);

        return $catalogTableName;
    }

    /**
     * Renvoie l'url de téléchargement si elle existe
     */
    public function getUrl():string {
        if(!is_null($this->url)){
            return $this->url;
        }

        return 'non initialisée';
    }

    /**
     * Construct FileName.
     *
     * @param Catalog $catalog
     * @param string  $extension
     * @param string  $archiveFormat
     *
     * @return FileUtils
     */
    private function getCSVFile(Catalog $catalog, string $extension, string $archiveFormat): FileUtils
    {
        $fileName = date('YmdHis').'_'.$catalog->getOrigin().'_'.$catalog->getSlug().$extension.$archiveFormat;

        return new FileUtils($this->csvFolder.$fileName);
    }

    /**
     * Create temporary table name.
     *
     * @param Catalog $catalog
     * @param bool    $format
     *
     * @return string
     */
    private function getTableName(Catalog $catalog, bool $format): string
    {
        $prefix = 'catalog_';
        if (!$format) {
            $prefix = 'tmp_catalog_';
        }

        return $prefix.$catalog->getOrigin().'_'.str_replace('-', '_', $catalog->getSlug());
    }

    /**
     * @return \Closure
     *
     * @IncludeSQL
     */
    private function applyCastToFormattedColumns(): \Closure
    {
        $castColumns = Configuration\Column\Asmodine::getCastSQLColumns();
        $castStrings = [
            Configuration\Column\Asmodine::TYPE_DECIMAL => 'IF(%1$s IS NULL, NULL, CAST(%1$s AS DECIMAL%2$s))',
            Configuration\Column\Asmodine::TYPE_BOOLEAN => 'LOWER(%1$s) IN (\'1\',\'true\',\'vrai\',\'oui\',\'yes\',\'in stock\', \'en stock\')',
            Configuration\Column\Asmodine::TYPE_SMALLINT => 'IF(%1$s IS NULL, NULL, CAST(%1$s AS UNSIGNED))',
            Configuration\Column\Asmodine::TYPE_ENUM => '%1$s',
            Configuration\Column\Asmodine::TYPE_DATETIME => 'IF(%1$s IS NULL, NULL, STR_TO_DATE(%1$s, \'%%d/%%m/%%Y %%H:%%i\'))',
            Configuration\Column\Asmodine::TYPE_DATE => 'IF(%1$s IS NULL, NULL, STR_TO_DATE(%1$s, \'%%d/%%m/%%Y %%h:%%i\'))',
        ];

        return function (&$item, $key) use ($castColumns, $castStrings) {
            if (!in_array($key, array_keys($castColumns))) {
                return;
            }
            if (!isset($castStrings[$castColumns[$key]['type']])) {
                throw new \OutOfRangeException($castColumns[$key]['type'].' does not have a formatted string.');
            }

            $formatString = $castStrings[$castColumns[$key]['type']];
            $item = sprintf($formatString, $item, $castColumns[$key]['more']);
        };
    }
}
