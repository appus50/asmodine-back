<?php

namespace Asmodine\AdminBundle\Command;

use Asmodine\AdminBundle\Repository\BrandRepository;
use Asmodine\AdminBundle\Repository\ModelRepository;
use Asmodine\AdminBundle\Repository\ProductRepository;
use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Asmodine\CommonBundle\Service\ElasticsearchService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AsmodineAdminElasticsearchBulkModelCommand.
 */
class AsmodineAdminElasticsearchBulkModelCommand extends AbstractAsmodineCommand
{
    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:admin:elasticsearch:bulkmodel')
            ->setDescription('Push models to Elasticsearch')
            ->addArgument('brand', InputOption::VALUE_REQUIRED, 'Slug Model');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     *
     * @throws \Asmodine\CommonBundle\Exception\NotFoundEntityException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ElasticsearchService $service */
        $service = $this->getContainer()->get('asmodine.common.elasticsearch');

        /** @var ModelRepository $modelRepo */
        $modelRepo = $this->getContainer()->get('asmodine.admin.repository.model');
        /** @var BrandRepository $brandRepo */
        $brandRepo = $this->getContainer()->get('asmodine.admin.repository.brand');
        $slug = $input->getArgument('brand');
        $brand = $brandRepo->findOneBySlug($slug);
        if (is_null($brand)) {
            $this->output->writeln('Unknow slug '.$slug);

            return;
        }
        /** @var ProductRepository $productRepo */
        $productRepo = $this->getContainer()->get('asmodine.admin.repository.product');

        $service->bulk(ElasticsearchService::MODEL, $modelRepo, '%d', ['id'], 500, ['brand_id' => $brand->id], $productRepo);
    }
}
