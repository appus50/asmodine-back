<?php

namespace Asmodine\AdminBundle\DataFixtures\PHP;

use Asmodine\AdminBundle\Repository\BrandRepository;
use Asmodine\AdminBundle\Repository\CategoryRepository;
use Asmodine\AdminBundle\Repository\SizeBrandRepository;
use Asmodine\AdminBundle\Repository\SizeGuideMeasureRepository;
use Asmodine\CommonBundle\DataFixtures\PHP\AbstractFixturesPHP;
use Asmodine\CommonBundle\DTO\BrandDTO;
use Asmodine\CommonBundle\Model\Profile\Body;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Insert4SizeGuideMeasureFixtures.
 */
class Insert4SizeGuideMeasureFixtures extends AbstractFixturesPHP
{
    const DELTA = 0.9;
    const DELTA_NECK = 0.5;

    /**
     * Import Size Guide Measure.
     */
    public function run(): void
    {
        try {
            $fileContent = file_get_contents(__DIR__.'/'.$this->locale.'/size_guide.measure.yml');
            $sizeGuideMeasure = Yaml::parse($fileContent);
        } catch (\Exception $e) {
            throw $e;
        }
        array_walk($sizeGuideMeasure, $this->insertSizeGuideMeasure());
    }

    /**
     * Insert Brand Size Guide.
     *
     * @return \Closure
     */
    private function insertSizeGuideMeasure(): \Closure
    {
        /** @var BrandRepository $brandRepo */
        $brandRepo = $this->container->get('asmodine.admin.repository.brand');

        return function ($sizeGuideMeasure, $brandSlug) use ($brandRepo) {
            $brandDTO = $brandRepo->findOneBySlug($brandSlug);
            array_walk($sizeGuideMeasure, $this->getCategorieSizeDatas($brandDTO));
        };
    }

    /**
     * Insert One record in size guide measure.
     *
     * @param BrandDTO $brandDTO
     *
     * @return \Closure
     */
    private function getCategorieSizeDatas(BrandDTO $brandDTO): \Closure
    {
        /** @var SizeBrandRepository $sizeBrandRepo */
        $sizeBrandRepo = $this->container->get('asmodine.admin.repository.size_brand');
        /** @var CategoryRepository $categoryRepo */
        $categoryRepo = $this->container->get('asmodine.admin.repository.category');
        /** @var SizeGuideMeasureRepository $sizeGuideMeasureRepo */
        $sizeGuideMeasureRepo = $this->container->get('asmodine.admin.repository.size_guide_measure');

        return function ($datasFixtures, $categoryPath) use ($brandDTO, $sizeBrandRepo, $categoryRepo, $sizeGuideMeasureRepo) {
            $categoryDTO = $categoryRepo->findOneByPath($categoryPath);
            $insertSizeGuideFunc = function ($datasSizeGuide, $sizeName) use ($brandDTO, $categoryDTO, $sizeBrandRepo, $sizeGuideMeasureRepo) {
                if (!is_array($datasSizeGuide)) {
                    return;
                }
                $datas = $this->formatFixturesDatas($datasSizeGuide);
                $datas = array_merge($this->getEmptyDatas(), $datas);
                $sizeBrandRepo->insert($brandDTO, $sizeName);
                $sizeGuideMeasureRepo->insert($brandDTO, $sizeName, 'category', $categoryDTO->id, $datas);
            };
            array_walk($datasFixtures, $insertSizeGuideFunc);
        };
    }

    /**
     * Format fixtures.
     *
     * @param $fixtures
     *
     * @return array
     */
    private function formatFixturesDatas($fixtures): array
    {
        $datas = [];
        foreach ($fixtures as $bodyPart => $values) {
            if (!in_array($bodyPart, Body::getSlugs())) {
                continue;
            }
            $delta = Body::NECK == $bodyPart ? self::DELTA_NECK : self::DELTA;
            if (!is_array($values)) {
                $datas[$bodyPart.'_min'] = floatval($values) - $delta;
                $datas[$bodyPart.'_med'] = $values;
                $datas[$bodyPart.'_max'] = floatval($values) + $delta;
            }
            if (is_array($values) && 1 == count($values)) {
                $datas[$bodyPart.'_min'] = floatval($values[0]) - $delta;
                $datas[$bodyPart.'_med'] = $values[0];
                $datas[$bodyPart.'_max'] = floatval($values[0]) + $delta;
            }
            if (is_array($values) && 2 == count($values)) {
                $datas[$bodyPart.'_min'] = floatval(min($values));
                $datas[$bodyPart.'_max'] = floatval(max($values));
            }
        }

        return $datas;
    }

    /**
     * @return array
     */
    private function getEmptyDatas(): array
    {
        $parts = [];
        foreach (Body::getSlugs() as $slug) {
            $parts[$slug.'_min'] = null;
            $parts[$slug.'_med'] = null;
            $parts[$slug.'_max'] = null;
        }

        return $parts;
    }
}
