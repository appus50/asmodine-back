<?php

namespace Asmodine\SizeAdvisorBundle\Repository;

use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;
use Asmodine\CommonBundle\Repository\ElasticsearchPushInterface;
use Doctrine\DBAL\Statement;

/**
 * Class UserProductScoreRepository.
 */
class UserProductScoreRepository extends AbstractAsmodineRepository implements ElasticsearchPushInterface
{
    /**
     * @param int|null $userId
     *
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function createNewAssociations(?int $userId)
    {
        $where = !is_null($userId) ? ' WHERE c.user_id = '.$userId.' ' : '';
        $this->execute(
            "INSERT IGNORE INTO `advisor_user_product_score` (`user_id`,`model_id`, `product_id`, `note_color`, `note_style`, `note_size`, `note_size_max`, `updated_at`)
            SELECT c.user_id, t.model_id, p.id, 0, 0, 0, 0, NOW()
            FROM back_product AS p
            INNER JOIN advisor_product_tree AS t on p.id = t.product_id
            INNER JOIN cim_customer AS c ON t.gender = c.gender
            $where
           "
        );
    }

    /**
     * Update User Color Note.
     *
     * @param int|null $userId
     *
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function calculateColorNote(?int $userId): void
    {
        $where = !is_null($userId) ? ' WHERE c.user_id = '.$userId.' ' : '';
        $this->execute(
            "UPDATE `advisor_user_product_score` AS a 
              INNER JOIN cim_customer AS c ON c.user_id = a.user_id
              INNER JOIN advisor_note_color AS n ON n.morphotype = c.morphotype AND n.product_id = a.product_id
            SET `note_color` = n.note, a.updated_at = NOW()
            $where
           "
        );
    }

    /**
     * Update User Style Note.
     */
    public function calculateStyleNote(?int $userId): void
    {
        $where = !is_null($userId) ? ' WHERE c.user_id = '.$userId.' ' : '';
        $this->execute(
            "UPDATE `advisor_user_product_score` AS a 
              INNER JOIN cim_customer AS c ON c.user_id = a.user_id
              INNER JOIN advisor_note_style AS n ON n.size = c.size AND n.morphoprofile = c.morphoprofile AND n.morpho_weight = c.morpho_weight AND n.product_id = a.product_id
            SET `note_style` = n.note, a.updated_at = NOW()
            $where
           "
        );
    }

    /**
     * Update User Size Guide Note.
     *
     * @param int|null $userId
     *
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function calculateSizeGuide(?int $userId): void
    {
        $andWhere = !is_null($userId) ? ' AND c.user_id = '.$userId : '';

        $this->dropTable('tmp_advisor_size_guide_matches', true);

        $this->execute(
            'CREATE TEMPORARY TABLE `tmp_advisor_size_guide_matches`
              (INDEX `product_idx` (`product_id`), INDEX `matches_idx` (`user_id`, `product_id`))
              SELECT
                  c.user_id,
                  s.product_id,
                  s.body_part,
                  IF(s.min <= c.value AND c.value <= s.max, 1,
                      IF(c.value < s.min,
                          IF(c.value+70 <= s.min, -9, ( ((0.45-1)/70) * (s.min - c.value) + 1) ),
                          IF(s.max+20 <= c.value, -9, ( ((0.45-1)/20) * (c.value - s.max) + 1) )
                      )
                  ) AS score_body_part
              FROM `cim_customer_measure` AS c
              INNER JOIN `advisor_size_guide` AS s ON c.body_part = s.body_part
              INNER JOIN `cim_customer` AS cc ON c.user_id = cc.user_id
              INNER JOIN `advisor_product_tree` AS t ON s.product_id = t.product_id
              WHERE t.gender = cc.gender '.$andWhere
        );

        $this->execute(
            'UPDATE `advisor_user_product_score` AS a 
              INNER JOIN (
                SELECT m.user_id, m.product_id, IF(SUM(m.score_body_part)>0,SUM(m.score_body_part),0)  AS note, p.measure_count AS note_size_max
                FROM tmp_advisor_size_guide_matches AS m
                INNER JOIN advisor_size_guide_nb_points AS p ON m.product_id = p.product_id 
                GROUP BY m.user_id, m.product_id
            ) AS s ON s.user_id = a.user_id AND s.product_id = a.product_id
            SET a.note_size = s.note, a.note_size_max = s.note_size_max, a.updated_at = NOW()
           '
        );

        $this->dropTable('tmp_advisor_size_guide_matches', true);
    }

    /**
     * @see ElasticsearchPushInterface::getBulkDatas
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
        $where = '';
        if (isset($params['user_id'])) {
            $where = 'WHERE s.user_id = '.intval($params['user_id']);
        }

        return $this->execute(
            "
          SELECT
            s.model_id AS model_id,
            s.product_id AS product_id,
            s.user_id AS user_id,
            
            s.note_color AS note_color,
            (3*(s.note_size/s.note_size_max)) AS note_size,
            s.note_style AS note_style,
            
            (3*(s.note_size/s.note_size_max) * 5 + s.note_style * 2 + s.note_color * 1) / (5+2+1) AS note_advice,
            (3*(s.note_size/s.note_size_max) * 120 + s.note_style * 60 + s.note_color) AS note_ranking
     
            FROM `advisor_user_product_score` AS s
            
          $where
          LIMIT $limit OFFSET $offset"
        );
    }

    /**
     * @see AbstractAsmodineRepository::count()
     *
     * @return int
     */
    public function getNbRows(): int
    {
        return $this->count('advisor_user_product_score', true, ['user_id', 'product_id']);
    }
}
