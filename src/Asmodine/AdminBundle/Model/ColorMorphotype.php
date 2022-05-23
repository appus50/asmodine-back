<?php

namespace Asmodine\AdminBundle\Model;

use Asmodine\CommonBundle\Exception\EnumParameterException;
use Asmodine\CommonBundle\Model\Morphotype\Morphotype;

/**
 * Class ColorMorphotype.
 */
class ColorMorphotype
{
    /**
     * Asmodine Color Name.
     *
     * @var string
     */
    private $colorName;

    /**
     * Slug Morphotype.
     *
     * @var string
     */
    private $morphotype;

    /**
     * Note between 1 and 3.
     *
     * @var int
     */
    private $note;

    /**
     * ColorMorphotype constructor.
     *
     * @param string $colorName
     * @param string $morphotype
     * @param int    $note
     *
     * @throws EnumParameterException
     */
    public function __construct(string $colorName, string $morphotype, int $note)
    {
        $this->colorName = $colorName;
        if (!in_array($morphotype, Morphotype::getSlugs())) {
            throw new EnumParameterException($morphotype, Morphotype::getSlugs());
        }
        $this->morphotype = $morphotype;
        $this->note = $note;
    }

    /**
     * Get Asmodine color name.
     *
     * @return string
     */
    public function getColorName(): string
    {
        return $this->colorName;
    }

    /**
     * Get Morphotype Slug.
     *
     * @return string
     */
    public function getMorphotype(): string
    {
        return $this->morphotype;
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
