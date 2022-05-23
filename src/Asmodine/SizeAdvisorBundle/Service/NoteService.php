<?php

namespace Asmodine\SizeAdvisorBundle\Service;

use Asmodine\CommonBundle\Exception\InterfaceException;
use Asmodine\SizeAdvisorBundle\Repository\NoteRepositoryInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class NoteService.
 */
class NoteService
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * NoteService constructor.
     *
     * @param ContainerInterface $container
     * @param LoggerInterface    $logger
     */
    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * Rebuild notes tables.
     *
     * @param $type
     *
     * @return int
     *
     * @throws InterfaceException
     */
    public function run($type): int
    {
        /** @var NoteRepositoryInterface $repository */
        $repository = $this->container->get('asmodine.sizeadvisor.repository.note_'.$type);

        if (!($repository instanceof NoteRepositoryInterface)) {
            $exception = new InterfaceException(get_class($repository), 'NoteRepositoryInterface');
            $this->logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            throw $exception;
        }

        $repository->createTable();
        $repository->loadAll();

        $nb = $repository->countRows();
        $this->logger->info('Calculated Notes', ['type' => $type, 'total' => $nb]);

        return $nb;
    }
}
