<?php

namespace Asmodine\AdminBundle\Model;

use Asmodine\CommonBundle\Exception\EnumParameterException;
use Asmodine\CommonBundle\Model\Morphoprofile\Morphoprofile;
use Asmodine\CommonBundle\Model\Morphoprofile\Size;
use Asmodine\CommonBundle\Model\Morphoprofile\Weight;

/**
 * Class StyleMorphology.
 */
class StyleMorphology
{
    /**
     * Asmodine Style Name.
     *
     * @var string
     */
    private $styleName;

    /**
     * Slug Size.
     *
     * @var string
     */
    private $size;

    /**
     * Slug Morphoprofile.
     *
     * @var string
     */
    private $morphoprofile;

    /**
     * Slug Weight.
     *
     * @var string
     */
    private $weight;

    /**
     * Note between 1 and 3.
     *
     * @var int
     */
    private $note;

    /**
     * StyleMorphology constructor.
     *
     * @param string $styleName
     * @param string $size
     * @param string $morphoprofile
     * @param string $weight
     * @param int    $note
     *
     * @throws EnumParameterException
     */
    public function __construct(string $styleName, string $size, string $morphoprofile, string $weight, int $note)
    {
        $this->styleName = $styleName;
        if (!in_array($size, Size::getSlugs())) {
            throw new EnumParameterException($size, Size::getSlugs());
        }
        if (!in_array($morphoprofile, Morphoprofile::getSlugs())) {
            throw new EnumParameterException($morphoprofile, Morphoprofile::getSlugs());
        }
        if (!in_array($weight, Weight::getSlugs())) {
            throw new EnumParameterException($weight, Weight::getSlugs());
        }

        $this->size = $size;
        $this->morphoprofile = $morphoprofile;
        $this->weight = $weight;
        $this->note = $note;
    }

    /**
     * Get style name.
     *
     * @return string
     */
    public function getStyleName(): string
    {
        return $this->styleName;
    }

    /**
     * Get size slug.
     *
     * @return string
     */
    public function getSize(): string
    {
        return $this->size;
    }

    /**
     * Get morphoprofile slug.
     *
     * @return string
     */
    public function getMorphoprofile(): string
    {
        return $this->morphoprofile;
    }

    /**
     * Get weight slug.
     *
     * @return string
     */
    public function getWeight(): string
    {
        return $this->weight;
    }

    /**
     * Get note between 1 and 3.
     *
     * @return int
     */
    public function getNote(): int
    {
        return $this->note;
    }
}
