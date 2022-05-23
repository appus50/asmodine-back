<?php

namespace Asmodine\AdminBundle\DataFixtures\PHP;

use Asmodine\AdminBundle\Repository\CategoryRepository;
use Asmodine\AdminBundle\Repository\SynonymRepository;
use Asmodine\CommonBundle\DataFixtures\PHP\AbstractFixturesPHP;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Insert5SizeGuideBodyPartFixtures.
 */
class Insert0SynonymFixtures extends AbstractFixturesPHP
{
    /**
     * Import SizeGuideBodyPart.
     */
    public function run(): void
    {
        foreach (['category', 'color', 'style'] as $type) {
            try {
                $fileContent = file_get_contents(__DIR__.'/'.$this->locale.'/synonym.'.$type.'.yml');
                $synonyms = Yaml::parse($fileContent);
            } catch (\Exception $e) {
                throw $e;
            }

            array_walk(
                $synonyms,
                function ($synonymDatas, $type) {
                    if (in_array($type, ['category', 'color'])) {
                        array_walk($synonymDatas, $this->insertSynonyms($type));
                    }
                    if ('style' == $type) {
                        array_walk($synonymDatas, $this->insertStyleSynonyms());
                    }
                }
            );
        }
    }

    /**
     * Insert Synonym.
     *
     * @param string $type
     *
     * @return \Closure
     *
     * @throws \Exception
     */
    private function insertSynonyms(string $type): \Closure
    {
        /** @var SynonymRepository $synoRepo */
        $synoRepo = $this->container->get('asmodine.admin.repository.synonym');

        return function ($synonyms, $name) use ($synoRepo, $type) {
            $insertFunc = function ($synonym) use ($synoRepo, $type, $name) {
                $columnName = 'name';
                if ('category' == $type) {
                    $columnName = 'path';
                }
                if (is_array($synonym)) {
                    $synoRepo->insert($type, $columnName, $name, $synonym['name'], $synonym['priority']);
                }
                if (is_string($synonym)) {
                    $synoRepo->insert($type, $columnName, $name, $synonym, 100);
                }
            };
            if (is_array($synonyms)) {
                array_map($insertFunc, $synonyms);
            }
        };
    }

    /**
     * Insert Style Synonym.
     *
     * @return \Closure
     *
     * @throws \Exception
     */
    private function insertStyleSynonyms(): \Closure
    {
        /** @var CategoryRepository $categoryReop */
        $categoryReop = $this->container->get('asmodine.admin.repository.category');

        /** @var SynonymRepository $synoRepo */
        $synoRepo = $this->container->get('asmodine.admin.repository.synonym');

        return function ($datas, $path) use ($categoryReop, $synoRepo) {
            $categoryDTO = $categoryReop->findOneByPath($path);
            $catId = $categoryDTO->id;

            $prepareInsertFunc = function ($synonyms, $style) use ($synoRepo, $catId) {
                $insertFunc = function ($synonym) use ($catId, $style, $synoRepo) {
                    $synonymName = is_array($synonym) ? $synonym['name'] : $synonym;
                    $synonymPriority = is_array($synonym) ? $synonym['priority'] : 100;
                    $synoRepo->insertStyle($style, $catId, $synonymName, $synonymPriority);
                };
                array_map($insertFunc, $synonyms);
            };
            array_walk($datas, $prepareInsertFunc);
        };
    }
}
