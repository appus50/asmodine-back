<?php

namespace Application\Migrations;

use Asmodine\CommonBundle\Model\Morphoprofile\Gender;
use Asmodine\CommonBundle\Model\Morphoprofile\Morphoprofile;
use Asmodine\CommonBundle\Model\Morphoprofile\Size;
use Asmodine\CommonBundle\Model\Morphoprofile\Weight;
use Asmodine\CommonBundle\Model\Morphotype\Eye;
use Asmodine\CommonBundle\Model\Morphotype\Hair;
use Asmodine\CommonBundle\Model\Morphotype\Morphotype;
use Asmodine\CommonBundle\Model\Morphotype\Skin;
use Asmodine\CommonBundle\Model\Profile\Body;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20170907000000
 * @package Application\Migrations
 */
class Version20170907000000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            "CREATE TABLE cim_customer (
                `user_id` INT UNSIGNED NOT NULL COMMENT 'User id in DB Front',
                `physical_profile_id` INT UNSIGNED NOT NULL COMMENT 'PhysicalProfile id current in DB Front',
                `gender`  ENUM('".implode("','", Gender::getSlugs())."') NULL,
                `height` SMALLINT NULL,
                `weight` SMALLINT NULL,
                `skin` ENUM('".implode("','", Skin::getSlugs())."') NULL,
                `hair` ENUM('".implode("','", Hair::getSlugs())."') NULL,
                `eyes` ENUM('".implode("','", Eye::getSlugs())."') NULL,
                `size` ENUM('".implode("','", Size::getSlugs())."') NULL,
                `morphotype` ENUM('".implode("','", Morphotype::getSlugs())."') NULL,
                `morphoprofile` ENUM('".implode("','", Morphoprofile::getSlugs())."') NULL,
                `morpho_weight` ENUM('".implode("','", Weight::getSlugs())."') NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                 
                 UNIQUE `user_idx` (`user_id`),
                 INDEX `morphotype_idx` (`morphotype`),
                 INDEX `morpho_idx` (`size`,`morphoprofile`,`morpho_weight`),
                 INDEX `updated_idx` (`updated_at`)
             ) 
             ENGINE = MYISAM
             CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE cim_customer_measure (
                `user_id`  INT UNSIGNED NOT NULL,
                `body_part` VARCHAR(31) NOT NULL,
                `value` SMALLINT,
                PRIMARY KEY (`user_id`, `body_part`),
                INDEX `body_idx` (`body_part`)
                )"
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE IF EXISTS `cim_customer`');
        $this->addSql('DROP TABLE IF EXISTS `cim_customer_measure`');
    }
}
