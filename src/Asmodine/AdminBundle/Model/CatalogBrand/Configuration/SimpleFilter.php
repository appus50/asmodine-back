<?php

namespace Asmodine\AdminBundle\Model\CatalogBrand\Configuration;

use Asmodine\CommonBundle\Annotation\IncludeSQL;
use Asmodine\CommonBundle\Exception\EnumParameterException;
use Asmodine\CommonBundle\Exception\NullException;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class SimpleFilter.
 */
class SimpleFilter
{
    const POSITION_START = 'start';
    const POSITION_ANYWHERE = 'middle';
    const POSITION_END = 'end';
    const POSITION_EQUAL = 'equal';

    const GLUE_AND = 'and';
    const GLUE_OR = 'or';

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $columnName;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $position;

    /**
     * @var string
     *
     * @Serializer\Type("array<string>")
     */
    private $contents;

    /**
     * @var bool
     *
     * @Serializer\Type("boolean")
     */
    private $keep;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $glue;

    /**
     * SimpleFilter constructor.
     *
     * @param string $columnName
     * @param string $position
     * @param array  $contents
     * @param bool   $keep
     * @param string $glue
     *
     * @throws EnumParameterException
     * @throws NullException
     */
    public function __construct(string $columnName, string $position, array $contents, bool $keep, ?string $glue)
    {
        $this->columnName = trim(str_replace(' ', '', $columnName));
        if (!in_array(
            $position,
            [self::POSITION_START, self::POSITION_ANYWHERE, self::POSITION_END, self::POSITION_EQUAL]
        )) {
            throw new EnumParameterException(
                $position,
                [self::POSITION_START, self::POSITION_ANYWHERE, self::POSITION_END, self::POSITION_EQUAL]
            );
        }
        $this->position = $position;
        $this->keep = $keep;
        if (0 == count($contents)) {
            throw new NullException('Simple Filter Contents is null');
        }
        $this->contents = $contents;
        if (!in_array($glue, [self::GLUE_AND, self::GLUE_OR]) && count($contents) > 1) {
            throw new EnumParameterException($glue, [self::GLUE_AND, self::GLUE_OR]);
        }
        $this->glue = $glue;
    }

    /**
     * Construct SQL Filter.
     *
     * @return string
     * @IncludeSQL
     */
    public function getSQLFilter(): string
    {
        // TODO Check if correct
        $sql = 'DELETE FROM `%s` WHERE `'.$this->columnName.'` ';
        $likeOrEqual = '';
        if ($this->keep) {
            $likeOrEqual = self::POSITION_EQUAL == $this->position ? '!= ' : 'NOT LIKE ';
        }
        if (!$this->keep) {
            $likeOrEqual = self::POSITION_EQUAL == $this->position ? '= ' : 'LIKE ';
        }
        $sql .= $likeOrEqual;
        $contents = $this->contents;

        $glue = self::GLUE_OR == $this->glue ? ' AND ' : ' OR ';
        if (!$this->keep) {
            $glue = self::GLUE_OR == $this->glue ? ' OR ' : ' AND ';
        }

        $likeFunc = function ($content) {
            if (in_array($this->position, [self::POSITION_EQUAL])) {
                if ($content == floatval($content) || $content == intval($content)) {
                    return $content;
                }

                return "'".addslashes($content)."'";
            }
            // !!! sprintf % => %%
            $tmp = in_array($this->position, [self::POSITION_END, self::POSITION_ANYWHERE]) ? "'%%" : "'";
            $tmp .= addslashes($content);
            $tmp .= in_array($this->position, [self::POSITION_ANYWHERE, self::POSITION_START]) ? "%%'" : "'";

            return $tmp;
        };
        $contents = array_map($likeFunc, $contents);
        $sql .= ' '.implode($glue.'`'.$this->columnName.'` '.$likeOrEqual, $contents);

        return $sql;
    }

    /**
     * @return string
     */
    public function getColumnName(): string
    {
        return $this->columnName;
    }

    /**
     * @return string
     */
    public function getPosition(): string
    {
        return $this->position;
    }

    /**
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @return bool
     */
    public function isKeep(): bool
    {
        return $this->keep;
    }

    /**
     * @return string
     */
    public function getGlue()
    {
        return $this->glue;
    }


}
