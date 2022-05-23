<?php

namespace Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate;

use Asmodine\AdminBundle\Model\Catalog;
use Asmodine\AdminBundle\Model\Catalog\Configuration;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class NetAffiliation.
 */
class NetAffiliation extends AbstractAffiliate
{
    /**
     * @var int
     *
     * @Serializer\Type("string")
     */
    private $maff;

    /**
     * @param string $maff
     *
     * @return $this
     */
    public function setMaff(string $maff)
    {
        $this->maff = $maff;

        return $this;
    }

    /**
     * @return string
     */
    public function buildUrl(): ?string
    {
        return 'http://flux.netaffiliation.com/feed.php?maff='.$this->maff;
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
        return Catalog::ARCHIVE_FORMAT_NONE;
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
