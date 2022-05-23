<?php

namespace Asmodine\AdminBundle\Model\CatalogBrand;

use Asmodine\AdminBundle\Model\CatalogBrand\Configuration\Action;
use Asmodine\AdminBundle\Model\CatalogBrand\Configuration\SimpleFilter;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Configuration.
 */
class Configuration
{
    const SEPARATOR_SIZE = 'size';
    const SEPARATOR_COLOR = 'color';

    /**
     * @var array
     *
     * @Serializer\Type("array<Asmodine\AdminBundle\Model\CatalogBrand\Configuration\SimpleFilter>")
     */
    private $simpleFilters;

    /**
     * @var array
     *
     * @Serializer\Type("array<Asmodine\AdminBundle\Model\CatalogBrand\Configuration\Action>")
     */
    private $actions;

    /**
     * @var array
     *
     * @Serializer\Type("array")
     */
    private $separatorSize;

    /**
     * @var array
     *
     * @Serializer\Type("array")
     */
    private $separatorColor;

    /**
     * Get All simple filters.
     *
     * @return array
     */
    public function getSimpleFilters(): array
    {
        if (is_null($this->simpleFilters)) {
            $this->simpleFilters = [];
        }

        return $this->simpleFilters;
    }

    /**
     * Set simple filter.
     *
     * @param string       $name
     * @param SimpleFilter $filter
     *
     * @return Configuration
     */
    public function setSimpleFilter(string $name, SimpleFilter $filter): self
    {
        $this->simpleFilters[$name] = $filter;

        return $this;
    }

    /**
     * Get All actions.
     *
     * @return array
     */
    public function getActions(): array
    {
        if (is_null($this->actions)) {
            $this->actions = [];
        }

        return $this->actions;
    }

    /**
     * Set action.
     *
     * @param string $name
     * @param Action $action
     *
     * @return Configuration
     */
    public function setAction(string $name, Action $action): self
    {
        $this->actions[$name] = $action;

        return $this;
    }

    /**
     * @return array
     */
    public function getSeparatorSize(): array
    {
        if (is_null($this->separatorSize)) {
            $this->separatorSize = [];
        }

        return $this->separatorSize;
    }

    /**
     * @param array $separatorSize
     *
     * @return Configuration
     */
    public function setSeparatorSize(array $separatorSize): self
    {
        $this->separatorSize = $separatorSize;

        return $this;
    }

    /**
     * @return array
     */
    public function getSeparatorColor(): array
    {
        if (is_null($this->separatorColor)) {
            $this->separatorColor = [];
        }

        return $this->separatorColor;
    }

    /**
     * @param array $separatorColor
     *
     * @return Configuration
     */
    public function setSeparatorColor(array $separatorColor): self
    {
        $this->separatorColor = $separatorColor;

        return $this;
    }
}
