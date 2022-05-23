<?php

namespace Application\Migrations;

use Asmodine\CommonBundle\Model\Morphoprofile\Gender;
use Asmodine\CommonBundle\Model\Morphoprofile\Morphoprofile;
use Asmodine\CommonBundle\Model\Morphoprofile\Size;
use Asmodine\CommonBundle\Model\Morphoprofile\Weight;
use Asmodine\CommonBundle\Model\Morphotype\Morphotype;
use Asmodine\CommonBundle\Model\Profile\Body;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20170717100000.
 *
 * Intial database structure
 */
class Version20170717100000 extends AbstractMigration
{
    /**
     * Create all initial tables.
     *
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->createUtilTables();
        $this->createBrandTable();
        $this->createCategoriesTables();
        $this->createAttributesTables();
        $this->createProductsTables();
        $this->createTagTables();
        $this->createImportConfigTables();
        $this->createSizeGuideTables();
    }

    /**
     * Delete all initial tables.
     *
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE IF EXISTS `back_utils_numbers`');
        $this->addSql('DROP TABLE IF EXISTS `back_utils_synonyms`');

        $this->addSql('DROP TABLE IF EXISTS `back_size_guide_body_part`');
        $this->addSql('DROP TABLE IF EXISTS `back_size_guide_measure`');

        $this->addSql('DROP TABLE IF EXISTS `back_catalog_brand`');
        $this->addSql('DROP TABLE IF EXISTS `back_catalog`');

        $this->addSql('DROP TABLE IF EXISTS `back_tag_model`');
        $this->addSql('DROP TABLE IF EXISTS `back_tag`');

        $this->addSql('DROP TABLE IF EXISTS `back_image`');
        $this->addSql('DROP TABLE IF EXISTS `back_product`');
        $this->addSql('DROP TABLE IF EXISTS `back_model`');

        $this->addSql('DROP TABLE IF EXISTS `back_style_morphology`');
        $this->addSql('DROP TABLE IF EXISTS `back_style_category_asmodine`');
        $this->addSql('DROP TABLE IF EXISTS `back_size_brand`');
        $this->addSql('DROP TABLE IF EXISTS `back_color_morphotype`');
        $this->addSql('DROP TABLE IF EXISTS `back_color_brand`');
        $this->addSql('DROP TABLE IF EXISTS `back_color_asmodine`');

        $this->addSql('DROP TABLE IF EXISTS `back_category_brand`');
        $this->addSql('DROP TABLE IF EXISTS `back_category_asmodine`');

        $this->addSql('DROP TABLE IF EXISTS `back_brand`');
    }

    /**
     * Size Guide
     */
    private function createSizeGuideTables()
    {
        $initBodyPartFunc = function ($part) {
            $sql = "SMALLINT DEFAULT NULL COMMENT 'In millimeters'";

            return
                "`{$part}_min` {$sql}, "
                ."`{$part}_med` {$sql}, "
                ."`{$part}_max` {$sql}";
        };
        $bodyPartsSQL = array_map($initBodyPartFunc, Body::getSlugs());
        $this->addSql(
            "CREATE TABLE back_size_guide_measure (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `brand_size_id` INT UNSIGNED NOT NULL,
                `type` ENUM('brand', 'category', 'model', 'product') NOT NULL,
                `type_id` INT UNSIGNED NOT NULL COMMENT 'id of product, model, category or brand', "
            .implode(', ', $bodyPartsSQL).", 
    
                PRIMARY KEY (`id`),
                UNIQUE `type_idx` (`brand_size_id`, `type`, `type_id`)
                ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $this->addSql(
            'ALTER TABLE `back_size_guide_measure`
                ADD CONSTRAINT fk_brand_size_id
                FOREIGN KEY (`brand_size_id`) REFERENCES `back_size_brand` (`id`)
                ON DELETE CASCADE'
        );

        $this->addSql(
            "CREATE TABLE back_size_guide_body_part (
              `brand_id` INT UNSIGNED NOT NULL COMMENT '0 if default values (=> type=category with depth=2)',
              `type` ENUM('category', 'model', 'product') NOT NULL,
              `type_id` INT UNSIGNED NOT NULL COMMENT 'id of product, model or category (not the brand as the size_guide_measure)',
              `".implode("` TINYINT(1) NOT NULL DEFAULT 0, `", Body::getSlugs())."` TINYINT(1) NOT NULL DEFAULT 0,
              
              PRIMARY KEY (`brand_id`, `type`, `type_id`),
              INDEX `type_idx` (`type`, `type_id`)
            )
            ENGINE = MYISAM
            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    /**
     * Utils Tables
     */
    private function createUtilTables()
    {
        $this->addSql("CREATE TABLE `back_utils_numbers` (`n` INT NOT NULL, INDEX `n_idx` (`n`)) COMMENT 'Utility table for cutting sizes and colors'");
        $this->addSql('INSERT INTO `back_utils_numbers` VALUES (1),(2),(3),(4),(5),(6),(7),(8),(9),(10),(11),(12)');

        $this->addSql(
            "CREATE TABLE `back_utils_synonyms` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `type` ENUM('category', 'style', 'color') NOT NULL,
                `external_id` INT UNSIGNED NOT NULL COMMENT 'back_category_asmodine.id OR back_style_category_asmodine.id OR back_color_asmodine.id',
                `value` VARCHAR(255) NOT NULL,
                `priority` TINYINT UNSIGNED NOT NULL DEFAULT 100 COMMENT '[0,255]',
                
                PRIMARY KEY (`id`),
                INDEX (`type`, `external_id`)
            ) 
            ENGINE = MYISAM
            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    /**
     * Brand table.
     */
    private function createBrandTable()
    {
        $this->addSql(
            'CREATE TABLE `back_brand` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL,
                `description` TEXT NULL,
                `enabled` BOOLEAN NOT NULL DEFAULT FALSE,
                `logo` VARCHAR(512) NULL,
                `iframe`  BOOLEAN NOT NULL DEFAULT TRUE,
                
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP NOT NULL,
                                
                PRIMARY KEY (`id`),
                UNIQUE `slug_idx` (`slug`),
                INDEX `enabled_idx` (`enabled`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
    }

    /**
     * Creation of Asmodine category with associate style and brand category.
     */
    private function createCategoriesTables()
    {
        // Asmodine Category
        $this->addSql(
            "CREATE TABLE `back_category_asmodine` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `parent_id` INT UNSIGNED NULL,
                `name` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL,
                `description` TEXT NULL,
                `gender` ENUM('".implode("','", Gender::getSlugs())."') NULL,
                `path` VARCHAR(512) NOT NULL,
                `depth` TINYINT UNSIGNED NOT NULL,
                `position` TINYINT UNSIGNED NULL,
                `enabled` BOOLEAN NOT NULL DEFAULT FALSE,
                `icon` VARCHAR(255) NULL,
                
                PRIMARY KEY (`id`),
                UNIQUE `path_idx` (`path`),
                UNIQUE `slug_idx` (`parent_id`, `slug`),
                INDEX `enabled_idx` (`enabled`),
                INDEX `position_idx` (`position`),
                INDEX `depth_idx` (`depth`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $this->addSql(
            'ALTER TABLE `back_category_asmodine`
                ADD CONSTRAINT fk_category_parent
                FOREIGN KEY (`parent_id`) REFERENCES `back_category_asmodine` (`id`)
                ON DELETE CASCADE'
        );

        // Brand Category
        $this->addSql(
            'CREATE TABLE `back_category_brand` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `brand_id` INT UNSIGNED NOT NULL,
                `category_asmodine_id` INT UNSIGNED NULL,
                `name` VARCHAR(255) NOT NULL,
                
                PRIMARY KEY (`id`),
                INDEX `brand_idx` (`brand_id`),
                INDEX `category_asmodine_idx` (`category_asmodine_id`),
                UNIQUE `category_name_idx` (`brand_id`, `name`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        $this->addSql(
            'ALTER TABLE `back_category_brand`
                ADD CONSTRAINT fk_category_brand_category_asmodine
                FOREIGN KEY (`category_asmodine_id`) REFERENCES `back_category_asmodine` (`id`)
                ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE `back_category_brand`
                ADD CONSTRAINT fk_category_brand_brand
                FOREIGN KEY (`brand_id`) REFERENCES `back_brand` (`id`)
                ON DELETE CASCADE'
        );
    }

    /**
     * Creation of Color and size.
     */
    private function createAttributesTables()
    {
        $this->addSql(
            'CREATE TABLE `back_color_asmodine` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(127) NOT NULL,
                `hexa` VARCHAR(6) NOT NULL,
                
                PRIMARY KEY (`id`),
                INDEX `name_idx` (`name`),
                INDEX `hexa_idx` (`name`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        $this->addSql(
            'CREATE TABLE `back_color_brand` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `brand_id` INT UNSIGNED NOT NULL,
                `color_asmodine_id` INT UNSIGNED NULL,
                `name` VARCHAR(127) NOT NULL,
                `hexa` VARCHAR(6) NULL,
                
                PRIMARY KEY (`id`),
                INDEX `brand_idx` (`brand_id`),
                UNIQUE `name_idx` (`brand_id`, `name`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        $this->addSql(
            'ALTER TABLE `back_color_brand`
                ADD CONSTRAINT fk_color_brand_color_asmodine
                FOREIGN KEY (`color_asmodine_id`) REFERENCES `back_color_asmodine` (`id`)
                ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE `back_color_brand`
                ADD CONSTRAINT fk_color_brand_brand
                FOREIGN KEY (`brand_id`) REFERENCES `back_brand` (`id`)
                ON DELETE CASCADE'
        );

        $this->addSql(
            "CREATE TABLE `back_color_morphotype` (
                `color_asmodine_id` INT UNSIGNED NOT NULL,
                `morphotype` ENUM('".implode("','", Morphotype::getSlugs())."'),
                `note` TINYINT(1) NOT NULL,
                
                PRIMARY KEY (`color_asmodine_id`, `morphotype` )
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $this->addSql(
            'ALTER TABLE `back_color_morphotype`
                ADD CONSTRAINT fk_color_morphotype_color_asmodine
                FOREIGN KEY (`color_asmodine_id`) REFERENCES `back_color_asmodine` (`id`)
                ON DELETE CASCADE'
        );

        $this->addSql(
            'CREATE TABLE `back_size_brand` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `brand_id` INT UNSIGNED NOT NULL,
                `name`  VARCHAR(63) NOT NULL,
                
                PRIMARY KEY (`id`),
                INDEX `brand_idx` (`brand_id`),
                UNIQUE `name_idx` (`brand_id`, `name`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        $this->addSql(
            'ALTER TABLE `back_size_brand`
                ADD CONSTRAINT fk_size_brand_brand
                FOREIGN KEY (`brand_id`) REFERENCES `back_brand` (`id`)
                ON DELETE CASCADE'
        );

        $this->addSql(
            'CREATE TABLE `back_style_category_asmodine` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `category_asmodine_id` INT UNSIGNED NULL,
                `name` VARCHAR(255) NOT NULL,
                `enabled` BOOLEAN NOT NULL DEFAULT FALSE,
                
                PRIMARY KEY (`id`),
                INDEX `name_idx` (`name`),
                INDEX `category_asmodine_idx` (`category_asmodine_id`),
                INDEX `enabled_idx` (`enabled`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        $this->addSql(
            'ALTER TABLE `back_style_category_asmodine`
                ADD CONSTRAINT fk_style_asmodine_category_asmodine
                FOREIGN KEY (`category_asmodine_id`) REFERENCES `back_category_asmodine` (`id`)
                ON DELETE CASCADE'
        );

        $this->addSql(
            "CREATE TABLE `back_style_morphology` (
                `style_asmodine_id` INT UNSIGNED NOT NULL,
                `size` ENUM('".implode("','", Size::getSlugs())."') NOT NULL,
                `morphoprofile` ENUM('".implode("','", Morphoprofile::getSlugs())."') NOT NULL,
                `morpho_weight` ENUM('".implode("','", Weight::getSlugs())."') NOT NULL,
                `note` TINYINT(1) NOT NULL,
                
                PRIMARY KEY (`style_asmodine_id`, `size`, `morphoprofile`, `morpho_weight`),
                INDEX(`style_asmodine_id`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $this->addSql(
            'ALTER TABLE `back_style_morphology`
                ADD CONSTRAINT fk_style_morphology_style_asmodine
                FOREIGN KEY (`style_asmodine_id`) REFERENCES `back_style_category_asmodine` (`id`)
                ON DELETE CASCADE'
        );
    }

    /**
     * Creation of Model, Product and Images tables.
     */
    private function createProductsTables()
    {
        $this->addSql(
            "CREATE TABLE `back_model` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `brand_id` INT UNSIGNED NOT NULL,
                `category_id` INT UNSIGNED NOT NULL,
                `style_id` INT UNSIGNED NULL,
                
                `composition` TEXT NULL,
                
                `model_id` VARCHAR(128) NOT NULL,
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
                `unit_price` DECIMAL(10,2) NULL COMMENT 'Current Price (with discount if exist)',
               
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
               
                `enabled` BOOLEAN NOT NULL DEFAULT TRUE,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                
                PRIMARY KEY (`id`),
                UNIQUE `model_idx` (`brand_id`, `model_id`),
                UNIQUE `slug_idx` (`brand_id`, `slug`),
                INDEX `brand_idx` (`brand_id`),
                INDEX `stock_idx` (`stock_in`),
                INDEX `style_idx` (`style_id`),
                INDEX `name_idx` (`name`),
                INDEX `enabled_idx` (`enabled`),
                INDEX `discount_idx` (`discount`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $this->addSql(
            'ALTER TABLE `back_model`
                ADD CONSTRAINT fk_brand
                FOREIGN KEY (`brand_id`) REFERENCES `back_brand` (`id`)
                ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE `back_model`
                ADD CONSTRAINT fk_category
                FOREIGN KEY (`category_id`) REFERENCES `back_category_brand` (`id`)
                ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE `back_model`
                ADD CONSTRAINT fk_category_style
                FOREIGN KEY (`style_id`) REFERENCES `back_style_category_asmodine` (`id`)
                ON DELETE CASCADE'
        );

        // Product
        $this->addSql(
            "CREATE TABLE `back_product` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `brand_id` INT UNSIGNED NOT NULL,
                `model_id` INT UNSIGNED NOT NULL COMMENT 'back_product.model == back_model.id | back_product.model != back_model.model_id',
                
                `size_id` INT UNSIGNED NULL,
                `color_id` INT UNSIGNED NULL,
                `composition` TEXT NULL,
                `weight` VARCHAR(63) NULL,
                
                `external_id` VARCHAR(255) NULL,
                `ean` VARCHAR(16) NULL,
                `sku` VARCHAR(32) NULL,
                `reference` VARCHAR(32) NULL,
                
                `name` VARCHAR(255) NULL,
                `description` TEXT NULL,
                `description_short` TEXT NULL,
                `url` VARCHAR(512) NULL,
               
                `unit_price` DECIMAL(10,2) NULL COMMENT 'Current Price (with discount if exist)',
               
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
                
                `enabled` BOOLEAN NOT NULL DEFAULT TRUE,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                
                PRIMARY KEY (`id`),
                UNIQUE `product_idx` (`brand_id`, `model_id`, `size_id`, `color_id`),
                INDEX `brand_idx` (`brand_id`),
                INDEX `model_idx` (`model_id`),
                INDEX `size_idx` (`size_id`),
                INDEX `color_idx` (`color_id`),
                INDEX `stock_idx` (`stock_in`),
                INDEX `name_idx` (`name`),
                INDEX `enabled_idx` (`enabled`),
                INDEX `discount_idx` (`discount`),
                INDEX `updated_idx` (`updated_at`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $this->addSql(
            'ALTER TABLE `back_product`
                ADD CONSTRAINT fk_product_brand
                    FOREIGN KEY (`brand_id`) REFERENCES `back_brand` (`id`)
                    ON DELETE CASCADE,
                ADD CONSTRAINT fk_product_model
                    FOREIGN KEY (`model_id`) REFERENCES `back_model` (`id`)
                    ON DELETE CASCADE,
                ADD CONSTRAINT fk_product_size
                    FOREIGN KEY (`size_id`) REFERENCES `back_size_brand` (`id`)
                    ON DELETE SET NULL,
                ADD CONSTRAINT fk_product_color
                    FOREIGN KEY (`color_id`) REFERENCES `back_color_brand` (`id`)
                    ON DELETE SET NULL'
        );

        // Image
        $this->addSql(
            "CREATE TABLE `back_image` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `type` ENUM('model', 'product') NOT NULL,
                `external_id` INT UNSIGNED NOT NULL COMMENT 'model.id OR product.id',
                `initial_link` VARCHAR(512) NOT NULL,
                `local_link`  VARCHAR(255) NULL,
                `position` TINYINT UNSIGNED NOT NULL,
                `enabled` BOOLEAN NOT NULL DEFAULT TRUE,
                `download` BOOLEAN NOT NULL DEFAULT FALSE,
                                
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                
                PRIMARY KEY (`id`),
                INDEX `model_or_product_idx` (`type`, `external_id`, `enabled`),
                INDEX `download_idx` (`download`),
                INDEX `updated_idx` (`updated_at`)
            )
            ENGINE = MYISAM
            CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
            COMMENT 'Model or Product images only'"
        );
    }

    /**
     * Creation of Tag and Association.
     */
    private function createTagTables()
    {
        $this->addSql(
            "CREATE TABLE `back_tag` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL,
                `enabled` BOOLEAN NOT NULL DEFAULT TRUE,
                `configuration` MEDIUMTEXT NULL COMMENT 'JSON Format',
                
                PRIMARY KEY (`id`),
                UNIQUE `slug_idx` (`slug`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $this->addSql(
            'CREATE TABLE `back_tag_model` (
                `tag_id` INT UNSIGNED NOT NULL,
                `model_id` INT UNSIGNED NOT NULL,
                
                `created_at` DATETIME NOT NULL,
                
                INDEX `tag_idx` (`tag_id`),
                INDEX `model_idx` (`model_id`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        $this->addSql(
            'ALTER TABLE `back_tag_model`
                ADD CONSTRAINT fk_tag_model_tag
                FOREIGN KEY (`tag_id`) REFERENCES `back_tag` (`id`)
                ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE `back_tag_model`
                ADD CONSTRAINT fk_tag_model_model
                FOREIGN KEY (`model_id`) REFERENCES `back_model` (`id`)
                ON DELETE CASCADE'
        );
    }

    /**
     * Creation of tables required for importing products.
     */
    private function createImportConfigTables()
    {
        $this->addSql(
            "CREATE TABLE `back_catalog` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `origin` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL,
                `configuration` MEDIUMTEXT NULL COMMENT 'JSON Format',
                `enabled` BOOLEAN NOT NULL DEFAULT FALSE,
                `imported_at` DATETIME NULL,
                
                PRIMARY KEY (`id`),
                UNIQUE `slug_idx` (`slug`),
                INDEX `name_idx` (`name`),
                INDEX `origin_idx` (`origin`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE `back_catalog_brand` (
                `catalog_id`  INT UNSIGNED NOT NULL,
                `brand_id`   INT UNSIGNED NOT NULL,
                `configuration` MEDIUMTEXT NULL COMMENT 'JSON Format',
                `imported_at` DATETIME NULL,
                
                PRIMARY KEY (`catalog_id`,`brand_id`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $this->addSql(
            'ALTER TABLE `back_catalog_brand`
                ADD CONSTRAINT catalog_brand_catalog
                    FOREIGN KEY (`catalog_id`) REFERENCES `back_catalog` (`id`)
                    ON DELETE CASCADE,
                ADD CONSTRAINT catalog_brand_brand
                    FOREIGN KEY (`brand_id`) REFERENCES `back_brand` (`id`)
                    ON DELETE CASCADE'
        );
    }
}
