<?php

namespace Asmodine\SizeAdvisorBundle\Command;

use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Asmodine\SizeAdvisorBundle\Service\NoteService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AsmodineSizeAdvisorNoteCommand.
 */
class AsmodineSizeAdvisorNoteCommand extends AbstractAsmodineCommand
{
    /**
     * @var NoteService
     */
    private $service;

    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:sizeadvisor:note')
            ->setAliases(['asmodine:sas:note'])
            ->setDescription('Calculate the notes of each product according to its style, size, color')
            ->addOption('style', null, InputOption::VALUE_NONE, 'Calculate notes according to style')
            ->addOption('color', null, InputOption::VALUE_NONE, 'Calculate notes according to color');
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
        $this->service = $this->getContainer()->get('asmodine.size_advisor.note');

        $options = ['style', 'color'];
        $types = array_filter(
            $options,
            function ($option) use ($input) {
                return $input->getOption($option);
            }
        );
        if (0 == count($types)) {
            $types = $options;
        }

        $initTime = $this->time;
        $this->sfStyle->title('Calculation on : '.implode(', ', $types));
        $results = array_map($this->executeCalculationOf(), $types);
        $nb = array_sum($results);

        if (count($types) > 1) {
            $this->time = $initTime;
            $this->sfStyle->section('End of calculations');
            $this->sfStyle->block(['Found: '.number_format($nb, 0, ',', ' '), 'Total time :'.$this->getDuration()]);
        }
    }

    /**
     * Run calculation on each "product".
     *
     * @return \Closure
     */
    private function executeCalculationOf(): \Closure
    {
        return function ($type): int {
            $this->getDuration();
            $this->sfStyle->section('Start calculations on '.$type);
            try {
                $results = $this->service->run($type);
            } catch (\Exception $exception) {
                $this->sfStyle->error($exception->getMessage());

                return 0;
            }

            if (0 == $results) {
                $this->sfStyle->error(
                    [
                        'There are no notes. Check the logs',
                        'Execution time:'.$this->getDuration(),
                    ]
                );
            }
            if (0 < $results) {
                $this->sfStyle->success(
                    [
                        number_format($results, 0, ',', ' ').' calculated notes.',
                        'Execution time :'.$this->getDuration(),
                    ]
                );
            }

            return $results;
        };
    }
}
