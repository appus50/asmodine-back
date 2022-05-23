<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Version20171215095614
 * Views
 * @package Application\Migrations
 */
class Version20171215095614 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema)
    {$this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE VIEW webmarketing_error_category AS SELECT cb.id, b.name AS marque, cb.name as categorie_non_associee, m.name AS modele_nom, p.name AS produit_nom, m.url AS model_url, p.url AS produit_url FROM back_category_brand AS cb JOIN back_brand AS b ON cb.brand_id = b.id JOIN back_model AS m ON cb.id = m.category_id JOIN back_product AS p ON p.model_id = m.id WHERE cb.category_asmodine_id IS NULL');
        $this->addSql('CREATE VIEW webmarketing_error_color AS SELECT c.id, c.name AS couleur_non_associee, b.name AS marque, m.name AS modele_nom, p.name AS produit_nom, m.url AS model_url, p.url AS produit_url FROM back_color_brand AS c JOIN back_product AS p ON p.color_id = c.id JOIN back_model AS m ON p.model_id = m.id JOIN back_brand AS b ON m.brand_id = b.id WHERE c.color_asmodine_id IS NULL');
        $this->addSql('CREATE VIEW webmarketing_error_style AS SELECT b.name AS brand_name, ca.path AS catgeory_path, m.name AS model_name, m.description, COALESCE(m.url, p.url) AS url FROM  back_model AS m JOIN back_product AS p ON p.model_id = m.id JOIN back_brand AS b on p.brand_id = b.id LEFT JOIN back_category_brand AS cb ON m.category_id = cb.id LEFT JOIN back_category_asmodine AS ca ON cb.category_asmodine_id = ca.id WHERE m.style_id IS NULL GROUP BY m.id');
        $this->addSql('CREATE VIEW webmarketing_sizeguide AS SELECT sb.id, b.name AS brand_name, ca.path AS category_path, sb.name AS brand_size_name,  count(p.id) AS nb_products,  sgb.arm, sgm.arm_min, sgm.arm_med, sgm.arm_max, sgb.bra, sgm.bra_min, sgm.bra_med, sgm.bra_max, sgb.chest, sgm.chest_min, sgm.chest_med, sgm.chest_max, sgb.calf, sgm.calf_min, sgm.calf_med, sgm.calf_max, sgb.finger, sgm.finger_min, sgm.finger_med, sgm.finger_max, sgb.foot_length, sgm.foot_length_min, sgm.foot_length_med, sgm.foot_length_max, sgb.foot_width, sgm.foot_width_min, sgm.foot_width_med, sgm.foot_width_max, sgb.hand_length, sgm.hand_length_min, sgm.hand_length_med, sgm.hand_length_max, sgm.hand_width_min, sgm.hand_width_med, sgm.hand_width_max, sgb.hollow_to_floor, sgm.hollow_to_floor_min, sgm.hollow_to_floor_med, sgm.hollow_to_floor_max, sgb.head, sgm.head_min, sgm.head_med, sgm.head_max, sgb.hip, sgm.hip_min, sgm.hip_med, sgm.hip_max, sgb.inside_leg, sgm.inside_leg_min, sgm.inside_leg_med, sgm.inside_leg_max, sgb.neck, sgm.neck_min, sgm.neck_med, sgm.neck_max, sgb.shoulder, sgm.shoulder_min, sgm.shoulder_med, sgm.shoulder_max, sgb.shoulder_to_hip, sgm.shoulder_to_hip_min, sgm.shoulder_to_hip_med, sgm.shoulder_to_hip_max, sgb.thigh, sgm.thigh_min, sgm.thigh_med, sgm.thigh_max, sgb.waist, sgm.waist_min, sgm.waist_med, sgm.waist_max, sgb.wrist, sgm.wrist_min, sgm.wrist_med, sgm.wrist_max FROM back_size_brand AS sb INNER JOIN back_brand AS b ON sb.brand_id = b.id INNER JOIN back_product AS p ON p.size_id = sb.id INNER JOIN back_model AS m ON p.model_id = m.id INNER JOIN back_category_brand AS cb ON m.category_id = cb.id INNER JOIN back_category_asmodine AS ca ON cb.category_asmodine_id = ca.id LEFT JOIN back_size_guide_measure AS sgm ON sgm.brand_size_id = sb.id AND sgm.type_id = ca.id AND sgm.type = \'category\' LEFT JOIN back_size_guide_body_part AS sgb ON sgb.type_id = ca.id AND sgb.type = \'category\' GROUP BY sb.id, ca.id ORDER BY brand_name, brand_size_name');
        $this->addSql('CREATE VIEW webmarketing_sizeguide_import AS SELECT sgm.id, b.name AS brand_name, ca.path AS category_path, sb.name AS brand_size_name, sgm.arm_min, sgm.arm_med, sgm.arm_max, sgm.bra_min, sgm.bra_med, sgm.bra_max, sgm.chest_min, sgm.chest_med, sgm.chest_max, sgm.calf_min, sgm.calf_med, sgm.calf_max, sgm.finger_min, sgm.finger_med, sgm.finger_max, sgm.foot_length_min, sgm.foot_length_med, sgm.foot_length_max, sgm.foot_width_min, sgm.foot_width_med, sgm.foot_width_max, sgm.hand_length_min, sgm.hand_length_med, sgm.hand_length_max, sgm.hand_width_min, sgm.hand_width_med, sgm.hand_width_max, sgm.hollow_to_floor_min, sgm.hollow_to_floor_med, sgm.hollow_to_floor_max, sgm.head_min, sgm.head_med, sgm.head_max, sgm.hip_min, sgm.hip_med, sgm.hip_max, sgm.inside_leg_min, sgm.inside_leg_med, sgm.inside_leg_max, sgm.neck_min, sgm.neck_med, sgm.neck_max, sgm.shoulder_min, sgm.shoulder_med, sgm.shoulder_max, sgm.shoulder_to_hip_min, sgm.shoulder_to_hip_med, sgm.shoulder_to_hip_max, sgm.thigh_min, sgm.thigh_med, sgm.thigh_max, sgm.waist_min, sgm.waist_med, sgm.waist_max, sgm.wrist_min, sgm.wrist_med, sgm.wrist_max FROM back_size_guide_measure AS sgm INNER JOIN back_size_brand AS sb ON sgm.brand_size_id = sb.id INNER JOIN back_brand AS b ON sb.brand_id = b.id INNER JOIN back_category_asmodine AS ca ON sgm.type_id = ca.id AND sgm.type = \'category\' ORDER BY brand_name, category_path, brand_size_name');


        $this->addSql('CREATE VIEW study_category AS SELECT cb.id AS id, b.name AS brand, cb.name AS brand_category, ca.path AS asmodine_category_path, ca.name AS asmodine_category_name, COUNT(m.id) AS nb_model FROM back_category_brand AS cb JOIN back_brand as b ON cb.brand_id = b.id LEFT JOIN back_category_asmodine AS ca ON cb.category_asmodine_id = ca.id LEFT JOIN back_model AS m ON m.category_id = cb.id GROUP BY m.category_id');
        $this->addSql('CREATE VIEW study_color AS SELECT cb.id AS id, b.name AS brand, cb.name AS brand_color, ca.name AS asmodine_color_name, f.name AS asmodine_color_filter_name, COUNT(p.id) AS nb_product FROM back_color_brand AS cb JOIN back_brand as b ON cb.brand_id = b.id LEFT JOIN back_color_asmodine AS ca ON cb.color_asmodine_id = ca.id LEFT JOIN back_product AS p ON p.color_id = cb.id LEFT JOIN back_color_filter_asmodine AS f ON ca.color_filter_id = f.id GROUP BY p.color_id');
        $this->addSql('CREATE VIEW study_size AS SELECT sb.id AS id, b.name AS brand, sb.name AS brand_size, COUNT(p.id) AS nb_product FROM back_size_brand AS sb JOIN back_brand as b ON sb.brand_id = b.id LEFT JOIN back_product AS p ON p.size_id = sb.id GROUP BY p.size_id');
        $this->addSql('CREATE VIEW category_nb_model AS SELECT c.path AS category, COUNT(m.id) AS nb_model FROM back_category_asmodine AS c LEFT JOIN back_category_brand AS cb ON cb.category_asmodine_id = c.id LEFT JOIN back_model as m ON m.category_id = cb.id GROUP BY c.id');

       // $this->addSql("CREATE USER 'webmarketing'@'%' IDENTIFIED VIA mysql_native_password USING '".$this->container->getParameter('database_webmarketing_password')."'");
       // $this->addSql("GRANT SELECT, CREATE VIEW, SHOW VIEW ON *.* TO 'webmarketing'@'%' REQUIRE NONE WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;");
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("DROP VIEW webmarketing_error_category");
        $this->addSql("DROP VIEW webmarketing_error_color");
        $this->addSql("DROP VIEW webmarketing_error_style");
        $this->addSql("DROP VIEW webmarketing_sizeguide");
        $this->addSql("DROP VIEW webmarketing_sizeguide_import");

        $this->addSql("DROP VIEW study_category");
        $this->addSql("DROP VIEW study_color");
        $this->addSql("DROP VIEW study_size");

        // $this->addSql("DROP USER 'webmarketing'@'%'");
    }

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
