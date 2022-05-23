<?php

namespace Asmodine\SizeAdvisorBundle\Command;

use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AsmodineSizeAdvisorNoteCommand.
 */
class AsmodineSizeAdvisorFullCommand extends AbstractAsmodineCommand
{
    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:sizeadvisor:full')
            ->setAliases(['asmodine:sas:full'])
            ->setDescription('Updates SAS Note, SizeGuide and User score');
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
        $runCommand = function ($command) {
            $this->sfStyle->title('Command : '.$command);
            $command = $this->getApplication()->find($command);
            $arguments = ['command' => $command];
            $input = new ArrayInput($arguments);
            $command->run($input, $this->output);
        };
        $commands = ['asmodine:sas:note', 'asmodine:sas:sizeguide', 'asmodine:sas:user', 'asmodine:sas:elastic:populate'];
        array_map($runCommand, $commands);
    }
}
