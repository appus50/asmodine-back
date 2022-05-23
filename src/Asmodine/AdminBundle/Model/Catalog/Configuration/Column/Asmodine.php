<?php

namespace Asmodine\AdminBundle\Model\Catalog\Configuration\Column;

use Asmodine\CommonBundle\Annotation\IncludeSQL;
use Asmodine\CommonBundle\Exception\NullException;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Configuration.
 */
class Asmodine
{
    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $name;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $type;

    /**
     * @var bool
     *
     * @Serializer\Type("boolean")
     */
    private $null;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $more;

    const EXTERNAL_ID = 'external_id';
    const EAN = 'ean';
    const SKU = 'sku';
    const REFERENCE = 'reference';
    const MODEL_ID = 'model_id';

    const NAME = 'name';
    const BRAND = 'brand';
    const CATEGORY = 'category';
    const DESCRIPTION = 'description';
    const DESCRIPTION_SHORT = 'description_short';
    const URL = 'url';

    const IMAGE_1 = 'image_1';
    const IMAGE_2 = 'image_2';
    const IMAGE_3 = 'image_3';
    const IMAGE_4 = 'image_4';
    const IMAGE_5 = 'image_5';
    const IMAGE_6 = 'image_6';
    const IMAGE_7 = 'image_7';
    const IMAGE_8 = 'image_8';
    const IMAGE_9 = 'image_9';
    const IMAGE_10 = 'image_10';

    const CURRENCY = 'currency';
    const UNIT_PRICE = 'unit_price';

    const STOCK_IN = 'stock_in';
    const STOCK_AMOUNT = 'stock_amount';

    const DISCOUNT_TYPE = 'discount_type';
    const DISCOUNT_VALUE = 'discount_value';
    const DISCOUNT_FROM = 'discount_from';
    const DISCOUNT_TO = 'discount_to';
    const DISCOUNT_PRICE_WITHOUT = 'discount_old_price';

    const DELIVERY_COST = 'delivery_cost';
    const DELIVERY_INFORMATION = 'delivery_information';

    const ACTIVE_FROM = 'active_from';
    const ACTIVE_TO = 'active_to';

    const FURTHER_INFORMATION = 'further_information';

    // Product
    const COMPOSITION = 'composition';
    const SIZE = 'size';
    const COLOR = 'color';

    // TYPE
    const TYPE_VARCHAR = 'VARCHAR';
    const TYPE_TEXT = 'TEXT';
    const TYPE_DECIMAL = 'DECIMAL';
    const TYPE_BOOLEAN = 'BOOLEAN';
    const TYPE_SMALLINT = 'SMALLINT';
    const TYPE_DATETIME = 'DATETIME';
    const TYPE_DATE = 'DATE';
    const TYPE_ENUM = 'ENUM';

    /**
     * Asmodine constructor.
     *
     * @param string $name
     * @param string $type
     * @param bool   $null
     * @param string $more
     */
    public function __construct(string $name, string $type, bool $null, string $more = '')
    {
        $this->name = $name;
        $this->type = $type;
        $this->null = $null;
        $this->more = $more;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get column info.
     *
     * @param string $name
     *
     * @return Asmodine
     *
     * @throws NullException
     */
    public static function getColumn(string $name): self
    {
        $cols = self::getColumnsDetails();

        if (!isset($cols[$name])) {
            throw new NullException($name.' is not an Asmodine column');
        }

        $col = $cols[$name];

        return new self($name, $col['type'], $col['null'], $col['more']);
    }

    /**
     * Returns all configurable column names.
     *
     * @return array
     */
    public static function getColumnsKeys(): array
    {
        return array_keys(self::getColumnsDetails());
    }

    /**
     * Returns column details to cast.
     *
     * @return array
     */
    public static function getCastSQLColumns(): array
    {
        $castColFunc = function ($details) {
            return !in_array($details['type'], [self::TYPE_VARCHAR, self::TYPE_TEXT]);
        };

        return array_filter(self::getColumnsDetails(), $castColFunc);
    }

    /**
     * Returns preformatted SQL columns.
     *
     * @return array
     */
    public static function getSQLColumns(): array
    {
        $columns = self::getColumnsDetails();
        $formatColumns = [];
        foreach ($columns as $name => $details) {
            $formatColumns[] = "`$name` "
                .$details['type']
                .$details['more']
                .' NULL';
        }

        return $formatColumns;
    }

    /**
     * return only required column.
     *
     * @return array
     */
    public static function getRequiredColumns(): array
    {
        $notNullFunc = function ($details) {
            return !$details['null'];
        };

        return array_filter(self::getColumnsDetails(), $notNullFunc);
    }

    /**
     * All columns with details.
     *
     * @return array
     *
     * @IncludeSQL
     */
    private static function getColumnsDetails(): array
    {
        return [
            self::EXTERNAL_ID => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(255)'],
            self::EAN => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(16)'],
            self::SKU => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(32)'],
            self::REFERENCE => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(32)'],
            self::MODEL_ID => ['type' => self::TYPE_VARCHAR, 'null' => false, 'more' => '(128)'],
            self::NAME => ['type' => self::TYPE_VARCHAR, 'null' => false, 'more' => '(255)'],
            self::BRAND => ['type' => self::TYPE_VARCHAR, 'null' => false, 'more' => '(127)'],
            self::CATEGORY => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(255)'],
            self::DESCRIPTION => ['type' => self::TYPE_TEXT, 'null' => true, 'more' => ''],
            self::DESCRIPTION_SHORT => ['type' => self::TYPE_TEXT, 'null' => true, 'more' => ''],
            self::URL => ['type' => self::TYPE_VARCHAR, 'null' => false, 'more' => '(512)'],
            self::IMAGE_1 => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(512)'],
            self::IMAGE_2 => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(512)'],
            self::IMAGE_3 => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(512)'],
            self::IMAGE_4 => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(512)'],
            self::IMAGE_5 => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(512)'],
            self::IMAGE_6 => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(512)'],
            self::IMAGE_7 => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(512)'],
            self::IMAGE_8 => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(512)'],
            self::IMAGE_9 => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(512)'],
            self::IMAGE_10 => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(512)'],
            self::CURRENCY => ['type' => self::TYPE_VARCHAR, 'null' => false, 'more' => '(3)'],
            self::UNIT_PRICE => ['type' => self::TYPE_DECIMAL, 'null' => false, 'more' => '(10,2)'],
            self::STOCK_IN => ['type' => self::TYPE_BOOLEAN, 'null' => true, 'more' => ''],
            self::STOCK_AMOUNT => ['type' => self::TYPE_SMALLINT, 'null' => true, 'more' => ''],
            self::DISCOUNT_TYPE => [
                'type' => self::TYPE_ENUM,
                'null' => true,
                'more' => "('percent', 'amount')",
            ],
            self::DISCOUNT_VALUE => ['type' => self::TYPE_DECIMAL, 'null' => true, 'more' => '(10,2)'],
            self::DISCOUNT_FROM => ['type' => self::TYPE_DATETIME, 'null' => true, 'more' => ''],
            self::DISCOUNT_TO => ['type' => self::TYPE_DATETIME, 'null' => true, 'more' => ''],
            self::DISCOUNT_PRICE_WITHOUT => ['type' => self::TYPE_DECIMAL, 'null' => true, 'more' => '(10,2)'],
            self::DELIVERY_COST => ['type' => self::TYPE_DECIMAL, 'null' => true, 'more' => '(10,2)'],
            self::DELIVERY_INFORMATION => ['type' => self::TYPE_TEXT, 'null' => true, 'more' => ''],
            self::ACTIVE_FROM => ['type' => self::TYPE_DATE, 'null' => true, 'more' => ''],
            self::ACTIVE_TO => ['type' => self::TYPE_DATE, 'null' => true, 'more' => ''],
            self::FURTHER_INFORMATION => ['type' => self::TYPE_TEXT, 'null' => true, 'more' => ''],
            self::COMPOSITION => ['type' => self::TYPE_TEXT, 'null' => true, 'more' => ''],
            self::SIZE => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(255)'],
            self::COLOR => ['type' => self::TYPE_VARCHAR, 'null' => true, 'more' => '(255)'],
        ];
    }
}
