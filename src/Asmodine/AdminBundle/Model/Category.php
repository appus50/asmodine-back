<?php

namespace Asmodine\AdminBundle\Model;

use Asmodine\CommonBundle\Exception\EnumParameterException;
use Asmodine\CommonBundle\Model\Morphoprofile\Gender;
use Asmodine\CommonBundle\Util\Str;

/**
 * Class Category.
 */
class Category
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $slug;

    /**
     * @var int
     */
    private $position;

    /**
     * @var string
     */
    private $gender;

    /**
     * @var string
     */
    private $icon;

    /**
     * @var string
     */
    private $parentPath;

    /**
     * @var bool
     */
    private $enable;

    /**
     * Category constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->slug = Str::slugify($name);
        $this->position = 0;
        $this->icon = '';
        $this->parentPath = '/';
        $this->enable = false;
    }

    /**
     * @param int $position
     *
     * @return Category
     */
    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Set icon path.
     *
     * @param string $icon
     *
     * @return Category
     */
    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set path of parent category.
     *
     * @param string $parentPath
     *
     * @return Category
     */
    public function setParentPath(string $parentPath): self
    {
        $this->parentPath = $parentPath;

        return $this;
    }

    /**
     * Enable the category.
     *
     * @return Category
     */
    public function enable(): self
    {
        $this->enable = true;

        return $this;
    }

    /**
     * Disable the category.
     *
     * @return Category
     */
    public function disable(): self
    {
        $this->enable = false;

        return $this;
    }

    /**
     * Return true if the category is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enable;
    }

    /**
     * Get category name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get category slug.
     *
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Get category position.
     *
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Get icon path.
     *
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * Return Parent Path.
     *
     * @return string
     */
    public function getParentPath(): string
    {
        return $this->parentPath;
    }

    /**
     * Get gender slug.
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set gender slug.
     *
     * @param string $gender
     *
     * @throws EnumParameterException
     */
    public function setGender(string $gender)
    {
        $gender = strtolower($gender);
        if (!in_array($gender, Gender::getSlugs())) {
            throw new EnumParameterException($gender, Gender::getSlugs());
        }
        $this->gender = $gender;
    }
}
