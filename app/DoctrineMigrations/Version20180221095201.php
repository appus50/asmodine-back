<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20180221095201
 * @package Application\Migrations
 */
class Version20180221095201 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('CREATE VIEW webmarketing_user_color_note AS 
            SELECT u.user_id AS user_id, u.physical_profile_id as user_physical_profile_id, u.gender as gender, u.height as height, u.size as size, u.morphotype as morphotype, u.morphoprofile as morphoprofile, u.morpho_weight as morphoweight, n.green, n.yellow, n.red
            FROM 
                (
               SELECT user_id, SUM(green) AS green, SUM(yellow) AS yellow, SUM(red) AS red
               FROM (
                   SELECT user_id, model_id, note, IF(note >= 2.25,1,0) AS green, IF(note < 2.25 AND note >= 1.87,1,0) AS yellow, IF(note < 1.87,1,0) AS red
                   FROM (
                        SELECT user_id, model_id, MAX(note) AS note
                        FROM (
                            SELECT user_id, model_id, ((3*(note_size/note_size_max) * 5 + note_style * 2 + note_color * 1) / (5+2+1)) AS note
                            FROM advisor_user_product_score
                        ) AS tmp_score_model
                        WHERE note IS NOT NULL
                        GROUP BY user_id, model_id
                   ) AS note_model
                ) AS sum_note_model
                GROUP BY user_id
            ) AS n
            LEFT JOIN cim_customer as u ON n.user_id = u.user_id');
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DROP VIEW webmarketing_user_color_note");
    }
}




