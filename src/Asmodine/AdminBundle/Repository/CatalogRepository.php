<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\AdminBundle\DTO\CatalogDTO;
use Asmodine\AdminBundle\Model\Catalog;
use Asmodine\CommonBundle\Exception\NotFoundEntityException;
use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;

/**
 * Class CatalogRepository.
 */
class CatalogRepository extends AbstractAsmodineRepository
{
    /**
     * Insert new catalog.
     *
     * @param Catalog $catalog
     * @param string  $configuration
     *
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function insert(Catalog $catalog, string $configuration): void
    {
        $sql = 'INSERT INTO `back_catalog` '
            .'(`name`, `origin`, `slug`, `configuration`, `enabled`) '
            .'VALUES (:catname, :origin, :slug, :configuration, :enabled)';
        $params = [
            'catname' => $catalog->getName(),
            'origin' => $catalog->getOrigin(),
            'slug' => $catalog->getSlug(),
            'configuration' => $configuration,
            'enabled' => $catalog->isEnabled() ? 1 : 0,
        ];

        $this->execute($sql, $params);
    }

    /**
     * Find Catalog with its id.
     *
     * @param int $id
     *
     * @return CatalogDTO
     *
     * @throws NotFoundEntityException
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function findById(int $id): CatalogDTO
    {
        $sql = 'SELECT * FROM `back_catalog` WHERE id = :id';
        $stmt = $this->execute($sql, ['id' => $id]);
        if ($row = $stmt->fetch()) {
            return new CatalogDTO($row);
        }
        throw new NotFoundEntityException('back_catalog', 'slug = '.$id);
    }

    /**
     * Find Catalog with its slug.
     *
     * @param string $slug
     *
     * @return CatalogDTO
     *
     * @throws NotFoundEntityException
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function findOneBySlug(string $slug): CatalogDTO
    {
        $sql = 'SELECT * FROM `back_catalog` WHERE slug = :slug';
        $stmt = $this->execute($sql, ['slug' => $slug]);
        if ($row = $stmt->fetch()) {
            return new CatalogDTO($row);
        }
        throw new NotFoundEntityException('back_catalog', 'slug = '.$slug);
    }

    /**
     * Get all catalogs.
     *
     * @param array $orderBy
     *
     * @return CatalogDTO[]
     */
    public function findAll($orderBy = ['slug' => 'ASC'], $enableOnly= true): array
    {
        $qb = new QueryBuilder($this->conn);
        $qb->select('c.*');
        $qb->from('back_catalog', 'c');
        if($enableOnly) {
            $qb->where('c.enabled = :enabled')->setParameter('enabled', true);
        }
        $addOrderBy = function ($order, $sort) use ($qb) {
            $qb->addOrderBy('c.'.$sort, $order);
        };
        array_walk($orderBy, $addOrderBy);

        /** @var Statement $statement */
        $statement = $qb->execute();
        $catalogs = [];
        while ($row = $statement->fetch()) {
            $catalogs[] = new CatalogDTO($row);
        }

        return $catalogs;
    }

    /**
     * Update imported_at.
     *
     * @param Catalog $catalog
     *
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function setImported(Catalog $catalog): void
    {
        $sql = 'UPDATE `back_catalog` SET imported_at = NOW() WHERE `slug` = :slug';

        $params = ['slug' => $catalog->getSlug()];

        $this->execute($sql, $params);
    }
}
