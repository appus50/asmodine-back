<?php

namespace Asmodine\AdminBundle\Model;

use Asmodine\AdminBundle\DTO\CatalogDTO;
use Asmodine\AdminBundle\Model\Catalog\Configuration;
use Asmodine\CommonBundle\Exception\EnumParameterException;
use Asmodine\CommonBundle\Util\Str;

/**
 * Class Catalog.
 */
class Catalog
{
    const ORIGIN_AWIN = 'awin';
    const ORIGIN_EFFILIATION = 'effiliation';
    const ORIGIN_TRADEDOUBLER = 'tradedoubler';
    const ORIGIN_NETAFFILIATION = 'netaffiliation';
    const ORIGIN_MANUAL = 'manual';

    const EXT_CSV = '.csv';
    const EXT_XML = '.xml';

    const ARCHIVE_FORMAT_NONE = '';
    const ARCHIVE_FORMAT_GZ = '.gz';
    const ARCHIVE_FORMAT_ZIP = '.zip';

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $origin;

    /**
     * @var string
     */
    private $slug;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Catalog constructor.
     *
     * @param int|null      $id
     * @param string        $name
     * @param string        $origin
     * @param string        $slug
     * @param bool          $enabled
     * @param Configuration $configuration
     *
     * @throws EnumParameterException
     */
    private function __construct(?int $id, string $name, string $origin, string $slug, bool $enabled, Configuration $configuration)
    {
        $this->id = $id;
        $this->name = $name;
        if (!in_array($origin, $this->getOrigins())) {
            throw new EnumParameterException($origin, $this->getOrigins());
        }
        $this->origin = $origin;
        $this->slug = $slug;
        $this->enabled = $enabled;
        $this->configuration = $configuration;
    }

    /**
     * Catalog constructor.
     *
     * @param string $name
     * @param string $origin
     * @param bool   $enabled
     *
     * @return Catalog
     *
     * @throws EnumParameterException
     */
    public static function create(string $name, string $origin, bool $enabled): self
    {
        $slug = Str::slugify($name);
        $configuration = new Configuration($origin);
        $instance = new self(null, $name, $origin, $slug, $enabled, $configuration);

        return $instance;
    }

    /**
     * Catalog constructor.
     *
     * @param CatalogDTO    $catalogDTO
     * @param Configuration $configuration
     *
     * @return Catalog
     *
     * @throws EnumParameterException
     */
    public static function loadDTO(CatalogDTO $catalogDTO, Configuration $configuration): self
    {
        $instance = new self($catalogDTO->id, $catalogDTO->name, $catalogDTO->origin, $catalogDTO->slug, $catalogDTO->enabled, $configuration);

        return $instance;
    }

    /**
     * Get Catalog Name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get Catalog Origin.
     *
     * @return string
     */
    public function getOrigin(): string
    {
        return $this->origin;
    }

    /**
     * Get Catalog Slug.
     *
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Get Available Origins.
     *
     * @return array
     */
    public static function getOrigins(): array
    {
        return [self::ORIGIN_AWIN, self::ORIGIN_TRADEDOUBLER, self::ORIGIN_EFFILIATION, self::ORIGIN_NETAFFILIATION, self::ORIGIN_MANUAL];
    }

    /**
     * Get Catalog Configuration.
     *
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * Set Configuration.
     *
     * @param Configuration $configuration
     *
     * @return Catalog
     */
    public function setConfiguration(Configuration $configuration): self
    {
        $this->configuration = $configuration;

        return $this;
    }

    /**
     * Is Enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
