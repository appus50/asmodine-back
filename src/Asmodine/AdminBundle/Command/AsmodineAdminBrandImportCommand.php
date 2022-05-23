<?php

namespace Asmodine\AdminBundle\Command;

use Asmodine\AdminBundle\DTO\CatalogDTO;
use Asmodine\AdminBundle\Exception\ImportException;
use Asmodine\AdminBundle\Model\Brand;
use Asmodine\AdminBundle\Model\CatalogBrand\Configuration;
use Asmodine\AdminBundle\Repository\BrandRepository;
use Asmodine\AdminBundle\Repository\CatalogBrandRepository;
use Asmodine\AdminBundle\Repository\CatalogRepository;
use Asmodine\AdminBundle\Service\ImportBrandService;
use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Asmodine\CommonBundle\DTO\BrandDTO;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AsmodineAdminBrandImportCommand.
 */
class AsmodineAdminBrandImportCommand extends AbstractAsmodineCommand
{
    /**
     * @var CatalogBrandRepository
     */
    private $catalogBrandRepo;

    /**
     * @var CatalogRepository
     */
    private $catalogRepo;

    /**
     * @var BrandRepository
     */
    private $brandRepo;

    /**
     * @var ImportBrandService
     */
    private $service;

    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:admin:brand:import')
            ->setDescription('Insert or update models and products of a brand')
            ->addArgument(
                'slug',
                InputArgument::REQUIRED,
                'brand slug ("list" to display all brands)'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force Brand Import'
            );
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
        $this->brandRepo = $this->getContainer()->get('asmodine.admin.repository.brand');
        $this->catalogRepo = $this->getContainer()->get('asmodine.admin.repository.catalog');
        $this->catalogBrandRepo = $this->getContainer()->get('asmodine.admin.repository.catalog_brand');
        $slug = trim(strtolower($input->getArgument('slug')));

        if ('list' == $slug) {
            $this->showList();

            return;
        }
        $force = $input->getOption('force');

        $brand = $this->getBrand($slug);
        $this->service = $this->getContainer()->get('asmodine.admin.brand.import');

        $catalogsBrands = $this->catalogBrandRepo->findByBrand($brand);
        array_map($this->executeImportOn($brand, $force), $catalogsBrands);
    }

    /**
     * Run import on each related catalogs.
     *
     * @param Brand $brand
     * @param bool  $force
     *
     * @return \Closure
     */
    private function executeImportOn(Brand $brand, bool $force): \Closure
    {
        /** Serializer $serializer */
        $serializer = $this->getContainer()->get('jms_serializer');

        return function ($catalogBrandDTO) use ($brand, $force, $serializer) {
            /** @var CatalogDTO $catalogDTO */
            $catalogDTO = $this->catalogRepo->findById($catalogBrandDTO->catalogId);
            $this->service->init($brand, $catalogDTO);

            /** @var Configuration $configuration */
            $configuration = $serializer->deserialize($catalogBrandDTO->configuration, Configuration::class, 'json');

            $this->sfStyle->title('Brand : '.$brand->getName());
            $this->sfStyle->section('Check Formatted Catalog');
            $this->sfStyle->listing(
                [
                    'Latest catalog import :       '.(!is_null($catalogDTO->importedAt)
                        ? $catalogDTO->importedAt->format('H:i:s d/m/Y') : ' never'),
                    'Latest brand/catalog import : '.(!is_null($catalogBrandDTO->importedAt)
                        ? $catalogBrandDTO->importedAt->format('H:i:s d/m/Y') : ' never'),
                ]
            );

            if (!is_null($catalogDTO->importedAt) && !is_null($catalogBrandDTO->importedAt) && $catalogDTO->importedAt < $catalogBrandDTO->importedAt && !$force) {
                $this->sfStyle->caution('Brand has already been imported (use --force to import the associated catalogs again).');

                return;
            }

            // Step 0 : Force catalog import if it is not up to date
            if (is_null($catalogDTO->importedAt) || (!is_null($catalogDTO->importedAt) && !is_null($catalogBrandDTO->importedAt) && $catalogDTO->importedAt < $catalogBrandDTO->importedAt)) {
                $this->forceCatalogUpdate($catalogDTO);
                $this->sfStyle->title('End of catalog import, relaunch of import of the brand `'.$brand->getName().'`');
            }

            // Step 1 : Duplicate catalog to create specifiq brand catalog
            $initTime = $this->time;
            $this->sfStyle->section('Copy of the formatted catalog');
            $nbRow = $this->service->copyFormattedCatalog();
            $this->checkRow($nbRow);
            $this->sfStyle->success('Nb rows : '.$nbRow.$this->getDuration());

            /* Step 2 : Run SQL associated with brand and catalog configuration */
            $this->sfStyle->section('Apply Action');
            $execActions = function (Configuration\Action $action) {
                $this->service->executeAction($action);
            };
            $actions = $configuration->getActions();
            if (0 == count($actions)) {
                $this->sfStyle->note('No action');
            }
            array_map($execActions, $actions);
            $nbRow = $this->service->getNbRows();
            $this->checkRow($nbRow);
            $this->sfStyle->success('Nb rows : '.$nbRow.$this->getDuration());

            /* Step 3 : Keep or remove records */
            $this->sfStyle->section('Apply Simple Filter');
            $execSimpleFilters = function (Configuration\SimpleFilter $filter) {
                $this->service->executeSimpleFilter($filter);
            };
            $filters = $configuration->getSimpleFilters();
            if (0 == count($filters)) {
                $this->sfStyle->note('No filter');
            }
            array_map($execSimpleFilters, $filters);
            $this->service->removeIncompleteRow();
            $nbRow = $this->service->getNbRows();
            $this->checkRow($nbRow);
            $this->sfStyle->success('Nb rows : '.$nbRow.$this->getDuration());

            /* Step 4 : Create Tables Model & Product */
            $this->sfStyle->section('Create Temporary Tables Model and Product');
            $this->service->createModelAndProductTables($configuration);
            $nbRows = $this->service->getNbProductModelRows();
            $this->checkRow($nbRows['model']);
            $this->checkRow($nbRows['product']);
            $this->sfStyle->success('Nb rows (models: '.$nbRows['model'].'; products: '.$nbRows['product'].')'.$this->getDuration());

            /* Step 5 : Create or Update in Asmodine Table */
            $this->sfStyle->section('Update Tables : Color, Category, Size, Product, Model, Image');
            $this->service->updateAsmodineTables();
            $this->sfStyle->success('Done. '.$this->getDuration());

            /* Step 6 : Cleaning */
            $this->sfStyle->section('Cleaning');
            $this->service->clean();
            $this->sfStyle->success('Done. '.$this->getDuration());

            /* End */
            $this->catalogBrandRepo->setImported($brand, $catalogDTO);
            $this->time = $initTime;
            $this->sfStyle->note('End of import of the brand `'.$brand->getName().'`. Total time :'.$this->getDuration());
        };
    }

    /**
     * Show list of brands.
     */
    private function showList(): void
    {
        $this->sfStyle->title('Brands');
        $brands = $this->brandRepo->findAll();

        $datas = [];
        /** @var BrandDTO $brand */
        foreach ($brands as $brand) {
            $datas[] = [$brand->name, $brand->slug, substr($brand->description, 0, 47).'...'];
        }
        $this->sfStyle->table(['name', 'slug', 'description'], $datas);
    }

    /**
     * Find Brand in DB.
     *
     * @param string $slug
     *
     * @return Brand
     *
     * @throws \Exception
     */
    private function getBrand(string $slug): Brand
    {
        try {
            /** @var BrandDTO $brandDTO */
            $brandDTO = $this->brandRepo->findOneBySlug($slug);
        } catch (\Exception $e) {
            $this->sfStyle->error('Slug unknown : '.$slug);
            $this->sfStyle->error(substr($e->getMessage(), 0, 255));

            throw $e;
        }
        /** @var Brand $brand */
        $brand = Brand::loadDTO($brandDTO);

        return $brand;
    }

    /**
     * Force Update of Catalog.
     *
     * @param CatalogDTO $catalogDTO
     */
    private function forceCatalogUpdate(CatalogDTO $catalogDTO): void
    {
        $this->sfStyle->warning('Catalog must be updated');
        $command = $this->getApplication()->find('asmodine:admin:catalog:import');
        $arguments = [
            'command' => 'asmodine:admin:catalog:import',
            'slug' => $catalogDTO->slug,
        ];
        $catoalogImportInput = new ArrayInput($arguments);
        $command->run($catoalogImportInput, $this->output);
    }

    /**
     * Check if table is not empty and show message if not.
     *
     * @param int $nb
     *
     * @return ImportException
     */
    private function checkRow($nb)
    {
        if (0 == $nb) {
            $this->sfStyle->error('Table is empty !!!'.$this->getDuration());
            $this->write("\nEnd of Command", true);

            return new ImportException();
        }
    }
}
