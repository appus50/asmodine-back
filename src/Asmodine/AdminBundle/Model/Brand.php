<?php

namespace Asmodine\AdminBundle\Model;

use Asmodine\CommonBundle\DTO\BrandDTO;
use Asmodine\CommonBundle\Util\Str;

/**
 * Class Brand.
 */
class Brand
{
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
    private $slug;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $logo;

    /**
     * @var bool
     */
    private $iframe;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * Brand constructor.
     *
     * @param int|null $id
     * @param string   $name
     * @param string   $slug
     * @param string   $description
     * @param string   $logo
     * @param bool     $iframe
     * @param bool     $enabled
     */
    private function __construct(?int $id, string $name, string $slug, string $description, string $logo, bool $iframe, bool $enabled)
    {
        $this->id = $id;
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->logo = $logo;
        $this->iframe = $iframe;
        $this->enabled = $enabled;
    }

    /**
     * Get SQL Id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Brand constructor.
     *
     * @param string $name
     * @param string $description
     * @param string $logo
     * @param bool   $iframe
     * @param bool   $enabled
     *
     * @return Brand
     */
    public static function create(string $name, string $description, string $logo, bool $iframe = true, bool $enabled = true): self
    {
        $slug = Str::slugify($name);
        $instance = new self(null, $name, $slug, $description, $logo, $iframe, $enabled);

        return $instance;
    }

    /**
     * Brand constructor.
     *
     * @param BrandDTO $brandDTO
     *
     * @return Brand
     */
    public static function loadDTO(BrandDTO $brandDTO): self
    {
        $instance = new self($brandDTO->id, $brandDTO->name, $brandDTO->slug, $brandDTO->description, $brandDTO->logo, $brandDTO->iframe, $brandDTO->enabled);

        return $instance;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return $this->logo;
    }

    /**
     * Enable Brand.
     *
     * @return Brand
     */
    public function enable(): self
    {
        $this->enabled = true;

        return $this;
    }

    /**
     * Disable Brand.
     *
     * @return Brand
     */
    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }

    /**
     * Return true if brand accepts iframe.
     *
     * @return bool
     */
    public function isIframe(): bool
    {
        return $this->iframe;
    }

    /**
     * Return true if brand is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
