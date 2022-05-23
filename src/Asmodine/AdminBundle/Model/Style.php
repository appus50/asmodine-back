<?php

namespace Asmodine\AdminBundle\Model;

/**
 * Class Style.
 */
class Style
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * Style constructor.
     *
     * @param string $name
     * @param bool   $enabled
     */
    public function __construct(string $name, bool $enabled = true)
    {
        $this->name = $name;
        $this->enabled = $enabled;
    }

    /**
     * Get style name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get if style is enabled.
     *
     * @return bool
     */
    public function enabled(): bool
    {
        return $this->enabled;
    }
}
