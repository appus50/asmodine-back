<?php

namespace Asmodine\AdminBundle\DataFixtures\PHP;

use Asmodine\AdminBundle\Model\Brand;
use Asmodine\AdminBundle\Repository\BrandRepository;
use Asmodine\CommonBundle\DataFixtures\PHP\AbstractFixturesPHP;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Insert1BrandFixtures.
 */
class Insert1BrandFixtures extends AbstractFixturesPHP
{
    /**
     * Import Brand.
     *
     * @throws \Exception
     */
    public function run(): void
    {
        try {
            $fileContent = file_get_contents(__DIR__.'/'.$this->locale.'/brand.yml');
            $brands = Yaml::parse($fileContent);
        } catch (\Exception $e) {
            throw $e;
        }

        /** @var BrandRepository $brandRepo */
        $brandRepo = $this->container->get('asmodine.admin.repository.brand');
        $insertBrand = function ($datas) use ($brandRepo) {
            $isEnabled = isset($datas['is_enabled']) ? $datas['is_enabled'] : true;
            $isIframe = isset($datas['iframe']) ? $datas['iframe'] : true;
            $brand = Brand::create($datas['name'], $datas['description'], $datas['logo'], $isIframe, $isEnabled);
            $brandRepo->insert($brand);
        };
        array_map($insertBrand, $brands);
    }
}
