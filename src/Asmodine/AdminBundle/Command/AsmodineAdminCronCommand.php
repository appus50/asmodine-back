<?php

namespace Asmodine\AdminBundle\Command;

use Asmodine\AdminBundle\DTO\CatalogDTO;
use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Asmodine\CommonBundle\DTO\BrandDTO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AsmodineAdminCronCommand.
 */
class AsmodineAdminCronCommand extends AbstractAsmodineCommand
{
    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:admin:cron')
            ->setDescription('Automated tasks')
            ->addOption('import-catalogs', null, InputOption::VALUE_NONE, 'Import all active catalogs')
            ->addOption('import-brands', null, InputOption::VALUE_NONE, 'Import all active brands')
            ->addOption('status-products', null, InputOption::VALUE_NONE, 'Enable/Disable products')
            ->addOption('sas-update', null, InputOption::VALUE_NONE, 'Enable/Disable products')
            ->addOption('populate-elasticsearch', null, InputOption::VALUE_NONE, 'Send ');
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
        if ($input->getOption('import-catalogs')) {
            $catalogs = $this->getContainer()->get('asmodine.admin.repository.catalog')->findAll();
            array_map(
                function (CatalogDTO $catalog) {
                    $this->runCommand('asmodine:admin:catalog:import', ['slug' => $catalog->slug]);
                },
                $catalogs
            );
        }
        if ($input->getOption('import-brands')) {
            $brands = $this->getContainer()->get('asmodine.admin.repository.brand')->findAll();
            array_map(
                function (BrandDTO $brand) {
                    $this->runCommand('asmodine:admin:brand:import', ['slug' => $brand->slug]);
                },
                $brands
            );
        }
        if ($input->getOption('import-catalogs') || $input->getOption('import-brands')) {
            $this->runCommand('asmodine:admin:associate', []);
        }

        if ($input->getOption('sas-update')) {
            $this->runCommand('asmodine:sas:note', []);
            $this->runCommand('asmodine:sas:sizeguide', []);
            $this->runCommand('asmodine:sas:user', []);
        }
        if ($input->getOption('populate-elasticsearch')) {
            $this->runCommand('asmodine:sas:elastic:populate', []);
        }
    }
}
