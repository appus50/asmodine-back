<?php

namespace Asmodine\SizeAdvisorBundle\Service;

use Asmodine\SizeAdvisorBundle\Repository\SizeGuideRepository;
use Psr\Log\LoggerInterface;

/**
 * Class SizeGuideService.
 */
class SizeGuideService
{
    /**
     * @var SizeGuideRepository
     */
    private $repository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Microtime.
     *
     * @var int
     */
    private $time;

    /**
     * SizeGuideService constructor.
     *
     * @param SizeGuideRepository $sizeGuideRepository
     * @param LoggerInterface     $logger
     */
    public function __construct(SizeGuideRepository $sizeGuideRepository, LoggerInterface $logger)
    {
        $this->repository = $sizeGuideRepository;
        $this->logger = $logger;
    }

    /**
     * Generates all Size Guide.
     *
     * @return array
     *
     * @throws \Asmodine\CommonBundle\Exception\NotFoundTableException
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function run(): array
    {
        $actions = [];
        $this->time = microtime(true);

        $actions[] = [
            'name' => 'Tree',
            'description' => 'Generate tree of products',
            'nb' => $this->repository->generateTree(),
            'duration' => $this->getDuration(),
            'temporary' => true,
        ];
        $actions[] = [
            'name' => 'Measures to be used',
            'description' => 'Which part of the body for which product',
            'nb' => $this->repository->generateSizeGuideBodyPart(),
            'duration' => $this->getDuration(),
            'temporary' => true,
        ];
        $actions[] = [
            'name' => 'Full size guide',
            'description' => 'Measurement of any part of the body',
            'nb' => $this->repository->generateFullSizeGuide(),
            'duration' => $this->getDuration(),
            'temporary' => true,
        ];
        $actions[] = [
            'name' => 'Size Guide',
            'description' => 'Generate final size guide',
            'nb' => $this->repository->generateFinalSizeGuide(),
            'duration' => $this->getDuration(),
            'temporary' => false,
        ];
        $actions[] = [
            'name' => 'Number of measurement points by product',
            'description' => 'Determines the number of measurement points required for 100% compatibility',
            'nb' => $this->repository->generateNumberOfPoints(),
            'duration' => $this->getDuration(),
            'temporary' => false,
        ];
        $this->repository->clean();
        $this->logger->info('Size Guide Generation', $actions);

        return $actions;
    }

    /**
     * Get time difference between two calls.
     *
     * @return float
     */
    private function getDuration(): float
    {
        $d = microtime(true) - $this->time;
        $this->time = microtime(true);

        return floatval($d);
    }
}
