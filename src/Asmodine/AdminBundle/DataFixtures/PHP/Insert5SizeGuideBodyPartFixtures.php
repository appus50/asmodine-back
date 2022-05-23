<?php

namespace Asmodine\AdminBundle\DataFixtures\PHP;

use Asmodine\AdminBundle\Repository\BrandRepository;
use Asmodine\AdminBundle\Repository\CategoryRepository;
use Asmodine\AdminBundle\Repository\SizeGuideBodyPartRepository;
use Asmodine\CommonBundle\DataFixtures\PHP\AbstractFixturesPHP;
use Asmodine\CommonBundle\DTO\BrandDTO;
use Asmodine\CommonBundle\Model\Profile\Body;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Insert5SizeGuideBodyPartFixtures.
 */
class Insert5SizeGuideBodyPartFixtures extends AbstractFixturesPHP
{
    /**
     * Import SizeGuideBodyPart.
     */
    public function run(): void
    {
        try {
            $fileContent = file_get_contents(__DIR__.'/'.$this->locale.'/size_guide.body_part.yml');
            $sizeGuideMeasure = Yaml::parse($fileContent);
        } catch (\Exception $e) {
            throw $e;
        }
        array_walk($sizeGuideMeasure, $this->insertSizeGuideBodyPart());
    }

    /**
     * Insert Size Guide Body Part.
     *
     * @return \Closure
     */
    private function insertSizeGuideBodyPart(): \Closure
    {
        /** @var BrandRepository $brandRepo */
        $brandRepo = $this->container->get('asmodine.admin.repository.brand');

        return function ($sizeGuideMeasure, $brandSlug) use ($brandRepo) {
            $brandDTO = null;
            if ('default' != $brandSlug) {
                $brandDTO = $brandRepo->findOneBySlug($brandSlug);
            }
            if (count($sizeGuideMeasure) > 0) {
                array_walk($sizeGuideMeasure, $this->getOneSizeDatas($brandDTO));
            }
        };
    }

    /**
     * Insert One record in size guide body.
     *
     * @param BrandDTO $brandDTO
     *
     * @return \Closure
     */
    private function getOneSizeDatas(?BrandDTO $brandDTO): \Closure
    {
        /** @var CategoryRepository $categoryRepo */
        $categoryRepo = $this->container->get('asmodine.admin.repository.category');
        /** @var SizeGuideBodyPartRepository $sizeGuideBodyPartRepo */
        $sizeGuideBodyPartRepo = $this->container->get('asmodine.admin.repository.size_guide_body_part');

        return function ($datasFixtures, $categoryPath) use ($brandDTO, $categoryRepo, $sizeGuideBodyPartRepo) {
            $categoryDTO = $categoryRepo->findOneByPath($categoryPath);
            $datasFixtures = array_filter($datasFixtures, function ($d) {
                return in_array($d, Body::getSlugs());
            });
            $datas = [];
            foreach (Body::getSlugs() as $slug) {
                $datas[$slug] = in_array($slug, $datasFixtures);
            }
            $sizeGuideBodyPartRepo->insert($brandDTO, 'category', $categoryDTO->id, $datas);
        };
    }
}
