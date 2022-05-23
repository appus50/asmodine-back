<?php

namespace Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate;

use JMS\Serializer\Annotation as Serializer;

/**
 * Class AbstractAffiliate.
 *
 * @Serializer\Discriminator(field = "type", map = {
 *     "awin":          "Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate\Awin",
 *     "effiliation":   "Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate\Effiliation",
 *     "tradedoubler":   "Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate\Tradedoubler",
 *     "netaffiliation":   "Asmodine\AdminBundle\Model\Catalog\Configuration\Affiliate\NetAffiliation"
 * })
 */
abstract class AbstractAffiliate implements AffiliateInterface
{
}
