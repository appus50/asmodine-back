<?php

namespace Asmodine\AdminBundle\Model\CatalogBrand\Configuration;

use Asmodine\CommonBundle\Annotation\IncludeSQL;
use Asmodine\CommonBundle\Exception\EnumParameterException;
use Asmodine\CommonBundle\Exception\SerializerException;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Action
 * Spécific actions.
 */
class Action
{
    const ACTION_REPLACE = 'replace';

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $action;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $columnName;

    /**
     * @var string (json)
     *
     * @Serializer\Type("string")
     */
    private $contents;

    /**
     * Action constructor.
     *
     * @param string $action
     * @param string $columnName
     * @param        $contents
     *
     * @throws EnumParameterException
     * @throws SerializerException
     */
    public function __construct(string $action, string $columnName, $contents)
    {
        $actions = [self::ACTION_REPLACE];

        if (!in_array($action, $actions)) {
            throw new EnumParameterException($action, $actions);
        }
        $this->action = $action;
        $this->columnName = trim(str_replace(' ', '', $columnName));
        if (self::ACTION_REPLACE == $action && (!is_array($contents) || 0 == count($contents))) {
            throw new SerializerException('Action `REPLACE` must contain an hashmap (search string => replace string)');
        }
        $this->contents = json_encode($contents);
    }

    /**
     * Construct SQL action,.
     *
     * @return string
     * @IncludeSQL
     */
    public function getSQLAction(): string
    {
        if (self::ACTION_REPLACE == $this->action) {
            $contents = json_decode($this->contents);
            $replace = "`$this->columnName`";
            foreach ($contents as $init => $end) {
                $replace = ' REPLACE('.$replace.",'$init','$end')";
            }

            return "UPDATE `%s` SET `$this->columnName` = ".$replace;
        }

        return '';
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
    public function getContents()
    {
        return $this->contents;
    }
}
