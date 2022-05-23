<?php

namespace Asmodine\AdminBundle\Command;

use Asmodine\AdminBundle\Repository\ModelRepository;
use Asmodine\AdminBundle\Repository\ProductRepository;
use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AsmodineAdminProductActivateCommand
 * import & format catalog.
 */
class AsmodineAdminProductActivateCommand extends AbstractAsmodineCommand
{
    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:admin:product:activation')
            ->setDescription('Enable/Disable Product.');
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
        /** @var ProductRepository $productRepo */
        $productRepo = $this->getContainer()->get('asmodine.admin.repository.product');
        /** @var ModelRepository $modelRepo */
        $modelRepo = $this->getContainer()->get('asmodine.admin.repository.model');
        $this->sfStyle->title('Enable/Disable Products/Models');

        $this->sfStyle->section('Products');
        $productRepo->disableProductsAuto();
        $this->sfStyle->success($this->getDuration());

        $this->sfStyle->section('Models');
        $modelRepo->disableModelsAuto();
        $this->sfStyle->success($this->getDuration());
    }
}
