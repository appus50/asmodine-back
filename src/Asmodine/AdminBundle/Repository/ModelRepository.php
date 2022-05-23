<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;
use Asmodine\CommonBundle\Repository\ElasticsearchPushInterface;
use Doctrine\DBAL\Statement;

/**
 * Class ModelRepository.
 */
class ModelRepository extends AbstractAsmodineRepository implements ElasticsearchPushInterface
{
    /**
     * Return Select of datas to bulk.
     *
     * @param array $params
     * @param int   $limit
     * @param int   $offset
     *
     * @return Statement
     */
    public function getBulkDatas(array $params, int $offset, int $limit): Statement
    {
        return $this->execute("
          SELECT
              m.id AS id,
              m.slug AS slug,
              m.model_id AS model_id,
              
              m.brand_id AS brand_id,
              b.name AS brand_name,
              
              cat0.id AS category_depth0_id,
              cat1.id AS category_depth1_id,
              cat2.id AS category_depth2_id,
              cat0.name AS category_depth0,
              cat1.name AS category_depth1,
              cat2.name AS category_depth2,
              
              m.name AS name,
              m.reference AS reference,
              m.description_short AS description_short,
              m.description AS description,
              m.composition AS composition,
            
              COALESCE(m.unit_price, p.unit_price) AS unit_price,
              m.currency AS currency,
            
              COALESCE(m.discount, p.discount) AS discount,
              COALESCE(m.discount_type, p.discount_type) AS discount_type,
              COALESCE(m.discount_value, p.discount_value) AS discount_value,
              COALESCE(m.discount_old_price, p.discount_old_price) AS discount_old_price,
              COALESCE(im.local_link, im.initial_link, ip.local_link, ip.initial_link) as image,
              
              m.is_enabled_auto AS enabled
              
          FROM `back_model` AS m
          INNER JOIN `back_product` AS p ON p.model_id = m.id
          INNER JOIN `back_brand` AS b ON m.brand_id = b.id
          INNER JOIN `back_category_brand` AS catb ON m.category_id = catb.id
          INNER JOIN `back_category_asmodine` AS cat2 ON catb.category_asmodine_id = cat2.id
          INNER JOIN `back_category_asmodine` AS cat1 ON cat2.parent_id = cat1.id
          INNER JOIN `back_category_asmodine` AS cat0 ON cat1.parent_id = cat0.id
          LEFT JOIN `back_image` AS im ON im.type = 'model' AND im.external_id = m.id AND im.enabled = TRUE
          LEFT JOIN `back_image` AS ip ON ip.type = 'product' AND ip.external_id = p.id AND ip.enabled = TRUE
        
          WHERE m.brand_id = :brand_id
          GROUP BY m.id
          
          LIMIT $limit OFFSET $offset", $params);
    }

    /**
     * Disable model with date of end or no stock.
     *
     * @return Statement
     */
    public function disableModelsAuto(): Statement
    {
        return $this->execute('UPDATE `back_model` SET is_enabled_auto = FALSE WHERE 
           is_enabled_auto = TRUE AND ((active_to IS NOT NULL AND active_to > NOW()) OR stock_in = FALSE)');
    }

    /**
     * Create new slug.
     */
    public function createSlug(): void
    {
        $replaces = ['.' => '', "\'" => '-', "\/" => '-', 'š' => 's', 'Ð' => 'Dj', 'ž' => 'z', 'Þ' => 'B', 'ß' => 'Ss',
                     'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
                     'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
                     'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
                     'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', 'ƒ' => 'f',
                     'œ' => 'oe', '€' => 'euro', '$' => 'dollars', '@' => 'at', '£' => '', '&' => '',
                     ' ' => '-', '--' => '-', ];
        $replaceString = 'LOWER(m.name)';
        foreach ($replaces as $init => $end) {
            $replaceString = 'REPLACE('.$replaceString.", '".$init."', '".$end."')";
        }
        $this->execute("UPDATE `back_model` AS m JOIN `back_brand` AS b ON m.brand_id = b.id SET m.slug = CONCAT(b.slug, '-', TRIM(".$replaceString."), '-', m.id)  WHERE m.slug IS NULL");
    }
}
