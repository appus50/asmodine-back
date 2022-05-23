<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\AdminBundle\DTO\CatalogBrandDTO;
use Asmodine\AdminBundle\DTO\CatalogDTO;
use Asmodine\AdminBundle\Model\Brand;
use Asmodine\AdminBundle\Model\CatalogBrand\Configuration;
use Asmodine\AdminBundle\Model\CatalogBrand\Configuration\SimpleFilter;
use Asmodine\CommonBundle\DTO\BrandDTO;
use Asmodine\CommonBundle\Exception\NullException;
use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;
use Doctrine\DBAL\Statement;

/**
 * Class CatalogBrandRepository.
 */
class CatalogBrandRepository extends AbstractAsmodineRepository
{
    /**
     * Insert New CatalogBrand.
     *
     * @param CatalogDTO $catalog
     * @param BrandDTO   $brand
     * @param string     $configuration
     */
    public function insertWithDTO(CatalogDTO $catalog, BrandDTO $brand, string $configuration): void
    {
        $sql = 'INSERT INTO `back_catalog_brand` '
            .'(`catalog_id`, `brand_id`, `configuration`) '
            .'VALUES (:catalog, :brand, :configuration)';
        $params = [
            'catalog' => $catalog->id,
            'brand' => $brand->id,
            'configuration' => $configuration,
        ];

        $this->execute($sql, $params);
    }

    /**
     * Find all association brand <-> Catalog.
     *
     * @param Brand $brand
     *
     * @return CatalogBrandDTO[]
     */
    public function findByBrand(Brand $brand): array
    {
        $sql = 'SELECT * FROM `back_catalog_brand` WHERE `brand_id` = :brand_id';
        $statement = $this->execute($sql, ['brand_id' => $brand->getId()]);
        $catalogsBrand = [];
        while ($row = $statement->fetch()) {
            $catalogsBrand[] = new CatalogBrandDTO($row);
        }

        return $catalogsBrand;
    }

    /**
     * @param CatalogDTO $catalogDTO
     * @return array
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function findByCatalog(CatalogDTO $catalogDTO): array
    {
        $sql = 'SELECT * FROM `back_catalog_brand` WHERE `catalog_id` = :catalog_id';
        $statement = $this->execute($sql, ['catalog_id' => $catalogDTO->id]);
        $catalogsBrand = [];
        while ($row = $statement->fetch()) {
            $catalogsBrand[] = new CatalogBrandDTO($row);
        }

        return $catalogsBrand;
    }

    /**
     * Copy Table.
     *
     * @param string $fromTable
     * @param string $toTmpTable
     */
    public function copyTable(string $fromTable, string $toTmpTable): void
    {
        $this->dropTable($toTmpTable);
        $this->execute("CREATE TABLE `{$toTmpTable}` LIKE `{$fromTable}`");
        $this->execute("ALTER TABLE `{$toTmpTable}` ADD INDEX(`model_id`)");
        $this->execute("ALTER TABLE `{$toTmpTable}` ADD INDEX(`reference`)");
        $this->execute("ALTER TABLE `{$toTmpTable}` ADD INDEX(`name`)");
        $this->execute("ALTER TABLE `{$toTmpTable}` ADD INDEX(`brand`)");
        $this->execute("INSERT INTO `{$toTmpTable}` SELECT * FROM `{$fromTable}`");
    }

    /**
     * Executes SQL Action.
     *
     * @param string               $tableName
     * @param Configuration\Action $action
     *
     * @return Statement
     */
    public function executeAction(string $tableName, Configuration\Action $action): Statement
    {
        return $this->execute(sprintf($action->getSQLAction(), $tableName));
    }

    /**
     * Executes SQL Simple filter.
     *
     * @param string       $tableName
     * @param SimpleFilter $filter
     *
     * @return Statement
     */
    public function executeSimpleFilter(string $tableName, SimpleFilter $filter): Statement
    {
        return $this->execute(sprintf($filter->getSQLFilter(), $tableName));
    }

    /**
     * Clean Row Incomplete.
     *
     * @param string $tableName
     *
     * @return Statement
     */
    public function removeIncompleteRow(string $tableName): Statement
    {
        return $this->execute("DELETE FROM `{$tableName}` WHERE `url` IS NULL OR `unit_price` IS NULL");
    }

    /**
     * Update imported_at.
     *
     * @param Brand      $brand
     * @param CatalogDTO $catalog
     */
    public function setImported(Brand $brand, CatalogDTO $catalog): void
    {
        $sql = 'UPDATE `back_catalog_brand` SET imported_at = NOW() WHERE `brand_id` = :brand_id AND `catalog_id` = :catalog_id';
        $params = [
            'brand_id' => $brand->getId(),
            'catalog_id' => $catalog->id,
        ];

        $this->execute($sql, $params);
    }

    /**
     * Create temporary tables of model and products.
     *
     * @param string $tableName
     */
    public function createModelAndProductTables(string $tableName): void
    {
        $this->dropTable($tableName.'_model');
        $this->dropTable($tableName.'_product');
        $this->execute(
            "CREATE TABLE `{$tableName}_model` (
                `brand_id` INT UNSIGNED NOT NULL,
                `category_name` VARCHAR(255) NULL,
                
                `composition` TEXT NULL,
                
                `model_id` VARCHAR(128) NULL,
                `external_id` VARCHAR(255) NULL,
                `ean` VARCHAR(16) NULL,
                `sku` VARCHAR(32) NULL,
                `reference` VARCHAR(32) NULL,
                
                `name` VARCHAR(255) NULL,
                `slug` VARCHAR(255) NULL,
                `description` TEXT NULL,
                `description_short` TEXT NULL,
                `url` VARCHAR(512) NULL,
               
                `currency` VARCHAR(3) NOT NULL DEFAULT 'EUR',
                `unit_price` DECIMAL(10,2) NULL,
               
                `stock_in` BOOLEAN DEFAULT TRUE,
                `stock_amount` SMALLINT NULL,
               
                `discount` BOOLEAN NOT NULL DEFAULT FALSE,
                `discount_type` ENUM('percent', 'amount') NULL,
                `discount_value` DECIMAL(10,2) NULL,
                `discount_from` DATETIME NULL,
                `discount_to` DATETIME NULL,
                `discount_old_price` DECIMAL(10,2) NULL,
                
                `delivery_cost` DECIMAL(10,2) NULL,
                `delivery_information` TEXT NULL,
                               
                `active_from` DATE NULL,
                `active_to` DATE NULL,
               
                `further_information` TEXT NULL,
                
                `image_1` VARCHAR(512) NULL,
                `image_2` VARCHAR(512) NULL,
                `image_3` VARCHAR(512) NULL,
                `image_4` VARCHAR(512) NULL,
                `image_5` VARCHAR(512) NULL,
                `image_6` VARCHAR(512) NULL,
                `image_7` VARCHAR(512) NULL,
                `image_8` VARCHAR(512) NULL,
                `image_9` VARCHAR(512) NULL,
                `image_10` VARCHAR(512) NULL,
                               
                `is_products_data` BOOLEAN NOT NULL DEFAULT FALSE,
                               
                INDEX `brand_idx` (`brand_id`),
                INDEX `model_idx` (`brand_id`, `model_id`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $this->execute(
            "CREATE TABLE `{$tableName}_product` (
                `brand_id` INT UNSIGNED NOT NULL,
                `model_id` VARCHAR(128) NULL,
                
                `size` VARCHAR(63) NULL,
                `color` VARCHAR(127) NULL,
                `composition` TEXT NULL,
                
                `external_id` VARCHAR(255) NULL,
                `ean` VARCHAR(16) NULL,
                `sku` VARCHAR(32) NULL,
                `reference` VARCHAR(32) NULL,
                
                `name` VARCHAR(255) NULL,
                `description` TEXT NULL,
                `description_short` TEXT NULL,
                `url` VARCHAR(512) NULL,
               
                `unit_price` DECIMAL(10,2) NULL,
               
                `stock_in` BOOLEAN DEFAULT TRUE,
                `stock_amount` SMALLINT NULL,
               
                `discount` BOOLEAN NOT NULL DEFAULT FALSE,
                `discount_type` ENUM('percent', 'amount') NULL,
                `discount_value` DECIMAL(10,2) NULL,
                `discount_from` DATETIME NULL,
                `discount_to` DATETIME NULL,
                `discount_old_price` DECIMAL(10,2) NULL,
                
                `delivery_cost` DECIMAL(10,2) NULL,
                `delivery_information` TEXT NULL,
                               
                `active_from` DATE NULL,
                `active_to` DATE NULL,
                
                `further_information` TEXT NULL,
                
                `image_1` VARCHAR(512) NULL,
                `image_2` VARCHAR(512) NULL,
                `image_3` VARCHAR(512) NULL,
                `image_4` VARCHAR(512) NULL,
                `image_5` VARCHAR(512) NULL,
                `image_6` VARCHAR(512) NULL,
                `image_7` VARCHAR(512) NULL,
                `image_8` VARCHAR(512) NULL,
                `image_9` VARCHAR(512) NULL,
                `image_10` VARCHAR(512) NULL,
                
                INDEX `model_idx` (`model_id`),
                INDEX `size_idx` (`size`),
                INDEX `color_idx` (`color`)
              ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    /**
     * If catalog contains one product per line.
     *
     * @param string $tableName
     * @param Brand  $brand
     */
    public function populateWithProduct(string $tableName, Brand $brand): void
    {
        $sql = "INSERT INTO `{$tableName}_product` "
            .'(`brand_id`,`model_id`, `size`,`color`, `composition`,`external_id`,`ean`,`sku`,`reference`, '
            .'`name`,`description`,`description_short`, `url`,`unit_price`,`stock_in`,`stock_amount`, '
            .'`discount_type`,`discount_value`,`discount_from`,`discount_to`,`discount_old_price`,`delivery_cost`, '
            .'`delivery_information`, `active_from`,`active_to`,`further_information`, '
            .'`image_1`, `image_2`, `image_3`, `image_4`, `image_5`, `image_6`, `image_7`, `image_8`, `image_9`, `image_10`) '
            .'SELECT :brand_id, `model_id`, `size`,`color`, `composition`,`external_id`,`ean`,`sku`,`reference`, '
            .'`name`,`description`,`description_short`, `url`,`unit_price`,`stock_in`,`stock_amount`, '
            .'`discount_type`,`discount_value`,`discount_from`,`discount_to`,`discount_old_price`,`delivery_cost`, '
            .'`delivery_information`, `active_from`,`active_to`,`further_information`, '
            .'`image_1`, `image_2`, `image_3`, `image_4`, `image_5`, `image_6`, `image_7`, `image_8`, `image_9`, `image_10` '
            ." FROM `{$tableName}`";
        $params = [
            'brand_id' => $brand->getId(),
        ];
        $this->execute($sql, $params);

        $sql = "INSERT INTO `{$tableName}_model` (`brand_id`, `is_products_data`, `category_name`, `model_id`, `name`, `description_short`, `description`, `composition`) "
            ."SELECT :brand_id, TRUE, `category`, `model_id`, `name`, `description_short`, `description`, `composition` FROM `{$tableName}` GROUP BY `model_id`";
        $this->execute($sql, $params);
    }

    /**
     * If catalog contains one model per line.
     *
     * @param string        $tableName
     * @param Brand         $brand
     * @param Configuration $configuration
     *
     * @throws NullException
     */
    public function populateWithModel(string $tableName, Brand $brand, Configuration $configuration): void
    {
        $sizeSeparators = $configuration->getSeparatorSize();
        $colorSeparators = $configuration->getSeparatorColor();

        $sql = "INSERT INTO `{$tableName}_model` "
            .'(`brand_id`,`model_id`, `category_name`, `composition`, `external_id`,`ean`,`sku`,`reference`, '
            .'`name`,`description`,`description_short`, `url`,`unit_price`,`stock_in`,`stock_amount`, '
            .'`discount_type`,`discount_value`,`discount_from`,`discount_to`,`discount_old_price`,`delivery_cost`, '
            .'`delivery_information`, `active_from`,`active_to`,`further_information`, '
            .'`image_1`, `image_2`, `image_3`, `image_4`, `image_5`, `image_6`, `image_7`, `image_8`, `image_9`, `image_10`) '
            .'SELECT :brand_id, `model_id`, `category`, `composition`,`external_id`,`ean`,`sku`,`reference`, '
            .'`name`,`description`,`description_short`, `url`,`unit_price`,`stock_in`,`stock_amount`, '
            .'`discount_type`,`discount_value`,`discount_from`,`discount_to`,`discount_old_price`,`delivery_cost`, '
            .'`delivery_information`, `active_from`,`active_to`,`further_information`, '
            .'`image_1`, `image_2`, `image_3`, `image_4`, `image_5`, `image_6`, `image_7`, `image_8`, `image_9`, `image_10` '
            ." FROM `{$tableName}`";
        $params = [
            'brand_id' => $brand->getId(),
        ];
        $this->execute($sql, $params);

        foreach (['size', 'color'] as $field) {
            $this->dropTable($tableName.'_'.$field.'_numbers_tmp');
            $separators = [];
            if ('size' == $field) {
                $separators = $sizeSeparators;
            }
            if ('color' == $field) {
                $separators = $colorSeparators;
            }

            if (0 == count($separators)) {
                $separator = '§';
            }
            if (1 == count($separators)) {
                $separator = $separators[0];
            }
            if (count($separators) > 1) {
                $replaceFunc = function ($aSeparator) use ($tableName, $field) {
                    $this->execute("UPDATE `$tableName` SET `$field` = REPLACE(`$field`, '$aSeparator', '§')");
                };
                array_map($replaceFunc, $separators);
                $separator = '§';
            }
            $this->execute(
                "CREATE TEMPORARY TABLE `{$tableName}_{$field}_numbers_tmp` AS "
                ."SELECT i.model_id AS model_id, TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(i.$field, '$separator', u.n), '$separator', -1)) AS `$field` "
                ."FROM `{$tableName}` AS i INNER JOIN `back_utils_numbers` AS u "
                ."ON CHAR_LENGTH(i.size) - CHAR_LENGTH(REPLACE(i.size, '$separator', ''))>=u.n-1 "
                .'ORDER BY i.model_id, u.n'
            );
            $this->execute('ALTER TABLE `'.$tableName.'_'.$field.'_numbers_tmp` ADD INDEX(`model_id`)');
        }

        $sql = "INSERT INTO `{$tableName}_product` (`brand_id`,`model_id`, `size`, `color`) "
            .'SELECT :brand_id, i.model_id, s.size, c.color '
            ."FROM `{$tableName}` AS i, `{$tableName}_size_numbers_tmp` AS s, `{$tableName}_color_numbers_tmp` AS c "
            .'WHERE i.model_id = s.model_id AND i.model_id = c.model_id';
        $params = [
            'brand_id' => $brand->getId(),
        ];
        $this->execute($sql, $params);

        $this->dropTable($tableName.'_size_numbers_tmp');
        $this->dropTable($tableName.'_color_numbers_tmp');
    }

    /**
     * Insert New Categories, Sizes and Color
     * Link Attributes.
     *
     * @param $modelTableName
     * @param $productTableName
     */
    public function manageAttributes($modelTableName, $productTableName)
    {
        // INSERT NEW
        // Model
        $sql = 'INSERT IGNORE INTO `back_category_brand` (`brand_id`, `name`) '
            ."SELECT `brand_id`, `category_name` FROM `$modelTableName`";
        $this->execute($sql);

        // Products
        foreach (['size', 'color'] as $attr) {
            $tableAttrAsmodine = 'back_'.$attr.'_brand';
            $sql = "INSERT IGNORE INTO `$tableAttrAsmodine` (`brand_id`, `name`) "
                ."SELECT `brand_id`, `$attr` FROM `$productTableName`";
            $this->execute($sql);
        }

        // LINK
        $this->execute("ALTER TABLE `$modelTableName` ADD `category_id` INT UNSIGNED NULL AFTER `category_name`");
        $this->execute("ALTER TABLE `$productTableName` ADD `size_id` INT UNSIGNED NULL AFTER `size`");
        $this->execute("ALTER TABLE `$productTableName` ADD `color_id` INT UNSIGNED NULL AFTER `color`");

        $this->execute("UPDATE `$modelTableName` AS i SET `category_id` = (SELECT `id` FROM `back_category_brand` AS b WHERE i.category_name = b.name AND i.brand_id = b.brand_id)");
        $this->execute("UPDATE `$productTableName` AS i SET `size_id` = (SELECT `id` FROM `back_size_brand` AS b WHERE i.size = b.name AND i.brand_id = b.brand_id)");
        $this->execute("UPDATE `$productTableName` AS i SET `color_id` = (SELECT `id` FROM `back_color_brand` AS b WHERE i.color = b.name AND i.brand_id = b.brand_id)");
    }

    /**
     * Insert/Update model and products.
     *
     * @param int    $brandId
     * @param string $modelTableName
     * @param string $productTableName
     */
    public function updateAsmodineModelAndProduct(int $brandId, string $modelTableName, string $productTableName): void
    {
        $this->execute(
            'UPDATE `back_model` SET `is_enabled_auto` = false WHERE `brand_id` = :brand_id',
            ['brand_id' => $brandId]
        );
        $this->execute(
            'UPDATE `back_product` SET `is_enabled_auto` = false WHERE `brand_id` = :brand_id',
            ['brand_id' => $brandId]
        );

        $sqlModel = 'INSERT INTO `back_model` (`id`, `brand_id`, `category_id`, `composition`, `model_id`, `external_id`, '
            .'`ean`, `sku`, `reference`, `name`, `slug`, `description`, `description_short`, `url`, `currency`, `unit_price`, '
            .'`stock_in`, `stock_amount`, `discount`, `discount_type`, `discount_value`, `discount_from`, `discount_to`, '
            .'`discount_old_price`, `delivery_cost`, `delivery_information`, `active_from`, `active_to`, `further_information`, '
            .'`is_products_data`, `is_enabled_auto`, `created_at`, `updated_at`) '
            .'SELECT NULL, `brand_id`, `category_id`, `composition`, `model_id`, `external_id`, '
            .'`ean`, `sku`, `reference`, `name`, `slug`, `description`, `description_short`, `url`, `currency`, `unit_price`, '
            .'`stock_in`, `stock_amount`, `discount`, `discount_type`, `discount_value`, `discount_from`, `discount_to`, '
            .'`discount_old_price`, `delivery_cost`, `delivery_information`, `active_from`, `active_to`, `further_information`, '
            ."`is_products_data`, TRUE, NOW(), NOW() FROM `{$modelTableName}` AS i "
            .'ON DUPLICATE KEY UPDATE '
            .'`category_id` = i.category_id, '
            .'`composition` = i.composition, '
            .'`external_id` = i.external_id, '
            .'`ean` = i.ean, '
            .'`sku` = i.sku, '
            .'`reference` = i.reference, '
            .'`name` = i.name, '
            .'`slug` = i.slug, '
            .'`description` = i.description, '
            .'`description_short` = i.description_short, '
            .'`url` = i.url, '
            .'`currency` = i.currency, '
            .'`unit_price` = i.unit_price, '
            .'`stock_in` = i.stock_in, '
            .'`stock_amount` = i.stock_amount, '
            .'`discount` = i.discount, '
            .'`discount_type` = i.discount_type, '
            .'`discount_value` = i.discount_value, '
            .'`discount_from` = i.discount_from, '
            .'`discount_to` = i.discount_to, '
            .'`discount_old_price` = i.discount_old_price, '
            .'`delivery_cost` = i.delivery_cost, '
            .'`delivery_information` = i.delivery_information, '
            .'`active_from` = i.active_from, '
            .'`active_to` = i.active_to, '
            .'`further_information` = i.further_information, '
            .'`is_products_data` = i.is_products_data, '
            .'`is_enabled_auto` = true, '
            .'`updated_at` = NOW()';
        $this->execute($sqlModel);

        $this->execute("ALTER TABLE `$productTableName` ADD `model_asmodine_id` INT UNSIGNED NULL AFTER `model_id`");
        $this->execute("UPDATE `$productTableName` AS i SET `model_asmodine_id` = (SELECT `id` FROM `back_model` AS m WHERE i.model_id = m.model_id AND i.brand_id = m.brand_id)");
        $this->execute("DELETE FROM `$productTableName` WHERE  `model_asmodine_id` IS NULL"); //FIXME Hack to delete
        $sqlProduct = 'INSERT INTO `back_product` (`id`, `brand_id`, `composition`, `model_id`, `external_id`, '
            .'`size_id`, `color_id`,  '
            .'`ean`, `sku`, `reference`, `name`, `description`, `description_short`, `url`, `unit_price`, '
            .'`stock_in`, `stock_amount`, `discount`, `discount_type`, `discount_value`, `discount_from`, `discount_to`, '
            .'`discount_old_price`, `delivery_cost`, `delivery_information`, `active_from`, `active_to`, `further_information`, '
            .'`is_enabled_auto`, `created_at`, `updated_at`) '
            .'SELECT NULL, `brand_id`, `composition`, `model_asmodine_id`, `external_id`, '
            .'`size_id`, `color_id`,  '
            .'`ean`, `sku`, `reference`, `name`, `description`, `description_short`, `url`, `unit_price`, '
            .'`stock_in`, `stock_amount`, `discount`, `discount_type`, `discount_value`, `discount_from`, `discount_to`, '
            .'`discount_old_price`, `delivery_cost`, `delivery_information`, `active_from`, `active_to`, `further_information`, '
            .'TRUE, NOW(), NOW() FROM `'.$productTableName.'` AS i '
            .'ON DUPLICATE KEY UPDATE '
            .'`model_id` = i.model_asmodine_id, '
            .'`size_id` = i.size_id, '
            .'`color_id` = i.color_id, '
            .'`composition` = i.composition, '
            .'`external_id` = i.external_id, '
            .'`ean` = i.ean, '
            .'`sku` = i.sku, '
            .'`reference` = i.reference, '
            .'`name` = i.name, '
            .'`description` = i.description, '
            .'`description_short` = i.description_short, '
            .'`url` = i.url, '
            .'`unit_price` = i.unit_price, '
            .'`stock_in` = i.stock_in, '
            .'`stock_amount` = i.stock_amount, '
            .'`discount` = i.discount, '
            .'`discount_type` = i.discount_type, '
            .'`discount_value` = i.discount_value, '
            .'`discount_from` = i.discount_from, '
            .'`discount_to` = i.discount_to, '
            .'`discount_old_price` = i.discount_old_price, '
            .'`delivery_cost` = i.delivery_cost, '
            .'`delivery_information` = i.delivery_information, '
            .'`active_from` = i.active_from, '
            .'`active_to` = i.active_to, '
            .'`further_information` = i.further_information, '
            .'`is_enabled_auto` = true, '
            .'`updated_at` = NOW()';
        $this->execute($sqlProduct);

        $this->execute(
            'UPDATE `back_model` SET `is_deleted` = true, `deleted_at` = NOW() WHERE `brand_id` = :brand_id AND `is_enabled_auto` = false',
            ['brand_id' => $brandId]
        );
        $this->execute(
            'UPDATE `back_product` SET `is_deleted` = true, `deleted_at` = NOW() WHERE `brand_id` = :brand_id AND `is_enabled_auto` = false',
            ['brand_id' => $brandId]
        );
    }

    /**
     * Update Table back_image.
     *
     * @param string $tableName
     * @param bool   $isModel
     */
    public function updateAsmodineImage(string $tableName, bool $isModel): void
    {
        if ($isModel) {
            $type = 'model';
            $id = 'model_asmodine_id';
            $this->execute("ALTER TABLE `{$tableName}_{$type}` ADD `$id` INT UNSIGNED NULL AFTER `model_id`");
            $this->execute("UPDATE `{$tableName}_{$type}` AS i SET `$id` = (SELECT `id` FROM `back_model` AS m WHERE i.model_id = m.model_id AND i.brand_id = m.brand_id)");
        }
        if (!$isModel) {
            $type = 'product';
            $id = 'product_asmodine_id';
            $this->execute("ALTER TABLE `{$tableName}_{$type}` ADD `$id` INT UNSIGNED NULL AFTER `model_asmodine_id`");
            $this->execute("UPDATE `{$tableName}_{$type}` AS i SET `$id` = (SELECT `id` FROM `back_product` AS p WHERE i.model_asmodine_id = p.model_id AND i.brand_id = p.brand_id AND i.size_id = p.size_id AND i.color_id = p.color_id)");
        }

        for ($i = 1; $i <= 10; ++$i) {
            $this->execute(
                'INSERT IGNORE INTO `back_image` (`type`, `external_id`, `initial_link`, `position`, `created_at`, `updated_at`) '
                ." SELECT '$type', $id, `image_$i`, $i, NOW(), NOW() FROM `{$tableName}_{$type}` WHERE LENGTH(`image_$i`) > 12"
            );
        }
    }

    /**
     * Count Nb Rows.
     *
     * @param string $tableName
     *
     * @return int
     */
    public function countRows(string $tableName): int
    {
        $usePrimary = false === strpos($tableName, 'tmp_');

        return $this->count($tableName, $usePrimary);
    }

    /**
     * Drop import Tables.
     *
     * @param string $tableName
     */
    public function dropImportTables(string $tableName): void
    {
        $this->dropTable($tableName);
        $this->dropTable($tableName.'_model');
        $this->dropTable($tableName.'_product');
    }
}
