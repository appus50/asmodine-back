<?php

namespace Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate;

use Asmodine\AdminBundle\Model\Catalog;
use Asmodine\AdminBundle\Model\Catalog\Configuration;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Effiliation.
 */
class Effiliation extends AbstractAffiliate
{
    /**
     * @var int
     *
     * @Serializer\Type("int")
     */
    private $reference;

    /**
     * @param int $reference
     *
     * @return $this
     */
    public function setReference(int $reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * @return string
     */
    public function buildUrl(): ?string
    {
        return 'http://feeds.effiliation.com/myformat/'.$this->reference;
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
