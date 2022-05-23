<?php

namespace Asmodine\AdminBundle\DataFixtures\PHP;

use Asmodine\AdminBundle\Model\CatalogBrand\Configuration;
use Asmodine\AdminBundle\Repository\BrandRepository;
use Asmodine\AdminBundle\Repository\CatalogBrandRepository;
use Asmodine\AdminBundle\Repository\CatalogRepository;
use Asmodine\CommonBundle\DataFixtures\PHP\AbstractFixturesPHP;
use JMS\Serializer\Serializer;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Insert3CatalogBrandConfigFixtures.
 */
class Insert3CatalogBrandConfigFixtures extends AbstractFixturesPHP
{
    /**
     * Import CatalogBrandConfig.
     */
    public function run(): void
    {
        try {
            $fileContent = file_get_contents(__DIR__.'/'.$this->locale.'/catalog.brand.yml');
            $catalogBrands = Yaml::parse($fileContent);
        } catch (\Exception $e) {
            throw $e;
        }
        array_map($this->insertCatalogBrands(), $catalogBrands);
    }

    /**
     * Insert config Catalog/Brand.
     *
     * @return \Closure
     */
    private function insertCatalogBrands(): \Closure
    {
        /** @var CatalogRepository $catalogRepo */
        $catalogRepo = $this->container->get('asmodine.admin.repository.catalog');
        /** @var BrandRepository $brandRepo */
        $brandRepo = $this->container->get('asmodine.admin.repository.brand');
        /** @var CatalogBrandRepository $catalogBrandRepo */
        $catalogBrandRepo = $this->container->get('asmodine.admin.repository.catalog_brand');
        /** @var Serializer $serializer */
        $serializer = $this->container->get('jms_serializer');

        return function ($catalogBrand) use ($catalogRepo, $brandRepo, $catalogBrandRepo, $serializer) {
            $catalogDTO = $catalogRepo->findOneBySlug($catalogBrand['catalog']);
            $brandDTO = $brandRepo->findOneBySlug($catalogBrand['brand']);
            $configuration = new Configuration();

            if (isset($catalogBrand['configuration']['simple_filter'])) {
                $simpleFilters = $catalogBrand['configuration']['simple_filter'];
                foreach ($simpleFilters as $name => $datas) {
                    $glue = isset($datas['glue']) ? $datas['glue'] : null;
                    $filter = new Configuration\SimpleFilter($datas['column'], $datas['position'], $datas['contents'], $datas['keep'], $glue);
                    $configuration->setSimpleFilter($name, $filter);
                }
            }

            if (isset($catalogBrand['configuration']['action'])) {
                $actions = $catalogBrand['configuration']['action'];
                foreach ($actions as $name => $datas) {
                    $action = new Configuration\Action($datas['action'], $datas['column'], $datas['contents']);
                    $configuration->setAction($name, $action);
                }
            }

            if (isset($catalogBrand['configuration']['separator']['size'])) {
                $configuration->setSeparatorSize($catalogBrand['configuration']['separator']['size']);
            }
            if (isset($catalogBrand['configuration']['separator']['color'])) {
                $configuration->setSeparatorColor($catalogBrand['configuration']['separator']['color']);
            }

            $jsonConfig = $serializer->serialize($configuration, 'json');
            $catalogBrandRepo->insertWithDTO($catalogDTO, $brandDTO, $jsonConfig);
        };
    }
}
