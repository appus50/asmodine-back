<?php

namespace Asmodine\SizeAdvisorBundle\Command;

use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Asmodine\SizeAdvisorBundle\Service\SizeGuideService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AsmodineSizeAdvisorSizeGuideCommand.
 */
class AsmodineSizeAdvisorSizeGuideCommand extends AbstractAsmodineCommand
{
    /**
     * @var SizeGuideService
     */
    private $service;

    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:sizeadvisor:sizeguide')
            ->setAliases(['asmodine:sas:sizeguide'])
            ->setDescription('Generates the full size guide by product.');
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
        $this->service = $this->getContainer()->get('asmodine.size_advisor.size_guide');
        $this->sfStyle->title('Construction of the size guide');
        $this->output->write('Be patient...', true);

        $formatResults = function ($action) {
            return [
                $action['name'],
                number_format($action['nb'], 0, ',', ' '),
                number_format($action['duration'], 3).' second'.($action['duration'] >= 2 ? 's' : ''),
                $action['temporary'] ? 'Yes' : 'No',
                $action['description'],
            ];
        };

        try {
            $results = $this->service->run();

            $results = array_map($formatResults, $results);
            $this->sfStyle->table(['Action', 'Number of records', 'Duration', 'Temporary Table', 'Description'], $results);
        } catch (\Exception $exception) {
            $this->getContainer()
                ->get('monolog.logger.asmodine_size_advisor_size_guide')
                ->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            $this->sfStyle->error($exception->getMessage());
        }
        $this->sfStyle->block('Total execution time :'.$this->getDuration());
    }
}
