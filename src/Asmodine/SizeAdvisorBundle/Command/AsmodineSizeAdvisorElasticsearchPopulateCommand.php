<?php

namespace Asmodine\SizeAdvisorBundle\Command;

use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Asmodine\CommonBundle\Repository\ElasticsearchPushInterface;
use Asmodine\CommonBundle\Service\ElasticsearchService;
use Asmodine\SizeAdvisorBundle\Repository\UserProductScoreRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AsmodineSizeAdvisorElasticsearchPopulateCommand.
 */
class AsmodineSizeAdvisorElasticsearchPopulateCommand extends AbstractAsmodineCommand
{
    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:sizeadvisor:elasticsearch:populate')
            ->setAliases(['asmodine:sas:elastic:populate'])
            ->setDescription('Updating scores for one or more users')
            ->addOption('userid', 'u', InputOption::VALUE_OPTIONAL);
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
        /** @var ElasticsearchService $elastic */
        $elastic = $this->getContainer()->get('asmodine.common.elasticsearch');

        /** @var UserProductScoreRepository $userProductScore */
        $userProductScore = $this->getContainer()->get('asmodine.sizeadvisor.repository.user_product_score');

        $sqlParams = [];
        $userId = $input->getOption('userid');
        if (!is_null($userId)) {
            $sqlParams['user_id'] = $userId;
        }

        $this->sfStyle->section('Bulk Users/Products Scores');
        $this->bulk($elastic, $userProductScore, ElasticsearchService::ADVICE, 'u%sp%s', ['user_id', 'product_id'], $sqlParams);
    }

    /**
     * @param ElasticsearchService       $elastic
     * @param ElasticsearchPushInterface $repository
     * @param string                     $esType
     * @param string                     $formatId
     * @param array                      $paramsFormatId
     * @param array                      $sqlParams
     */
    private function bulk(ElasticsearchService $elastic, ElasticsearchPushInterface $repository, string $esType, string $formatId, array $paramsFormatId, array $sqlParams): void
    {
        $this->output->write('Be patient...', true);

        try {
            $nb = $elastic->bulk($esType, $repository, $formatId, $paramsFormatId, 15000, $sqlParams);
            if ($nb > 0) {
                $this->sfStyle->success(number_format($nb, 0, ',', ' ').' items have been added or updated.');
            }
            if (0 == $nb) {
                $this->sfStyle->comment('No addition or update');
            }
        } catch (\Exception $exception) {
            echo $exception->getTraceAsString();
            $this->sfStyle->error($exception->getMessage());
        } finally {
            $this->sfStyle->block('Total execution time :'.$this->getDuration());
        }
    }
}
