<?php

namespace Asmodine\AdminBundle\Command;

use Asmodine\AdminBundle\Service\AssociateService;
use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AsmodineAdminBrandImportCommand.
 */
class AsmodineAdminAssociateCommand extends AbstractAsmodineCommand
{
    /**
     * @var AssociateService
     */
    private $service;

    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:admin:associate')
            ->setDescription('Associate brand category, style and/or color with Asmodine category, style and/or color')
            ->addOption('category', null, InputOption::VALUE_NONE, 'Associates categories')
            ->addOption('style', null, InputOption::VALUE_NONE, 'Associates styles')
            ->addOption('color', null, InputOption::VALUE_NONE, 'Associates colors');
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
        $this->service = $this->getContainer()->get('asmodine.admin.associate');

        $options = ['category', 'style', 'color'];
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
        $this->sfStyle->title('Association of : '.implode(', ', $types));
        $results = array_map($this->executeAssociationOn(), $types);
        $nb = array_sum($results);

        if (count($types) > 1) {
            $this->time = $initTime;
            $this->sfStyle->section('End of associations');
            $this->sfStyle->block(['Found: '.$nb, 'Total time :'.$this->getDuration()]);
        }
    }

    /**
     * Run association on each "entity".
     *
     * @return \Closure
     */
    private function executeAssociationOn(): \Closure
    {
        return function ($type): int {
            $this->sfStyle->section('Search association on '.$type);
            try {
                $results = $this->service->run($type);
            } catch (\Exception $exception) {
                $this->sfStyle->error($exception->getMessage());

                return 0;
            }

            if (0 == $results['begin']) {
                $this->sfStyle->success(['All objects are associated!', 'Execution time:'.$this->getDuration()]);

                return 0;
            }

            $nb = $results['begin'] - $results['end'];
            $message = [];
            $style = null;
            if (0 == $nb) {
                $message[] = 'No association.';
                $type = 'INFO';
                $style = 'fg=white;bg=magenta';
            }
            if (0 != $nb) {
                $message[] = $nb.' association'.($nb > 1 ? 's' : '').' found.';
                $type = 'OK';
                $style = 'fg=black;bg=green';
            }
            $pourcent = $results['all'] > 0 ? round((100.0 * $results['end']) / $results['all'], 2) : 'NA';
            $message[] = $results['end'].' non-associated objects. ('.$pourcent.'%)';
            $message[] = 'Execution time:'.$this->getDuration();

            $this->sfStyle->block($message, $type, $style, ' ', true);

            return $nb;
        };
    }
}
