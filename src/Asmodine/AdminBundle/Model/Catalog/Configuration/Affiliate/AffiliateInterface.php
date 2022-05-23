<?php

namespace Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate;

/**
 * Interface AffiliateInterface.
 */
interface AffiliateInterface
{
    public function buildUrl(): ?string;

    public function getArchiveFormat(): string;

    public function getFileExtension(): string;

    public function getCSVConfig(): array;
}
