<?php

namespace Asmodine\AdminBundle\Model\Catalog\Configuration\Column\Catalog;

use Asmodine\CommonBundle\Exception\EnumParameterException;
use Asmodine\CommonBundle\Annotation\IncludeSQL;
use Asmodine\CommonBundle\Exception\NullException;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Configuration.
 */
class Content
{
    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $type;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $value;

    const TYPE_COLUMN = 'column';
    const TYPE_STRING = 'string';
    const TYPE_INSIDE = 'inside';

    /**
     * Content constructor.
     *
     * @param string $type
     * @param string $value
     *
     * @throws EnumParameterException
     */
    private function __construct(string $type, string $value)
    {
        if (!in_array($type, [self::TYPE_COLUMN, self::TYPE_STRING, self::TYPE_INSIDE])) {
            throw new EnumParameterException($type, [self::TYPE_COLUMN, self::TYPE_STRING, self::TYPE_INSIDE]);
        }
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * Content constructor.
     *
     * @param mixed $datas
     *
     * @return Content
     *
     * @throws NullException
     */
    public static function create($datas): self
    {
        if (is_string($datas)) {
            return new self(self::TYPE_COLUMN, $datas);
        }

        if (!isset($datas['type'])) {
            throw new NullException('type is not defined in Catalog\Content');
        }

        if (isset($datas['value'])) {
            return new self($datas['type'], $datas['value']);
        }

        if (self::TYPE_INSIDE == $datas['type']) {
            $value = [
                'column' => $datas['column'],
                'start' => isset($datas['start']) ? $datas['start'] : null,
                'end' => isset($datas['end']) ? $datas['end'] : null,
            ];

            return new self($datas['type'], json_encode($value));
        }

        throw new NullException('Catalog\Content is not initialize');
    }

    /**
     * Get content type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getValue(){
        return $this->value;
    }

    /**
     * Returns a formatted SQL element of the content.
     *
     * @return string
     *
     * @IncludeSQL
     */
    public function getSQL()
    {
        if (self::TYPE_COLUMN === $this->getType()) {
            return '`'.$this->value.'`';
        }

        if (self::TYPE_STRING === $this->getType()) {
            return "'".$this->value."'";
        }

        if (self::TYPE_INSIDE === $this->getType()) {
            $datas = json_decode($this->value, true);
            $sql = "`{$datas['column']}`";
            if (!is_null($datas['start'])) {
                $sql = "SUBSTRING_INDEX($sql, '{$datas['start']}', -1)";
            }
            if (!is_null($datas['end'])) {
                $sql = "SUBSTRING_INDEX($sql, '{$datas['end']}', 1)";
            }

            return "IF(LOCATE('{$datas['start']}', `{$datas['column']}`)>0,$sql, NULL)";
        }
    }
}
