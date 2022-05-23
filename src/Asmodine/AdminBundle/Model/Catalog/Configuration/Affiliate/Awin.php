<?php

namespace Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate;

use Asmodine\AdminBundle\Model\Catalog;
use Asmodine\AdminBundle\Model\Catalog\Configuration;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Awin.
 */
class Awin extends AbstractAffiliate
{
    /**
     * @var array
     *
     * @Serializer\Type("array")
     */
    private $brands;

    /**
     * @var array
     *
     * @Serializer\Type("array<int>")
     */
    private $categories;

    /**
     * @var array
     *
     * @Serializer\Type("array<int>")
     */
    private $advertisers;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $language;

    /**
     * @var string
     *
     * @Serializer\Type("string")
     */
    private $apiKey;

    /**
     * @var array
     *
     * @Serializer\Type("array<string>")
     */
    private $columns;

    /**
     * @param int $brandId
     *
     * @return Awin
     */
    public function addBrand(int $brandId): self
    {
        if (null == $this->brands) {
            $this->brands = [];
        }
        if (!in_array($brandId, $this->brands)) {
            $this->brands[] = $brandId;
        }

        return $this;
    }

    /**
     * @param int $categoryId
     *
     * @return Awin
     */
    public function addCategory(int $categoryId): self
    {
        if (null == $this->categories) {
            $this->categories = [];
        }
        if (!in_array($categoryId, $this->categories)) {
            $this->categories[] = $categoryId;
        }

        return $this;
    }

    public function addAvertiser(int $advertiserId): self
    {
        if (null == $this->advertisers) {
            $this->advertisers = [];
        }
        if (!in_array($advertiserId, $this->advertisers)) {
            $this->advertisers[] = $advertiserId;
        }

        return $this;
    }

    /**
     * @param string $language
     *
     * @return Awin
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @param string $apiKey
     *
     * @return Awin
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @param string $column
     *
     * @return Awin
     */
    public function addColumn(string $column): self
    {
        if (null == $this->columns) {
            $this->columns = [];
        }
        if (!in_array($column, $this->columns)) {
            $this->columns[] = $column;
        }

        return $this;
    }

    /**
     * @return null|string
     */
    public function buildUrl(): ?string
    {
        $url = 'http://datafeed.api.productserve.com/datafeed/download';
        $url .= '/apikey/'.$this->apiKey;
        $url .= '/language/'.$this->language;
        if (!is_null($this->advertisers) && count($this->advertisers) > 0) {
            $url .= '/fid/'.implode(',', $this->advertisers);
        }
        if (!is_null($this->brands) && count($this->brands) > 0) {
            $url .= '/bid/'.implode(',', $this->brands);
        }
        if (!is_null($this->columns) && count($this->columns) > 0) {
            $url .= '/columns/'.implode(',', $this->columns);
        }

        $url .= '/format/csv/delimiter/%3B/dtd/1.5/compression/gzip/adultcontent/1/';

        return $url;
    }

    /**
     * @param string $url
     *
     * @return Awin
     */
    public function initWithUrl(string $url): self
    {
        $datas = explode('/', $url);

        // Simple Element
        $setters = ['apikey' => 'setApiKey', 'language' => 'setLanguage'];
        $setFunc = function ($setter, $name) use ($datas) {
            if ($position = array_search($name, $datas)) {
                $this->$setter($datas[$position + 1]);
            }
        };
        array_walk($setters, $setFunc);

        // Array
        $setters = ['bid' => 'addBrand', 'fid' => 'addAvertiser', 'cid' => 'addCategory', 'columns' => 'addColumn'];
        $addFunc = function ($setter, $name) use ($datas) {
            if ($position = array_search($name, $datas)) {
                $elements = explode(',', $datas[$position + 1]);
                array_map(
                    function ($elem) use ($setter) {
                        $this->$setter($elem);
                    },
                    $elements
                );
            }
        };
        array_walk($setters, $addFunc);

        return $this;
    }

    /**
     * Return CSV Extension.
     *
     * @return string
     */
    public function getFileExtension(): string
    {
        return Catalog::EXT_CSV;
    }

    /**
     * Return GZIP Extension.
     *
     * @return string
     */
    public function getArchiveFormat(): string
    {
        return Catalog::ARCHIVE_FORMAT_GZ;
    }

    /**
     * Get CSV Delimiter, Enclosure and Escape.
     *
     * @return array
     */
    public function getCSVConfig(): array
    {
        return [
          Configuration::CSV_DELIMITER => ';',
          Configuration::CSV_ENCLOSURE => '"',
          Configuration::CSV_ESCAPE => '\\',
        ];
    }
}
