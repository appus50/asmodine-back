<?php

namespace Asmodine\AdminBundle\Command;

use Asmodine\AdminBundle\DTO\CatalogDTO;
use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Asmodine\CommonBundle\DTO\BrandDTO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AsmodineAdminFullImportCommand.
 */
class AsmodineAdminFullImportCommand extends AbstractAsmodineCommand
{
    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:admin:full:import')
            ->setDescription('Imports all brands and catalogs');
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
        $catalogs = $this->getContainer()->get('asmodine.admin.repository.catalog')->findAll();
        array_map(function (CatalogDTO $catalog) {
            if ($catalog->enabled) {
                $this->runCommand('asmodine:admin:catalog:import', ['slug' => $catalog->slug]);
            }
        }, $catalogs);

        $brands = $this->getContainer()->get('asmodine.admin.repository.brand')->findAll();
        array_map(function (BrandDTO $brand) {
            if ($brand->enabled) {
                $this->runCommand('asmodine:admin:brand:import', ['slug' => $brand->slug]);
            }
        }, $brands);
    }
}
