<?php

namespace Asmodine\SizeAdvisorBundle\Repository;

use Asmodine\CommonBundle\Model\Morphoprofile\Gender;
use Asmodine\CommonBundle\Model\Profile\Body;
use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;
use Asmodine\CommonBundle\Repository\ElasticsearchPushInterface;
use Doctrine\DBAL\Statement;

/**
 * Class SizeGuideRepository.
 * TODO Move to Admin.
 */
class SizeGuideRepository extends AbstractAsmodineRepository implements ElasticsearchPushInterface
{
    private const TREE = 'advisor_product_tree';
    private const TMP_BODY = 'tmp_advisor_body_part';
    private const TMP_SIZE_GUIDE = 'tmp_advisor_full_size_guide';
    private const NUMBER_POINTS = 'advisor_size_guide_nb_points';
    private const SIZE_GUIDE = 'advisor_size_guide';

    /**
     * Generate tree of products.
     */
    public function generateTree(): int
    {
        $this->dropTable(self::TREE);
        $this->execute(
            'CREATE TABLE `'.self::TREE."` (
                `product_id`  INT UNSIGNED NOT NULL,
                `brand_size_id`  INT UNSIGNED NOT NULL,
                `model_id`  INT UNSIGNED NOT NULL,
                `category_depth2_id`  INT UNSIGNED NOT NULL,
                `category_depth1_id`  INT UNSIGNED NOT NULL,
                `category_depth0_id`  INT UNSIGNED NOT NULL,
                `brand_id`  INT UNSIGNED NOT NULL,
                `gender`  ENUM('".implode("','", Gender::getSlugs())."') NULL,
                PRIMARY KEY (`product_id`),
                INDEX `product_idx` (`product_id`),
                INDEX `brand_size_idx` (`brand_size_id`),
                INDEX `model_idx` (`model_id`),
                INDEX `category_depth2_idx` (`category_depth2_id`),
                INDEX `category_depth1_idx` (`category_depth1_id`),
                INDEX `category_depth0_idx` (`category_depth0_id`),
                INDEX `gender_idx` (`gender`),
                INDEX `brand_idx` (`brand_id`)
            )"
        );
        $this->execute(
            'INSERT IGNORE INTO `'.self::TREE.'` 
            SELECT p.id, p.size_id, m.id, c2.id, c1.id, c0.id, m.brand_id, c0.gender
            FROM `back_product` AS p
            INNER JOIN `back_model` AS m ON p.model_id = m.id
            INNER JOIN `back_category_brand` AS cb ON m.category_id = cb.id
            INNER JOIN `back_category_asmodine` AS c2 ON cb.category_asmodine_id = c2.id
            INNER JOIN `back_category_asmodine` AS c1 ON c2.parent_id = c1.id
            INNER JOIN `back_category_asmodine` AS c0 ON c1.parent_id = c0.id
                        
            WHERE c2.depth = 2 AND c0.enabled = TRUE AND c1.enabled = TRUE AND c2.enabled = TRUE AND m.is_enabled_auto = TRUE AND m.is_enabled_manual = TRUE AND p.is_enabled_auto = TRUE AND m.is_enabled_manual = TRUE'
        );

        return $this->count(self::TREE, true, ['product_id']);
    }

    /**
     * Which part of the body for which product.
     *
     * @return int
     *
     * @throws \Asmodine\CommonBundle\Exception\NotFoundTableException
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function generateSizeGuideBodyPart(): int
    {
        $bodySlugs = Body::getSlugs();
        $this->checkTable(self::TREE, true);

        $indexSlugs = array_map(
            function ($s) {
                return "INDEX `{$s}_idx` (`{$s}`)";
            },
            $bodySlugs
        );
        $coalesceSlugs = array_map(
            function ($s) {
                return "COALESCE(p0.{$s}, m1.{$s}, c2.{$s}, c3.{$s}, r4.{$s}, false)";
            },
            $bodySlugs
        );

        // Step 1 : 1 Line = 1 Product
        $this->dropTable(self::TMP_BODY.'_prepare');
        $this->execute(
            'CREATE TEMPORARY TABLE `'.self::TMP_BODY.'_prepare` (
                `product_id`  INT UNSIGNED NOT NULL,
                 `'.implode('` TINYINT(1) NOT NULL, `', $bodySlugs).'` TINYINT(1) NOT NULL,
                PRIMARY KEY (`product_id`),
                '.implode(', ', $indexSlugs).')'
        );
        $this->execute(
            'INSERT IGNORE INTO `'.self::TMP_BODY.'_prepare` (`product_id`, `'.implode('`, `', $bodySlugs).'`)
                  SELECT t.product_id, '.implode(', ', $coalesceSlugs).'
                  FROM `'.self::TREE."` AS t
                  LEFT JOIN `back_size_guide_body_part` AS p0 ON p0.brand_id = t.brand_id AND p0.type = 'product' AND t.product_id = p0.type_id 
                  LEFT JOIN `back_size_guide_body_part` AS m1 ON m1.brand_id = t.brand_id AND m1.type = 'model' AND t.model_id = m1.type_id 
                  LEFT JOIN `back_size_guide_body_part` AS c2 ON c2.brand_id = t.brand_id AND c2.type = 'category' AND t.category_depth2_id = c2.type_id
                  LEFT JOIN `back_size_guide_body_part` AS c3 ON c3.brand_id = t.brand_id AND c3.type = 'category' AND t.category_depth1_id = c3.type_id
                  LEFT JOIN `back_size_guide_body_part` AS r4 ON r4.brand_id = 0 AND r4.type = 'category' AND t.category_depth2_id = r4.type_id
                  "
        );

        // Step 2 : 1 Line = 1 product/bodyPart
        $this->dropTable(self::TMP_BODY);
        $this->execute(
            'CREATE TEMPORARY TABLE `'.self::TMP_BODY.'` (
                `product_id`  INT UNSIGNED NOT NULL,
                `body_part` VARCHAR(31) NOT NULL,
                PRIMARY KEY (`product_id`, `body_part`),
                INDEX product_idx (`product_id`))'
        );
        array_map(
            function ($slug) {
                $this->execute(
                    'INSERT INTO `'.self::TMP_BODY.'` (`product_id`, `body_part`) '
                    ."SELECT p.product_id, '{$slug}' FROM `".self::TMP_BODY."_prepare` AS p WHERE p.{$slug} = true"
                );
            },
            $bodySlugs
        );

        $this->dropTable(self::TMP_BODY.'_prepare');

        return $this->count(self::TMP_BODY, true, ['product_id', 'body_part']);
    }

    /**
     * Measurement of any part of the body.
     *
     * @return int
     *
     * @throws \Asmodine\CommonBundle\Exception\NotFoundTableException
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function generateFullSizeGuide(): int
    {
        $bodySlugs = Body::getSlugs();
        $this->checkTable(self::TREE, true);

        $insertSlug = array_map(
            function ($slug) {
                return "`{$slug}_min`, `{$slug}_max`";
            },
            $bodySlugs
        );
        $createSlug = array_map(
            function ($slug) {
                return "`{$slug}_min` SMALLINT NULL, `{$slug}_max` SMALLINT NULL";
            },
            $bodySlugs
        );
        $insertCoalesceSlug = array_map(
            function ($s) {
                return "COALESCE(p0.{$s}_min, m1.{$s}_min, c2.{$s}_min, c3.{$s}_min, c4.{$s}_min, b5.{$s}_min, NULL), "
                    ."COALESCE(p0.{$s}_max, m1.{$s}_max, c2.{$s}_max, c3.{$s}_max, c4.{$s}_max, b5.{$s}_max, NULL)";
            },
            $bodySlugs
        );

        $this->dropTable(self::TMP_SIZE_GUIDE);
        $this->execute(
            'CREATE TEMPORARY TABLE `'.self::TMP_SIZE_GUIDE.'` (
                `product_id`  INT UNSIGNED NOT NULL, '
            .implode(', ', $createSlug).', '
            .'PRIMARY KEY (`product_id`) '
            .')'
        );
        $this->execute(
            'INSERT IGNORE INTO `'.self::TMP_SIZE_GUIDE.'` (`product_id`, '.implode(', ', $insertSlug).')
                  SELECT t.product_id, '.implode(', ', $insertCoalesceSlug).'
                  FROM `'.self::TREE."` AS t
                  LEFT JOIN `back_size_guide_measure` AS p0 ON p0.type = 'product'  AND t.brand_size_id = p0.brand_size_id AND t.product_id = p0.type_id 
                  LEFT JOIN `back_size_guide_measure` AS m1 ON m1.type = 'model'    AND t.brand_size_id = m1.brand_size_id AND t.model_id = m1.type_id 
                  LEFT JOIN `back_size_guide_measure` AS c2 ON c2.type = 'category' AND t.brand_size_id = c2.brand_size_id AND t.category_depth2_id = c2.type_id
                  LEFT JOIN `back_size_guide_measure` AS c3 ON c3.type = 'category' AND t.brand_size_id = c3.brand_size_id AND t.category_depth1_id = c3.type_id 
                  LEFT JOIN `back_size_guide_measure` AS c4 ON c4.type = 'category' AND t.brand_size_id = c4.brand_size_id AND t.category_depth0_id = c4.type_id
                  LEFT JOIN `back_size_guide_measure` AS b5 ON b5.type = 'brand'    AND t.brand_size_id = b5.brand_size_id AND t.brand_id = b5.type_id"
        );

        return $this->count(self::TMP_SIZE_GUIDE, true, ['product_id']);
    }

    /**
     * Determines the number of measurement points required for 100% compatibility.
     *
     * @return int
     *
     * @throws \Asmodine\CommonBundle\Exception\NotFoundTableException
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function generateNumberOfPoints(): int
    {
        $this->checkTable(self::TMP_BODY, true);

        $this->dropTable(self::NUMBER_POINTS);
        $this->execute(
            'CREATE TABLE `'.self::NUMBER_POINTS.'` (
                `product_id`  INT UNSIGNED NOT NULL,
                `measure_count` TINYINT(1) NOT NULL,
             
                PRIMARY KEY (`product_id`)
            )'
        );
        $this->execute(
           'INSERT INTO `'.self::NUMBER_POINTS.'` (`product_id`, `measure_count`)
           SELECT sg.product_id, COUNT(*) AS nb FROM `'.self::SIZE_GUIDE.'` AS sg GROUP BY `product_id`'
       );

        // FIXME Non valide pour la V1 car certaines mensurations sont attendues mais non remplies
        /*     $this->execute(
            'INSERT INTO `'.self::NUMBER_POINTS.'` (`product_id`, `measure_count`)
            SELECT b.product_id, COUNT(*) AS nb FROM `'.self::TMP_BODY.'` AS b GROUP BY `product_id`'
        );*/

        return $this->count(self::NUMBER_POINTS, true, ['product_id']);
    }

    /**
     * Generate final size guide.
     *
     * @return int
     *
     * @throws \Asmodine\CommonBundle\Exception\NotFoundTableException
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function generateFinalSizeGuide(): int
    {
        $this->checkTable(self::TMP_BODY, true);
        $this->checkTable(self::TMP_SIZE_GUIDE, true);

        $bodySlugs = Body::getSlugs();

        $this->dropTable(self::SIZE_GUIDE);
        $this->execute(
            'CREATE TABLE `'.self::SIZE_GUIDE.'` (
                `product_id`  INT UNSIGNED NOT NULL,
                `body_part` VARCHAR(15) NOT NULL,
                `min` SMALLINT NOT NULL,
                `max` SMALLINT NOT NULL,
                PRIMARY KEY (`product_id`, `body_part`),
                INDEX `interval_idx` (`body_part`, `min`, `max`),
                INDEX `body_part_idx` (`body_part`),
                INDEX `min_max_idx` (`min`, `max`)
             )  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );

        $selectMin = 'CASE b.body_part';
        $selectMax = 'CASE b.body_part';
        foreach ($bodySlugs as $slug) {
            $selectMin .= " WHEN '{$slug}' THEN f.{$slug}_min";
            $selectMax .= " WHEN '{$slug}' THEN f.{$slug}_max";
        }
        $selectMin .= ' ELSE NULL END';
        $selectMax .= ' ELSE NULL END';

        $this->execute(
            'INSERT IGNORE INTO `'.self::SIZE_GUIDE.'` (`product_id`, `body_part`, `min`, `max`) '
            ."SELECT b.product_id, b.body_part, {$selectMin}, {$selectMax} FROM `".self::TMP_BODY.'` AS b INNER JOIN `'.self::TMP_SIZE_GUIDE.'` AS f ON b.product_id = f.product_id'
        );
        $this->execute('DELETE FROM `'.self::SIZE_GUIDE.'`  WHERE `min` < 0 OR `max` = 0');

        return $this->count(self::SIZE_GUIDE, true, ['product_id', 'body_part']);
    }

    /**
     * Return Select of datas to bulk.
     *
     * @param array $params
     * @param int   $offset
     * @param int   $limit
     *
     * @return Statement
     *
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function getBulkDatas(array $params, int $offset, int $limit): Statement
    {
        return $this->execute(
            "
          SELECT sg.*
          FROM `advisor_size_guide` AS sg
          LIMIT $limit OFFSET $offset"
        );
    }

    /**
     * Remove temporary tables.
     */
    public function clean(): void
    {
        $this->dropTable(self::TMP_BODY);
        $this->dropTable(self::TMP_SIZE_GUIDE);
    }
}
