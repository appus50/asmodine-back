<?php

namespace Asmodine\AdminBundle\DataFixtures\PHP;

use Asmodine\AdminBundle\Model\Catalog;
use Asmodine\AdminBundle\Model\Catalog\Configuration\Manual;
use Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate\Awin;
use Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate\Effiliation;
use Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate\NetAffiliation;
use Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate\Tradedoubler;
use Asmodine\AdminBundle\Model\Catalog\Configuration\Column\Asmodine as ColumnAsmodine;
use Asmodine\AdminBundle\Model\Catalog\Configuration\Column\Catalog as ColumnCatalog;
use Asmodine\AdminBundle\Repository\CatalogRepository;
use Asmodine\CommonBundle\DataFixtures\PHP\AbstractFixturesPHP;
use JMS\Serializer\Serializer;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Insert2CatalogFixtures.
 */
class Insert2CatalogFixtures extends AbstractFixturesPHP
{
    /**
     * Import Catalogs.
     *
     * @throws \Asmodine\CommonBundle\Exception\ModelException
     * @throws \Exception
     */
    public function run(): void
    {
        try {
            $fileContent = file_get_contents(__DIR__.'/'.$this->locale.'/catalog.config.yml');

            $catalogsConfigs = Yaml::parse($fileContent);
        } catch (\Exception $e) {
            throw $e;
        }

        $catalogs = [];
        if (isset($catalogsConfigs['Awin'])) {
            foreach ($catalogsConfigs['Awin'] as $name => $configs) {
                $catalogs[] = $this->createAwinCatalog($name, $configs['advertisers'], $configs['columns'], $configs['model'], isset($configs['is_enabled']) ? $configs['is_enabled'] : true);
            }
        }
        if (isset($catalogsConfigs['Effiliation'])) {
            foreach ($catalogsConfigs['Effiliation'] as $name => $configs) {
                $catalogs[] = $this->createEffiliationCatalog($name, $configs['reference'], $configs['columns'], $configs['model'], isset($configs['is_enabled']) ? $configs['is_enabled'] : true);
            }
        }
        if (isset($catalogsConfigs['Tradedoubler'])) {
            foreach ($catalogsConfigs['Tradedoubler'] as $name => $configs) {
                $catalogs[] = $this->createTradedoublerCatalog($name, $configs['myFeed'], $configs['myFormat'], $configs['columns'], $configs['model'], isset($configs['is_enabled']) ? $configs['is_enabled'] : true);
            }
        }
        if (isset($catalogsConfigs['NetAffiliation'])) {
            foreach ($catalogsConfigs['NetAffiliation'] as $name => $configs) {
                $catalogs[] = $this->createNetAffiliationCatalog($name, $configs['maff'], $configs['columns'], $configs['model'], isset($configs['is_enabled']) ? $configs['is_enabled'] : true);
            }
        }
        if (isset($catalogsConfigs['Manual'])) {
            foreach ($catalogsConfigs['Manual'] as $name => $configs) {
                $catalogs[] = $this->createManualCatalog($name, $configs['path'], $configs['configuration'], $configs['columns'], $configs['model'], isset($configs['is_enabled']) ? $configs['is_enabled'] : true);
            }
        }

        /** @var CatalogRepository $catalogRepo */
        $catalogRepo = $this->container->get('asmodine.admin.repository.catalog');
        /** @var Serializer $serializer */
        $serializer = $this->container->get('jms_serializer');
        $insert = function (Catalog $catalog) use ($catalogRepo, $serializer) {
            $configuration = $serializer->serialize($catalog->getConfiguration(), 'json');
            $catalogRepo->insert($catalog, $configuration);
        };

        array_walk($catalogs, $insert);
    }

    /**
     * Create Catalog From Awin.
     *
     * @param string $name
     * @param array  $advertisers
     * @param array  $catalogColumns
     * @param bool   $isModel
     * @param bool   $enabled
     *
     * @return Catalog
     *
     * @throws \Asmodine\CommonBundle\Exception\ModelException
     * @throws \Asmodine\CommonBundle\Exception\EnumParameterException
     */
    private function createAwinCatalog(string $name, array $advertisers, array $catalogColumns, bool $isModel, bool $enabled): Catalog
    {
        $apikey = $this->container->getParameter('affiliate.awin.api_key');
        $catalog = Catalog::create($name, Catalog::ORIGIN_AWIN, $enabled);
        $awinConfig = new Awin();
        $awinConfig
            ->setLanguage('fr')
            ->setApiKey($apikey);

        $columns = explode(
            ',',
            'aw_product_id,product_name,merchant_category,merchant_product_category_path,'
            .'merchant_product_second_category,description,product_short_description,large_image,size,aw_deep_link,'
            .'brand_name,terms_of_contract,currency,search_price,delivery_cost,product_price_old,colour,valid_from,'
            .'valid_to,ean,model_number,material,size_stock_amount,stock_status,delivery_time,isbn,base_price_text,'
            .'condition,merchant_image_url,alternate_image,alternate_image_two,alternate_image_three,alternate_image_four,'
            .'in_stock,stock_quantity,custom_1,custom_2,custom_3,custom_4,custom_5,custom_6,custom_7,custom_8,base_price,'
            .'Fashion%3Amaterial,Fashion%3Asize,Fashion%3Asuitable_for,mpn,product_type'
        );

        $addAdvertiser = function ($idAdvertiser) use ($awinConfig) {
            $awinConfig->addAvertiser($idAdvertiser);
        };
        $addColumn = function ($column) use ($awinConfig) {
            $awinConfig->addColumn($column);
        };

        array_map($addColumn, $columns);
        array_map($addAdvertiser, $advertisers);

        $configuration = $this->initConfiguration($catalog, $catalogColumns, $isModel);
        $configuration->setAffiliateConfiguration($awinConfig, Catalog::ORIGIN_AWIN);
        $catalog->setConfiguration($configuration);

        return $catalog;
    }

    /**
     * Create Catalog from Effiliation.
     *
     * @param string $name
     * @param int    $reference
     * @param array  $catalogColumns
     * @param bool   $isModel
     * @param bool   $enabled
     *
     * @return Catalog
     *
     * @throws \Asmodine\CommonBundle\Exception\ModelException
     * @throws \Asmodine\CommonBundle\Exception\EnumParameterException
     */
    private function createEffiliationCatalog(string $name, int $reference, array $catalogColumns, bool $isModel, bool $enabled): Catalog
    {
        $catalog = Catalog::create($name, Catalog::ORIGIN_EFFILIATION, $enabled);
        $effiliationConfig = new Effiliation();
        $effiliationConfig->setReference($reference);

        $configuration = $this->initConfiguration($catalog, $catalogColumns, $isModel);
        $configuration->setAffiliateConfiguration($effiliationConfig, Catalog::ORIGIN_EFFILIATION);
        $catalog->setConfiguration($configuration);

        return $catalog;
    }

    /**
     * Create Catalog from Tradedoubler.
     *
     * @param string $name
     * @param int    $myFeed
     * @param int    $myFormat
     * @param array  $catalogColumns
     * @param bool   $isModel
     * @param bool   $enabled
     *
     * @return Catalog
     *
     * @throws \Asmodine\CommonBundle\Exception\ModelException
     * @throws \Asmodine\CommonBundle\Exception\EnumParameterException
     */
    private function createTradedoublerCatalog(string $name, int $myFeed, int $myFormat, array $catalogColumns, bool $isModel, bool $enabled): Catalog
    {
        $catalog = Catalog::create($name, Catalog::ORIGIN_TRADEDOUBLER, $enabled);
        $tradedoublerConfig = new Tradedoubler();
        $tradedoublerConfig->setMyFeed($myFeed);
        $tradedoublerConfig->setMyFormat($myFormat);

        $configuration = $this->initConfiguration($catalog, $catalogColumns, $isModel);
        $configuration->setAffiliateConfiguration($tradedoublerConfig, Catalog::ORIGIN_TRADEDOUBLER);
        $catalog->setConfiguration($configuration);

        return $catalog;
    }

    /**
     * Create Catalog from NetAffiliation.
     *
     * @param string $name
     * @param string $maff
     * @param array  $catalogColumns
     * @param bool   $isModel
     * @param bool   $enabled
     *
     * @return Catalog
     *
     * @throws \Asmodine\CommonBundle\Exception\ModelException
     * @throws \Asmodine\CommonBundle\Exception\EnumParameterException
     */
    private function createNetAffiliationCatalog(string $name, string $maff, array $catalogColumns, bool $isModel, bool $enabled): Catalog
    {
        $catalog = Catalog::create($name, Catalog::ORIGIN_NETAFFILIATION, $enabled);
        $tradedoublerConfig = new NetAffiliation();
        $tradedoublerConfig->setMaff($maff);

        $configuration = $this->initConfiguration($catalog, $catalogColumns, $isModel);
        $configuration->setAffiliateConfiguration($tradedoublerConfig, Catalog::ORIGIN_NETAFFILIATION);
        $catalog->setConfiguration($configuration);

        return $catalog;
    }

    /**
     * Create Manual Catalog.
     *
     * @param string $name
     * @param string $path
     * @param array  $configuration
     * @param array  $catalogColumns
     * @param bool   $isModel
     * @param bool   $enabled
     *
     * @return Catalog
     *
     * @throws \Asmodine\CommonBundle\Exception\EnumParameterException
     */
    private function createManualCatalog(string $name, string $path, array $configuration, array $catalogColumns, bool $isModel, bool $enabled): Catalog
    {
        $catalog = Catalog::create($name, Catalog::ORIGIN_MANUAL, $enabled);
        $manualConfig = Manual::create($path, $configuration);

        $configuration = $this->initConfiguration($catalog, $catalogColumns, $isModel);
        $configuration->setManualConfiguration($manualConfig);
        $catalog->setConfiguration($configuration);

        return $catalog;
    }

    /**
     * Initialisation of configuration and columns.
     *
     * @param Catalog $catalog
     * @param array   $columns
     * @param bool    $isModel
     *
     * @return Catalog\Configuration
     */
    private function initConfiguration(Catalog $catalog, array $columns, bool $isModel): Catalog\Configuration
    {
        $configuration = $catalog->getConfiguration();

        if ($isModel) {
            $configuration->setIsModelLine();
        }
        if (!$isModel) {
            $configuration->setIsProductLine();
        }

        $setColumns = function ($catalog, $asmodine) use ($configuration) {
            if (is_null($catalog)) {
                return;
            }

            $asmodineColumn = ColumnAsmodine::getColumn($asmodine);
            $catalogColumn = ColumnCatalog::createWithContentDatas($catalog);
            $configuration->setColumn($asmodineColumn, $catalogColumn);
        };
        array_walk($columns, $setColumns);

        return $configuration;
    }
}
