<?php

namespace Asmodine\AdminBundle\Command;

use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Asmodine\CommonBundle\DTO\BrandDTO;
use Asmodine\CommonBundle\Repository\ElasticsearchPushInterface;
use Asmodine\CommonBundle\Service\ElasticsearchService;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class AsmodineAdminElasticsearchPopulateCommand.
 */
class AsmodineAdminElasticsearchPopulateCommand extends AbstractAsmodineCommand
{
    /**
     * @var ElasticsearchService
     */
    private $service;

    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:admin:elasticsearch:populate')
            ->setDescription('Push models and/or products to Elasticsearch')
            ->addOption('model', 'm', InputOption::VALUE_NONE, 'Bulk Models/products')
            ->addOption('image', 'i', InputOption::VALUE_NONE, 'Bulk Images')
            ->addOption('sizeguide', 's', InputOption::VALUE_NONE, 'Size Guide');
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
        $this->service = $this->getContainer()->get('asmodine.common.elasticsearch');
        $noOption = !$input->getOption('model') && !$input->getOption('image') && !$input->getOption('sizeguide');
        $bulkModels = $input->getOption('model') || $noOption;
        $bulkImages = $input->getOption('image') || $noOption;
        $bulkSizeGuide = $input->getOption('sizeguide') || $noOption;

        if ($bulkModels) {
            $brands = $this->getContainer()->get('asmodine.admin.repository.brand')->findAll();
            $progress = new ProgressBar($this->output, count($brands));
            $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% - %message%');
            $progress->start();

            array_map(
                function (BrandDTO $brand) use ($progress) {
                    if ($brand->enabled && !in_array($brand->slug, ['asos', 'boohoo'])) {
                        $progress->setMessage($brand->name);
						
                        $process = new Process('php bin/console asmodine:admin:elasticsearch:bulkmodel '.$brand->slug);
                        $process->setTimeout(30000);
                        $process->start();

                        foreach ($process as $type => $data) {
                            echo $data;
                        }
                    }
                    $progress->advance();
                },
                $brands
            );
            $output->writeln('');
        }
        if ($bulkImages) {
            $repo = $this->getContainer()->get('asmodine.admin.repository.image');
            $this->bulk(ElasticsearchService::IMAGE, $repo, '%d', ['id'], 10000);
        }
        if ($bulkSizeGuide) {
            $repo = $this->getContainer()->get('asmodine.sizeadvisor.repository.size_guide');
            $this->bulk(ElasticsearchService::SIZE_GUIDE, $repo, 'u%sp%s', ['product_id', 'body_part'], 10000);
        }
    }

    /**
     * Common bulk method.
     *
     * @param string                     $type
     * @param ElasticsearchPushInterface $repository
     * @param string                     $formatId
     * @param array                      $paramsFormatId
     * @param int                        $batchSize
     */
    private function bulk(string $type, ElasticsearchPushInterface $repository, string $formatId, array $paramsFormatId, int $batchSize)
    {
        $this->sfStyle->section('Bulk '.ucfirst($type).'s');

        $this->output->write('Be patient...', true);
        try {
            $pRepo = null;
            if (ElasticsearchService::MODEL == $type) {
                $pRepo = $this->getContainer()->get('asmodine.admin.repository.product');
            }
            $nb = $this->service->bulk($type, $repository, $formatId, $paramsFormatId, $batchSize, [], $pRepo);
            if ($nb > 0) {
                $this->sfStyle->success(number_format($nb, 0, ',', ' ').' items have been added or updated.');
            }
            if (0 == $nb) {
                $this->sfStyle->comment('No addition or update');
            }
        } catch (\Exception $exception) {
            $this->sfStyle->error($exception->getMessage());
        } finally {
            $this->sfStyle->block('Execution time :'.$this->getDuration());
        }
    }
}
