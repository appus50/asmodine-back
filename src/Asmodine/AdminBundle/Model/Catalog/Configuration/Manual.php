<?php

namespace Asmodine\AdminBundle\Model\Catalog\Configuration;

use JMS\Serializer\Annotation as Serializer;

/**
 * Class Manual.
 */
class Manual
{
    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $path;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $extension;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $archive;

    private function __construct(string $path, string $extension, string $archive)
    {
        $this->path = $path;
        $this->extension = $extension;
        $this->archive = $archive;
    }

    /**
     * @param string $path
     * @param array  $configuration
     *
     * @return Manual
     */
    public static function create(string $path, array $configuration): self
    {
        $instance = new self($path, $configuration['extension'], $configuration['archive']);

        return $instance;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getFileExtension(): string
    {
        return $this->extension;
    }

    /**
     * @return string
     */
    public function getArchiveFormat(): string
    {
        return $this->archive;
    }
}
