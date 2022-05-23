<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20170907200000
 * @package Application\Migrations
 */
class Version20170907200000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            "CREATE TABLE `advisor_user_product_score` (
                `user_id` INT UNSIGNED NOT NULL,
                `model_id` INT UNSIGNED NULL,
                `product_id` INT UNSIGNED NOT NULL,
                `note_color` SMALLINT NULL,
                `note_style` SMALLINT NULL,
                `note_size` DECIMAL(6,4) NULL,
                `note_size_max` SMALLINT NULL COMMENT 'Total number of measurements available',
                `updated_at` DATETIME NOT NULL,
                 
                 PRIMARY KEY (`user_id`, `product_id`), 
                 INDEX `user_idx` (`user_id`), 
                 INDEX `user_model` (`user_id`, `model_id`),
                 INDEX `model_idx` (`model_id`), 
                 INDEX `updated_idx` (`updated_at`)
             ) 
             ENGINE = MYISAM"
        );

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE IF EXISTS `advisor_user_product_score`');
    }
}
