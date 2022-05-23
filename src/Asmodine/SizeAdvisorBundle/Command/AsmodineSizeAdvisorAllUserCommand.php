<?php

namespace Asmodine\SizeAdvisorBundle\Command;

use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Asmodine\CustomerBundle\Repository\PhysicalProfileRepository;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AsmodineSizeAdvisorAllUserCommand.
 */
class AsmodineSizeAdvisorAllUserCommand extends AbstractAsmodineCommand
{
    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:sizeadvisor:alluser')
            ->setAliases(['asmodine:sas:alluser'])
            ->setDescription('Updating scores for all users. One by One');
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var PhysicalProfileRepository $phr */
        $phr = $this->getContainer()->get('asmodine.admin.repository.physical_profile');

        $ids = $phr->findAllOrderDesc();
        $commandName = 'asmodine:sizeadvisor:userscore';

        for ($i = 0; $i < count($ids); $i++) {
            $id = $ids[$i];
            $this->sfStyle->title('Command : '.$commandName.' --userid='.$id);
            $this->sfStyle->text('User '.($i + 1).'/'.count($ids));


            $command = $this->getApplication()->find($commandName);
            $arguments = [
                'command' => $command,
                '--userid' => $id,
                '--color' => true,
                '--style' => true,
                '--size' => true,
            ];
            $inputUserScore = new ArrayInput($arguments);
            $command->run($inputUserScore, $output);
        }
    }
}
