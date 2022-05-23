<?php

namespace Asmodine\AdminBundle\Service;

use Asmodine\AdminBundle\Repository\SynonymAssociationRepositoryInterface;
use Asmodine\CommonBundle\Exception\InterfaceException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityAssociationService.
 */
class AssociateService
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
     * AssociateService constructor.
     *
     * @param LoggerInterface    $logger
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * Search and associate.
     *
     * @param string $type
     *
     * @return array
     *
     * @throws InterfaceException
     */
    public function run(string $type): array
    {
        $this->logger->info('Search association on '.$type);
        /** @var SynonymAssociationRepositoryInterface $repository */
        $repository = $this->container->get('asmodine.admin.repository.'.$type);

        if (!($repository instanceof SynonymAssociationRepositoryInterface)) {
            $exception = new InterfaceException(get_class($repository), 'SynonymAssociationRepositoryInterface');
            $this->logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
            throw $exception;
        }

        $nb = $repository->getNbRowsWithoutAssociation();
        $all = $repository->getNbRows();
        if (0 == $nb) {
            $this->logger->info('    Everything is associated', ['type' => $type]);

            return ['begin' => 0, 'end' => 0, 'all' => $all];
        }

        $repository->runSynonymAssociation();
        $end = $repository->getNbRowsWithoutAssociation();
        $this->logger->info('    Association results', ['type' => $type, 'before' => $nb, 'after' => $end]);

        return ['begin' => $nb, 'end' => $end, 'all' => $all];
    }
}
