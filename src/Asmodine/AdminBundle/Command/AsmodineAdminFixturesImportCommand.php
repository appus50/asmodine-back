<?php

namespace Asmodine\AdminBundle\Command;

use Asmodine\AdminBundle\Model\Category;
use Asmodine\AdminBundle\Model\Color;
use Asmodine\AdminBundle\Model\ColorFilter;
use Asmodine\AdminBundle\Model\ColorMorphotype;
use Asmodine\AdminBundle\Model\Style;
use Asmodine\AdminBundle\Model\StyleMorphology;
use Asmodine\AdminBundle\Repository\CategoryRepository;
use Asmodine\AdminBundle\Repository\ColorFilterRepository;
use Asmodine\AdminBundle\Repository\ColorMorphotypeRepository;
use Asmodine\AdminBundle\Repository\ColorRepository;
use Asmodine\AdminBundle\Repository\StyleCategoryRepository;
use Asmodine\AdminBundle\Repository\StyleMorphologyRepository;
use Asmodine\CommonBundle\Command\AbstractAsmodineFixturesImportCommand;
use Asmodine\CommonBundle\DTO\CategoryDTO;
use Asmodine\CommonBundle\DTO\ColorDTO;
use Asmodine\CommonBundle\Service\ElasticsearchService;
use Asmodine\CommonBundle\Util\Str;
use Asmodine\CommonBundle\Util\Utils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Class AsmodineAdminFixturesImportCommand
 * Import Fixtures.
 */
class AsmodineAdminFixturesImportCommand extends AbstractAsmodineFixturesImportCommand
{

    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        parent::configure();
        $this->addOption('noes',null, InputOption::VALUE_NONE,'pas de config Elastic Search si activé');
        $this->addOption('esonly',null, InputOption::VALUE_NONE,'créer uniqument les indexes elasticsearch');
    }

    /**
     * @see Command::execute()
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('noes')) {
            $this->createElasticsearchIndexes();
        }
        if ($input->getOption('esonly')) {
            return 1;
        }
        $fixtures = [
            'category.asmodine' => $this->insertCategories(),
            'color.filter' => $this->insertFilterColors(),
            'color.asmodine' => $this->insertColors(),
            'color.morphotype' => $this->insertColorNote(),
            'style.asmodine' => $this->insertStyles(),
            'style.morphology' => $this->insertStyleNote(),
        ];
        array_walk($fixtures, $this->insertFixtures(__DIR__));

        // Import Developpement Fixtures (Must be extend AbstractFixturesPHP)
        $finder = new Finder();
        $finder->files()->in(__DIR__.'/../DataFixtures')->name('*Fixtures.php');
        $namespace = "Asmodine\AdminBundle\DataFixtures\PHP\\";
        $files = iterator_to_array($finder->getIterator());
        sort($files);
        array_map($this->insertFixturesPHP($namespace), $files);
    }

    /**
     * Insert categories in database.
     *
     * @return \Closure
     */
    private function insertCategories(): \Closure
    {
        return function ($categories) {
            /** @var CategoryRepository $morphotypeRepo */
            $morphotypeRepo = $this->getContainer()->get('asmodine.admin.repository.category');

            $insert = function ($fixture) use ($morphotypeRepo) {
                $category = new Category($fixture['name']);
                $category->enable();
                if (isset($fixture['position'])) {
                    $category->setPosition($fixture['position']);
                }
                if (isset($fixture['icon'])) {
                    $category->setIcon($fixture['icon']);
                }
                if (isset($fixture['parent'])) {
                    $category->setParentPath($fixture['parent']);
                }
                if (isset($fixture['gender'])) {
                    $category->setGender($fixture['gender']);
                }
                if (isset($fixture['disable'])) {
                    $category->disable();
                }
                $morphotypeRepo->insert($category);
            };

            array_walk($categories, $insert);
        };
    }

    /**
     * Insert Asmodine colors in database.
     *
     * @return \Closure
     */
    private function insertFilterColors(): \Closure
    {
        return function ($colors) {
            /** @var ColorFilterRepository $morphotypeRepo */
            $colorRepo = $this->getContainer()->get('asmodine.admin.repository.color_filter');

            $insert = function ($name, $slug) use ($colorRepo) {
                $color = new ColorFilter($slug, $name);
                $colorRepo->insert($color);
            };

            array_walk($colors, $insert);
        };
    }

    /**
     * Insert Asmodine colors in database.
     *
     * @return \Closure
     */
    private function insertColors(): \Closure
    {
        return function ($colors) {
            /** @var ColorRepository $morphotypeRepo */
            $colorRepo = $this->getContainer()->get('asmodine.admin.repository.color');

            $insert = function ($fixture) use ($colorRepo) {
                $color = new Color($fixture['name'], $fixture['hexa'], $fixture['filter']);
                $colorRepo->insert($color);
            };

            array_walk($colors, $insert);
        };
    }

    /**
     * Insert Color/Morphotype association with notes.
     *
     * @return \Closure
     */
    private function insertColorNote(): \Closure
    {
        return function ($colorsMorpho) {
            /** @var ColorRepository $morphotypeRepo */
            $colorRepo = $this->getContainer()->get('asmodine.admin.repository.color');
            /** @var ColorMorphotypeRepository $colorMorphoRepo */
            $colorMorphoRepo = $this->getContainer()->get('asmodine.admin.repository.color_morphotype');

            $insert = function (array $datas) use ($colorRepo, $colorMorphoRepo) {
                $colorMorpho = new ColorMorphotype($datas[0], $datas[1], $datas[2]);
                /** @var ColorDTO $color */
                $color = $colorRepo->findOneByName($colorMorpho->getColorName());
                $colorMorphoRepo->insert($color->id, $colorMorpho);
            };
            Utils::applyFunctionToArray($insert, $colorsMorpho);
        };
    }

    /**
     * Insert Asmodine Style.
     *
     * @return \Closure
     */
    private function insertStyles(): \Closure
    {
        return function ($styles) {
            /** @var StyleCategoryRepository $styleRepo */
            $styleRepo = $this->getContainer()->get('asmodine.admin.repository.style_category');

            $insert = function (array $datas) use ($styleRepo) {
                $category = $this->findCategoryByPath($datas);
                $style = new Style($datas[4]);
                $styleRepo->insert($category->id, $style);
            };
            Utils::applyFunctionToArray($insert, $styles);
        };
    }

    /**
     * Insert Style Note.
     *
     * @return \Closure
     */
    private function insertStyleNote(): \Closure
    {
        return function ($stylesMorpho) {
            /** @var StyleCategoryRepository $styleRepo */
            $styleRepo = $this->getContainer()->get('asmodine.admin.repository.style_category');
            /** @var StyleMorphologyRepository $styleMorphoRepo */
            $styleMorphoRepo = $this->getContainer()->get('asmodine.admin.repository.style_morphology');

            $insert = function (array $datas) use ($styleRepo, $styleMorphoRepo) {
                $category = $this->findCategoryByPath($datas);
                $style = $styleRepo->findOneByName($category->id, $datas[3]);
                $styleMorpho = new StyleMorphology($datas[3], $datas[5], $datas[6], $datas[4], $datas[7]);
                $styleMorphoRepo->insert($style->id, $styleMorpho);
            };
            Utils::applyFunctionToArray($insert, $stylesMorpho);
        };
    }

    /**
     * Find Category.
     *
     * @param array $datas Hierarchy of categories[ 'Cat', 'SubCat', 'SubSubCat', ...]
     * @param int   $depth
     *
     * @return CategoryDTO
     *
     * @throws \Asmodine\CommonBundle\Exception\NotFoundEntityException
     */
    private function findCategoryByPath(array $datas, int $depth = 3): CategoryDTO
    {
        /** @var CategoryRepository $categoryRepo */
        $categoryRepo = $this->getContainer()->get('asmodine.admin.repository.category');

        $slugs = array_map(
            function ($catName) {
                return Str::slugify($catName);
            },
            array_slice($datas, 0, $depth)
        );
        $path = '/'.implode('/', $slugs);

        return $categoryRepo->findOneByPath($path);
    }

    /**
     * Create Indexes of Elasticsearch (Admin + SAS).
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_index_management_operations.html
     *
     * @throws \Asmodine\CommonBundle\Exception\EnumParameterException
     */
    private function createElasticsearchIndexes(): void
    {
        $this->write('Elasticsearch index : ', false);
        /** @var ElasticsearchService $elastic */
        $elastic = $this->getContainer()->get('asmodine.common.elasticsearch');

        $properties = [
            ElasticsearchService::MODEL => [
                'id' => ['type' => 'integer'],
                'slug' => ['type' => 'keyword'],
                'model_id' => ['type' => 'keyword'],

                'brand_id' => ['type' => 'integer'],
                'brand_name' => ['type' => 'keyword'],

                'category_depth0_id' => ['type' => 'integer'],
                'category_depth1_id' => ['type' => 'integer'],
                'category_depth2_id' => ['type' => 'integer'],
                'category_depth0' => ['type' => 'text', 'analyzer' => 'french'],
                'category_depth1' => ['type' => 'text', 'analyzer' => 'french'],
                'category_depth2' => ['type' => 'text', 'analyzer' => 'french'],

                'name' => ['type' => 'text', 'analyzer' => 'french'],
                'reference' => ['type' => 'text', 'analyzer' => 'standard'],
                'description_short' => ['type' => 'text', 'analyzer' => 'french'],
                'description' => ['type' => 'text', 'analyzer' => 'french'],
                'composition' => ['type' => 'text', 'analyzer' => 'french'],

                'unit_price' => ['type' => 'float'],
                'currency' => ['type' => 'keyword'],

                'discount' => ['type' => 'boolean'],
                'discount_old_price' => ['type' => 'float'],
                'discount_type' => ['type' => 'keyword'],
                'discount_pourcent' => ['type' => 'float'],
                'discount_amount' => ['type' => 'float'],

                'image' => ['type' => 'text'],

                'products' => [
                    'type' => 'nested',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'size' => ['type' => 'keyword'],
                        'color' => ['type' => 'text'],
                        'color_filter' => ['type' => 'keyword'],
                        'url' => ['type' => 'text'],
                        'enabled' => ['type' => 'integer'],
                    ],
                ],
            ],
            ElasticsearchService::IMAGE => [
                'id' => ['type' => 'integer'],
                'type' => ['type' => 'keyword'],
                'external_id' => ['type' => 'integer'],
                'link' => ['type' => 'text', 'analyzer' => 'standard'],
                'position' => ['type' => 'short'],
                'enabled' => ['type' => 'boolean'],
            ],
            ElasticsearchService::SIZE_GUIDE => [
                'product_id' => ['type' => 'integer'],
                'body_part' => ['type' => 'keyword'],
                'min' => ['type' => 'integer'],
                'max' => ['type' => 'integer'],
            ],
            ElasticsearchService::ADVICE => [
                'model_id' => ['type' => 'integer'],
                'product_id' => ['type' => 'integer'],
                'user_id' => ['type' => 'integer'],

                'note_color' => ['type' => 'integer'],
                'note_size' => ['type' => 'float'],
                'note_style' => ['type' => 'integer'],

                'note_advice' => ['type' => 'float'],
                'note_ranking' => ['type' => 'float'],
            ],
        ];

        $elastic->createIndexes($properties);
        $this->write($this->trans('command.result.ok'));
    }
}
