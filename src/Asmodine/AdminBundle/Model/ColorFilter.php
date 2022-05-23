<?php

namespace Asmodine\AdminBundle\Model;

/**
 * Class ColorFilter.
 */
class ColorFilter
{
    /**
     * @var string
     */
    private $slug;

    /**
     * @var string
     */
    private $name;

    /**
     * ColorFilter constructor.
     *
     * @param string $slug
     * @param string $name
     */
    public function __construct(string $slug, string $name)
    {
        $this->slug = $slug;
        $this->name = $name;
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
    public function getName(): string
    {
        return $this->name;
    }
}
