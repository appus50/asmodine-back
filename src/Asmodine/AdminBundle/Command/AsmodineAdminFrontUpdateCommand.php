<?php

namespace Asmodine\AdminBundle\Command;

use Asmodine\AdminBundle\Service\FrontUpdateService;
use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AsmodineAdminFrontUpdateCommand.php
 * Updating the items administered here but used on the front.
 */
class AsmodineAdminFrontUpdateCommand extends AbstractAsmodineCommand
{
    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:admin:front:update')
            ->setDescription('Updating brand, categories on the front')
            ->addOption(
                'brands',
                'b',
                InputOption::VALUE_NONE,
                'Update Brands'
            )
            ->addOption(
                'categories',
                'c',
                InputOption::VALUE_NONE,
                'Update Categories'
            );
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
        $updates = ['brands', 'categories'];

        $this->sfStyle->title('Update Front');
        array_map($this->update(), $updates);
    }

    /**
     * Update one entity collection.
     *
     * @return \Closure
     */
    private function update(): \Closure
    {
        return function ($slug) {
            $option = $this->input->getOption($slug);
            if (!$option) {
                return;
            }
            /** @var FrontUpdateService $updateService */
            $updateService = $this->getContainer()->get('asmodine.admin.front.update');

            $this->sfStyle->section('Update '.ucfirst($slug));
            try {
                $response = call_user_func([$updateService, 'update'.ucfirst($slug)]);
                $datas = json_decode($response->getContent(), true);
                if (is_array($datas)) {
                    $this->sfStyle->listing($datas);
                }
                if (!is_array($datas)) {
                    $this->sfStyle->text($datas);
                }
            } catch (\Exception $exception) {
                $this->sfStyle->error(
                    'ApiException : '."\n"
                    .' - Code : '.$exception->getCode()."\n"
                    .' - Message : '.$exception->getMessage()."\n"
                    .' - Trace : '.$exception->getTraceAsString()
                );
            }
        };
    }
}
