<?php

namespace Asmodine\SizeAdvisorBundle\Command;

use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Asmodine\CommonBundle\Repository\ElasticsearchPushInterface;
use Asmodine\CommonBundle\Service\ElasticsearchService;
use Asmodine\SizeAdvisorBundle\Repository\UserProductScoreRepository;
use Asmodine\SizeAdvisorBundle\Service\UserScoreService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AsmodineSizeAdvisorUserScoreCommand.
 */
class AsmodineSizeAdvisorUserScoreCommand extends AbstractAsmodineCommand
{
    /**
     * @var UserScoreService
     */
    private $service;

    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:sizeadvisor:userscore')
            ->setAliases(['asmodine:sas:user'])
            ->setDescription('Updating scores for one or more users')
            ->addOption('userid', 'u', InputOption::VALUE_OPTIONAL)
            ->addOption('color', null, InputOption::VALUE_NONE, 'Color Scores')
            ->addOption('style', null, InputOption::VALUE_NONE, 'Style Scores')
            ->addOption('size', null, InputOption::VALUE_NONE, 'Size Guide Scores');
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
        $this->service = $this->getContainer()->get('asmodine.size_advisor.user_score');
        $this->sfStyle->title('Update scores');
        $this->output->write('Be patient...', true);
        $userId = $input->getOption('userid');
        if (!is_null($userId)) {
            $userId = (int) $userId;
        }

        $color = (bool) $input->getOption('color');
        $style = (bool) $input->getOption('style');
        $size = (bool) $input->getOption('size');
        if (!$color && !$style && !$size) {
            $color = true;
            $style = true;
            $size = true;
        }

        $formatResults = function ($action) {
            return [
                $action['name'],
                number_format($action['duration'], 3).' second'.($action['duration'] >= 2 ? 's' : ''),
            ];
        };

        try {
            $results = $this->service->run($color, $style, $size, $userId);
            $results = array_map($formatResults, $results);
            $this->sfStyle->table(['Note', 'Duration'], $results);
        } catch (\Exception $exception) {
            $this->getContainer()
                ->get('monolog.logger.asmodine_size_advisor_user_score')
                ->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            $this->sfStyle->error($exception->getMessage());
        }
        $this->sfStyle->block('Total execution time :'.$this->getDuration());

        /** @var ElasticsearchService $elastic */
        $elastic = $this->getContainer()->get('asmodine.common.elasticsearch');

        /** @var UserProductScoreRepository $userProductScore */
        $userProductScore = $this->getContainer()->get('asmodine.sizeadvisor.repository.user_product_score');

        $sqlParams = [];
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
