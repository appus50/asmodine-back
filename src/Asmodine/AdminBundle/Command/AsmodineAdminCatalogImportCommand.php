<?php

namespace Asmodine\AdminBundle\Command;

use Asmodine\AdminBundle\DTO\CatalogBrandDTO;
use Asmodine\AdminBundle\DTO\CatalogDTO;
use Asmodine\AdminBundle\Exception\ImportException;
use Asmodine\AdminBundle\Model\Catalog;
use Asmodine\AdminBundle\Repository\CatalogBrandRepository;
use Asmodine\AdminBundle\Repository\CatalogRepository;
use Asmodine\AdminBundle\Service\ImportCatalogService;
use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Asmodine\CommonBundle\Exception\NullException;
use Asmodine\CommonBundle\Util\FileUtils;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AsmodineAdminCatalogImportCommand
 * import & format catalog.
 */
class AsmodineAdminCatalogImportCommand extends AbstractAsmodineCommand
{
    /**
     * @var ImportCatalogService
     */
    private $service;

    /**
     * @var CatalogRepository
     */
    private $catalogRepo;

    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:admin:catalog:import')
            ->setDescription('Load and Format Catalog in DB.')
            ->addArgument(
                'slug',
                InputArgument::REQUIRED,
                'catalog slug ("list" to display all catalogs)'
            )
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Path of file if origin=manual'
            );
    }

    /**
     * @see Command::execute()
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     *
     * @throws NullException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->catalogRepo = $this->getContainer()->get('asmodine.admin.repository.catalog');
        $slug = trim(strtolower($input->getArgument('slug')));
        $fileOption = $input->getOption('file');

        if ('list' == $slug) {
            $this->showList();

            return;
        }

        $catalog = $this->getCatalog($slug);

        $this->service = $this->getContainer()->get('asmodine.admin.catalog.import');

        /** @var CatalogBrandRepository $catalogBrandRepo */
        $catalogBrandRepo = $this->getContainer()->get('asmodine.admin.repository.catalog_brand');

        try {
            $this->sfStyle->title('Catalog : '.$catalog->getName().' - '.$catalog->getOrigin());
            $initTime = $this->time;

            $this->sfStyle->section('In Progress... (Download/Copy)');
            $csvFile = $this->load($catalog, $fileOption);
            $this->sfStyle->note('URL : '.$this->service->getUrl());
            $this->sfStyle->success($csvFile->getFilename().' : '.$csvFile->readingFileSize().$this->getDuration());

            $this->sfStyle->section('Create temporary table');
            $nbRow = $this->service->createTableTemporary($catalog, $csvFile);
            $this->checkRow($nbRow);
            $this->sfStyle->success('Nb rows : '.$nbRow.$this->getDuration());

            $this->sfStyle->section('Format temporary table');
            $nbRow = $this->service->formatTableTemporary($catalog);
            $this->checkRow($nbRow);
            $this->sfStyle->success('Nb rows : '.$nbRow.$this->getDuration());

            $this->sfStyle->section('Clean format temporary table');
            $nbRow = $this->service->cleanFormatTableTemporary($catalog);
            $this->checkRow($nbRow);
            $this->sfStyle->success('Nb rows : '.$nbRow.$this->getDuration());

            $this->sfStyle->section('Drop temporary table');
            $name = $this->service->dropImportTable($catalog);
            $this->sfStyle->success('Table '.$name.' has been deleted'.$this->getDuration());

            $this->time = $initTime;
            $this->sfStyle->note('End of import of the catalog `'.$catalog->getName().'`. Total time :'.$this->getDuration());
        } catch (\Exception $exception) {
            $this->sfStyle->error($exception->getMessage());
            $messages =['Erreur lors de l\'import du catalogue', 'Désactivation des marques associées'];

            $catalogDTO = $this->catalogRepo->findOneBySlug($slug);

            $connection = $this->getContainer()->get('doctrine.dbal.default_connection');
            $statement = $connection->prepare('SELECT * FROM `back_catalog_brand` WHERE `catalog_id` = :catalog_id');
            $statement->bindValue('catalog_id', $catalogDTO->id);

            $statement->execute();
            while ($row = $statement->fetch()) {
                $st = $connection->prepare('UPDATE `back_brand` SET enabled = false WHERE id = :id');
                $st->bindValue('id', $row['brand_id']);
                $st->execute();

                $st2 = $connection->prepare('SELECT * FROM `back_brand` WHERE id = :id');
                $st2->bindValue('id', $row['brand_id']);
                $st2->execute();
                $messages[] = ' - '.$st2->fetch()['name'];
            }
            $this->sfStyle->error($messages);
        }
    }

    /**
     * Show list of catalogs.
     */
    private function showList(): void
    {
        $this->sfStyle->title('Catalogs');
        $catalogs = $this->catalogRepo->findAll();

        $datas = [];
        /** @var CatalogDTO $catalog */
        foreach ($catalogs as $catalog) {
            $datas[] = [$catalog->name, $catalog->origin, $catalog->slug];
        }
        $this->sfStyle->table(['name', 'origin', 'slug'], $datas);
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

            return new ImportException();
        }
    }

    /**
     * Find Catalog in DB.
     *
     * @param string $slug
     *
     * @return Catalog
     *
     * @throws \Exception
     */
    private function getCatalog(string $slug): Catalog
    {
        try {
            /** @var CatalogDTO $catalogDTO */
            $catalogDTO = $this->catalogRepo->findOneBySlug($slug);
        } catch (\Exception $e) {
            $this->sfStyle->error('Slug unknown : '.$slug);
            $this->sfStyle->error(substr($e->getMessage(), 0, 255));

            throw $e;
        }

        /** Serializer $serializer */
        $serializer = $this->getContainer()->get('jms_serializer');
        $configuration = $serializer->deserialize($catalogDTO->configuration, Catalog\Configuration::class, 'json');

        /** @var Catalog $catalog */
        $catalog = Catalog::loadDTO($catalogDTO, $configuration);

        return $catalog;
    }

    /**
     * Create CSV File Ready to download.
     *
     * @param Catalog $catalog
     * @param null|string $file
     *
     * @return FileUtils
     *
     * @throws NullException
     * @throws \Exception
     */
    private function load(Catalog $catalog, ?string $file): FileUtils
    {
        if (Catalog::ORIGIN_MANUAL != $catalog->getOrigin()) {
            $file = $this->service->importFromURL($catalog);

            return $file;
        }

        if (Catalog::ORIGIN_MANUAL == $catalog->getOrigin()) {
            if (0 == strpos($catalog->getConfiguration()->getManualConfiguration()->getPath(), 'http')) {
                $file = $this->service->importFromURL($catalog);

                return $file;
            }
        }

        if (is_null($file)) {
            throw new NullException('file option is null (path of csv) !');
        }

        $file = $this->service->importFromFile($catalog, $file);

        return $file;
    }
}
