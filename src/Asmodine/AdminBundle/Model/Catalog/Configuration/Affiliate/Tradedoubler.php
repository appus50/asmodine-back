<?php

namespace Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate;

use Asmodine\AdminBundle\Model\Catalog;
use Asmodine\AdminBundle\Model\Catalog\Configuration;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Tradedoubler.
 */
class Tradedoubler extends AbstractAffiliate
{
    /**
     * @var int
     *
     * @Serializer\Type("int")
     */
    private $myFeed;

    /**
     * @var int
     *
     * @Serializer\Type("int")
     */
    private $myFormat;

    /**
     * @param int $myFeed
     *
     * @return $this
     */
    public function setMyFeed(int $myFeed)
    {
        $this->myFeed = $myFeed;

        return $this;
    }

    /**
     * @param int $myFormat
     *
     * @return $this
     */
    public function setMyFormat(int $myFormat)
    {
        $this->myFormat = $myFormat;

        return $this;
    }

    /**
     * @return string
     */
    public function buildUrl(): ?string
    {
        return 'http://pf.tradedoubler.com/export/export?myFeed='.$this->myFeed.'&myFormat='.$this->myFormat;
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
        return Catalog::ARCHIVE_FORMAT_ZIP;
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
