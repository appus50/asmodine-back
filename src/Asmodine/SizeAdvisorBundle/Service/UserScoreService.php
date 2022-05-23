<?php

namespace Asmodine\SizeAdvisorBundle\Service;

use Asmodine\CommonBundle\DTO\PhysicalProfileDTO;
use Asmodine\CommonBundle\Model\Profile\Body;
use Asmodine\CommonBundle\Util\Str;
use Asmodine\SizeAdvisorBundle\Repository\UserProductScoreRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Class UserService.
 */
class UserScoreService
{
    /**
     * @var UserProductScoreRepository
     */
    private $productRepository;

    /**
     * @var string
     */
    private $projectDir;

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
     * UserScoreService constructor.
     *
     * @param UserProductScoreRepository $userScoreProductRepository
     * @param string                     $rootDir
     * @param LoggerInterface            $logger
     */
    public function __construct(UserProductScoreRepository $userScoreProductRepository, string $rootDir, LoggerInterface $logger)
    {
        $this->productRepository = $userScoreProductRepository;
        $this->projectDir = $rootDir;
        $this->logger = $logger;
    }

    /**
     * Create or Update User Scores.
     *
     * @param bool     $color
     * @param bool     $style
     * @param bool     $sizeGuide
     * @param int|null $userId
     *
     * @return array
     */
    public function run(bool $color, bool $style, bool $sizeGuide, ?int $userId): array
    {
        $actions = [];
        $this->time = microtime(true);
        if ($color || $style || $sizeGuide) {
            $this->productRepository->createNewAssociations($userId);
            $actions[] = ['name' => 'Add new Product', 'duration' => $this->getDuration()];
        }
        if ($color) {
            $this->productRepository->calculateColorNote($userId);
            $actions[] = ['name' => 'Color', 'duration' => $this->getDuration()];
        }
        if ($style) {
            $this->productRepository->calculateStyleNote($userId);
            $actions[] = ['name' => 'Style', 'duration' => $this->getDuration()];
        }
        if ($sizeGuide) {
            $this->productRepository->calculateSizeGuide($userId);
            $actions[] = ['name' => 'Size Guide', 'duration' => $this->getDuration()];
        }

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

    /**
     * Check different Value and run.
     *
     * @param PhysicalProfileDTO|null $old
     * @param PhysicalProfileDTO      $new
     */
    public function runIfDifferent(?PhysicalProfileDTO $old, PhysicalProfileDTO $new)
    {
        $command = 'php '.$this->projectDir.'/../bin/console asmodine:sas:user --userid='.$new->userId;

        $color = true;
        $style = true;
        $size = true;

        if (!is_null($old) && $old->morphotype === $new->morphotype) {
            $color = false;
        }
        if (!is_null($old) && $old->size === $new->size && $old->morphoprofile === $new->morphoprofile && $old->morphoWeight === $new->morphoWeight) {
            $style = false;
        }

        if (!is_null($old)) {
            $slugs = Body::getSlugs();
            $size = false;
            foreach ($slugs as $slug) {
                $slug = Str::toCamelCase($slug);
                if ($old->$slug !== $new->$slug) {
                    $size = true;
                }
            }
        }
        if ($color) {
            $command .= ' --color';
        }
        if ($style) {
            $command .= ' --style';
        }
        if ($size) {
            $command .= ' --size';
        }
        if ($color || $style || $size) {
            $this->runProcess($command);
        }
    }

    /**
     * Run Asynchronous Process.
     *
     * @param string $command
     */
    private function runProcess(string $command): void
    {
        $process = new Process($command);
        $this->logger->debug('Run Process : '.$process->getCommandLine());
        $process->start();
    }
}
