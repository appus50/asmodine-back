<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20171114182239
 * Add colorFilter table
 * @package Application\Migrations
 */
class Version20171114182239 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'CREATE TABLE `back_color_filter_asmodine` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `slug` VARCHAR(64) NOT NULL,
                `name` VARCHAR(64) NOT NULL,
                
                PRIMARY KEY (`id`),
                INDEX `slug_idx` (`slug`)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        $this->addSql('ALTER TABLE `back_color_asmodine` ADD `color_filter_id` INT UNSIGNED NOT NULL AFTER `id`');
        $this->addSql('ALTER TABLE `back_color_asmodine` ADD INDEX `color_filter_idx` (`color_filter_id`)');
        $this->addSql(
            'ALTER TABLE `back_color_asmodine`
                ADD CONSTRAINT fk_color_asmodine_color_filter_asmodine
                FOREIGN KEY (`color_filter_id`) REFERENCES `back_color_filter_asmodine` (`id`)
                ON DELETE RESTRICT'
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `back_color_asmodine` DROP INDEX fk_color_asmodine_color_filter_asmodine');
        $this->addSql('ALTER TABLE `back_color_asmodine` DROP `color_filter_id`');
        $this->addSql('DROP TABLE `back_color_filter_asmodine`');
    }
}
