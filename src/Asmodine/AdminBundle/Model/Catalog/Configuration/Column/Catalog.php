<?php

namespace Asmodine\AdminBundle\Model\Catalog\Configuration\Column;

use Asmodine\AdminBundle\Model\Catalog\Configuration\Column\Catalog\Content;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Configuration.
 */
class Catalog
{
    /**
     * @var array
     *
     * @Serializer\Type("array<Asmodine\AdminBundle\Model\Catalog\Configuration\Column\Catalog\Content>")
     */
    private $contents;

    /**
     * Catalog constructor.
     *
     * @param array $contents
     */
    public function __construct(array $contents = [])
    {
        $this->contents = $contents;
    }

    /**
     * Create Complex Column.
     *
     * @see Content
     *
     * @param mixed $contentDatas
     *
     * @return Catalog
     */
    public static function createWithContentDatas($contentDatas): self
    {
        $instance = new self();
        $addContent = function ($mixed) use ($instance) {
            $instance->addContent(Content::create($mixed));
        };

        if (!is_array($contentDatas)) {
            $contentDatas = [$contentDatas];
        }
        array_map($addContent, $contentDatas);

        return $instance;
    }

    /**
     * Add content element.
     *
     * @param Content $content
     *
     * @return Catalog
     */
    public function addContent(Content $content): self
    {
        $this->contents[] = $content;

        return $this;
    }

    /**
     * Return Content element.
     *
     * @param int $position
     *
     * @return Content
     */
    public function getContent(int $position = 0): Content
    {
        if (!isset($this->contents[$position])) {
            throw new \OutOfRangeException($position.' is out of [0-'.count($this->contents).']');
        }

        return $this->contents[$position];
    }

    /**
     * Return Number of elements.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->contents);
    }
}
