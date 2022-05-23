<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;
use Asmodine\CommonBundle\Repository\ElasticsearchPushInterface;
use Doctrine\DBAL\Statement;

/**
 * Class ImageRepository.
 */
class ImageRepository extends AbstractAsmodineRepository implements ElasticsearchPushInterface
{
    /**
     * Return Select of datas to bulk.
     *
     * @param array $params
     * @param int   $limit
     * @param int   $offset
     *
     * @return Statement
     */
    public function getBulkDatas(array $params, int $offset, int $limit): Statement
    {
        return $this->execute("
          SELECT id, type, external_id, CONCAT(type, '_', external_id) AS search_id , IF(download=true, local_link, initial_link) AS link, position, enabled
          FROM `back_image` AS i
          LIMIT $limit OFFSET $offset");
    }
}
