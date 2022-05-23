<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20171108153634
 * Update Life Cycle of Product And Model
 * @package Application\Migrations
 */
class Version20171108153634 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `back_model` ADD `deleted_at` DATETIME NULL AFTER `updated_at`');
        $this->addSql('ALTER TABLE `back_model` DROP INDEX `enabled_idx`');
        $this->addSql('ALTER TABLE `back_model` DROP `enabled`');
        $this->addSql('ALTER TABLE `back_model` ADD `is_products_data` BOOLEAN NOT NULL DEFAULT FALSE AFTER `further_information`, ADD INDEX `products_data_idx` (`is_products_data`)');
        $this->addSql('ALTER TABLE `back_model` ADD `is_enabled_manual` BOOLEAN NOT NULL DEFAULT TRUE AFTER `is_products_data`, ADD INDEX `model_enable_manual_idx` (`is_enabled_manual`)');
        $this->addSql('ALTER TABLE `back_model` ADD `is_enabled_auto` BOOLEAN NOT NULL DEFAULT TRUE AFTER `is_enabled_manual`, ADD INDEX `model_enable_auto_idx` (`is_enabled_auto`)');
        $this->addSql('ALTER TABLE `back_model` ADD `is_updated` BOOLEAN NOT NULL DEFAULT TRUE AFTER `is_enabled_auto`, ADD INDEX `model_update_idx` (`is_updated`)');
        $this->addSql('ALTER TABLE `back_model` ADD `is_deleted` BOOLEAN NOT NULL DEFAULT FALSE AFTER `is_updated`, ADD INDEX `model_delete_idx` (`is_deleted`)');

        $this->addSql('ALTER TABLE `back_product` ADD `deleted_at` DATETIME NULL AFTER `updated_at`');
        $this->addSql('ALTER TABLE `back_product` DROP INDEX `updated_idx`');
        $this->addSql('ALTER TABLE `back_product` DROP INDEX `enabled_idx`');
        $this->addSql('ALTER TABLE `back_product` DROP `enabled`');
        $this->addSql('ALTER TABLE `back_product` ADD `is_enabled_manual` BOOLEAN NOT NULL DEFAULT TRUE AFTER `further_information`, ADD INDEX `product_enable_manual_idx` (`is_enabled_manual`)');
        $this->addSql('ALTER TABLE `back_product` ADD `is_enabled_auto` BOOLEAN NOT NULL DEFAULT TRUE AFTER `is_enabled_manual`, ADD INDEX `product_enable_auto_idx` (`is_enabled_auto`)');
        $this->addSql('ALTER TABLE `back_product` ADD `is_updated` BOOLEAN NOT NULL DEFAULT TRUE AFTER `is_enabled_auto`, ADD INDEX `product_update_idx` (`is_updated`)');
        $this->addSql('ALTER TABLE `back_product` ADD `is_deleted` BOOLEAN NOT NULL DEFAULT FALSE AFTER `is_updated`, ADD INDEX `product_delete_idx` (`is_deleted`)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `back_product` DROP INDEX `product_delete_idx`');
        $this->addSql('ALTER TABLE `back_product` DROP INDEX `product_update_idx`');
        $this->addSql('ALTER TABLE `back_product` DROP INDEX `product_enable_auto_idx`');
        $this->addSql('ALTER TABLE `back_product` DROP INDEX `product_enable_manual_idx`');
        $this->addSql('ALTER TABLE `back_model` DROP INDEX `model_delete_idx`');
        $this->addSql('ALTER TABLE `back_model` DROP INDEX `model_update_idx`');
        $this->addSql('ALTER TABLE `back_model` DROP INDEX `model_enable_auto_idx`');
        $this->addSql('ALTER TABLE `back_model` DROP INDEX `model_enable_manual_idx`');
        $this->addSql('ALTER TABLE `back_product` DROP `is_deleted`');
        $this->addSql('ALTER TABLE `back_product` DROP `is_updated`');
        $this->addSql('ALTER TABLE `back_product` DROP `is_enabled_auto`');
        $this->addSql('ALTER TABLE `back_product` DROP `is_enabled_manual`');
        $this->addSql('ALTER TABLE `back_model` DROP `is_deleted`');
        $this->addSql('ALTER TABLE `back_model` DROP `is_updated`');
        $this->addSql('ALTER TABLE `back_model` DROP `is_enabled_auto`');
        $this->addSql('ALTER TABLE `back_model` DROP `is_enabled_manual`');

        $this->addSql('ALTER TABLE `back_product` ADD `enabled` BOOLEAN NULL DEFAULT TRUE AFTER `updated_at`');
        $this->addSql('ALTER TABLE `back_model` ADD `enabled` BOOLEAN NULL DEFAULT TRUE AFTER `updated_at`');
        $this->addSql('ALTER TABLE `back_product` ADD INDEX `enabled_idx` (`enabled`)');
        $this->addSql('ALTER TABLE `back_model` ADD INDEX `enabled_idx` (`enabled`)');
        $this->addSql('ALTER TABLE `back_product` ADD INDEX `updated_idx` (`updated_at`)');

        $this->addSql('ALTER TABLE `back_model` DROP `deleted_at`');
        $this->addSql('ALTER TABLE `back_product` DROP `deleted_at`');
    }
}
