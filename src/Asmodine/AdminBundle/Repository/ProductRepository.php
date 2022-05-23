<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;
use Asmodine\CommonBundle\Repository\ElasticsearchPushInterface;
use Doctrine\DBAL\Statement;

/**
 * Class ProductRepository.
 */
class ProductRepository extends AbstractAsmodineRepository implements ElasticsearchPushInterface
{
    /**
     * Return Select of datas to bulk.
     *
     * @param array $params
     * @param int   $limit
     * @param int   $offset
     *
     * @return Statement
     *
     * @deprecated
     */
    public function getBulkDatas(array $params, int $offset, int $limit): Statement
    {
        return $this->execute("
          SELECT
              p.id AS id,
              m.model_id AS model_brand_id,
              p.model_id AS model_id,
              cat2.id AS category_depth2_id,
              cat1.id AS category_depth1_id,
              cat0.id AS category_depth0_id,
              p.brand_id AS brand_id,
              b.name AS brand_name,
              COALESCE(p.reference, m.reference) AS reference,
              COALESCE(p.name, m.name) AS name,
              COALESCE(p.description, m.description) AS description,
              COALESCE(p.description_short, m.description_short) AS description_short,
              COALESCE(p.url, m.url) AS url,
              COALESCE(p.unit_price, m.unit_price) AS unit_price,
              m.currency AS currency,
              COALESCE(p.composition, m.composition) AS composition,
              COALESCE(p.stock_in, m.stock_in) AS stock_in,
              COALESCE(p.enabled, m.enabled) AS enabled,
              COALESCE(p.discount, m.discount) AS discount,
              COALESCE(p.discount_type, m.discount_type) AS discount_type,
              COALESCE(p.discount_value, m.discount_value) AS discount_value,
              COALESCE(p.discount_old_price, m.discount_old_price) AS discount_old_price,
              COALESCE(p.further_information, m.further_information) AS further_information,
              cb.name AS color_name,
              COALESCE(cb.hexa, ca.hexa) AS color_hexa,
              sb.name AS size
          FROM `back_product` AS p
          INNER JOIN `back_model` AS m ON p.model_id = m.id
          INNER JOIN `back_brand` AS b ON p.brand_id = b.id
          INNER JOIN `back_color_brand` AS cb ON p.color_id = cb.id
          INNER JOIN `back_color_asmodine` AS ca ON cb.color_asmodine_id = ca.id
          INNER JOIN `back_size_brand` AS sb ON p.size_id = sb.id
          INNER JOIN `back_category_brand` AS catb ON m.category_id = catb.id
          INNER JOIN `back_category_asmodine` AS cat2 ON catb.category_asmodine_id = cat2.id
          INNER JOIN `back_category_asmodine` AS cat1 ON cat2.parent_id = cat1.id
          INNER JOIN `back_category_asmodine` AS cat0 ON cat1.parent_id = cat0.id
          LIMIT $limit OFFSET $offset");
    }

    /**
     * Disable product with date of end or no stock.
     *
     * @return Statement
     */
    public function disableProductsAuto(): Statement
    {
        return $this->execute('UPDATE `back_product` SET is_enabled_auto = FALSE WHERE 
           is_enabled_auto = TRUE AND ((active_to IS NOT NULL AND active_to > NOW()) OR stock_in = FALSE)');
    }

    /**
     * @param $modelId
     *
     * @return Statement
     */
    public function getSubBulkDatas($modelId): Statement
    {
        return $this->execute('
          SELECT
              p.id AS id,
              
              sb.name AS size,
              cb.name AS color,
              cfa.slug AS color_filter,
              COALESCE(p.url, m.url) AS url,
              p.is_enabled_auto AS enabled
              
          FROM `back_product` AS p
          INNER JOIN `back_model` AS m ON p.model_id = m.id
          INNER JOIN `back_color_brand` AS cb ON p.color_id = cb.id
          INNER JOIN `back_color_asmodine` AS ca ON cb.color_asmodine_id = ca.id
          INNER JOIN `back_color_filter_asmodine` AS cfa ON ca.color_filter_id = cfa.id
          INNER JOIN `back_size_brand` AS sb ON p.size_id = sb.id
          WHERE p.model_id = :model_id', ['model_id' => $modelId]);
    }
}
