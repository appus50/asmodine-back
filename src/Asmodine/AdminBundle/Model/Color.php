<?php

namespace Asmodine\AdminBundle\Model;

/**
 * Class Color.
 */
class Color
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $hexa;

    /**
     * @var string
     */
    private $slugFilter;

    /**
     * Color constructor.
     *
     * @param string $name
     * @param string $hexa
     * @param string $slugFilter
     */
    public function __construct(string $name, string $hexa, ?string $slugFilter = null)
    {
        $this->name = $name;
        $this->hexa = str_replace('#', '', $hexa);
        $this->slugFilter = $slugFilter;
    }

    /**
     * Get color name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get color (hexa) witjout #.
     *
     * @return string
     */
    public function getHexa(): string
    {
        return $this->hexa;
    }

    /**
     * Get slug of filter color.
     *
     * @return string
     */
    public function getFilterSlug(): ?string
    {
        return $this->slugFilter;
    }
}
