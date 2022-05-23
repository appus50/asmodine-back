<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\AdminBundle\Model\Brand;
use Asmodine\CommonBundle\DTO\BrandDTO;
use Asmodine\CommonBundle\Exception\NotFoundEntityException;
use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;

/**
 * Class BrandRepository.
 */
class BrandRepository extends AbstractAsmodineRepository
{
    /**
     * Insert new brand.
     *
     * @param Brand $brand
     *
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function insert(Brand $brand): void
    {
        $sql = 'INSERT INTO `back_brand` '
            .'(`name`, `slug`, `description`, `logo`,`iframe`, `enabled`, `created_at`, `updated_at`) '
            .'VALUES (:brandname, :slug, :description, :logo, :iframe, :enabled, NOW(), NOW())';
        $params = [
            'brandname' => $brand->getName(),
            'slug' => $brand->getSlug(),
            'description' => $brand->getDescription(),
            'logo' => $brand->getLogo(),
            'iframe' => $brand->isIframe() ? 1 : 0,
            'enabled' => $brand->isEnabled() ? 1 : 0,
        ];

        $this->execute($sql, $params);
    }

    /**
     * Find Catalog with its slug.
     *
     * @param string $slug
     *
     * @return BrandDTO
     *
     * @throws NotFoundEntityException
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function findOneBySlug(string $slug): BrandDTO
    {
        $sql = 'SELECT * FROM `back_brand` WHERE slug = :slug';
        $stmt = $this->execute($sql, ['slug' => $slug]);
        if ($row = $stmt->fetch()) {
            return new BrandDTO($row);
        }
        throw new NotFoundEntityException('back_brand', 'slug = '.$slug);
    }

    /**
     * Get all brands.
     *
     * @param array $orderBy
     *
     * @return BrandDTO[]
     */
    public function findAll($orderBy = ['slug' => 'ASC'], $enableOnly = true): array
    {
        $qb = new QueryBuilder($this->conn);
        $qb->select('b.*');
        $qb->from('back_brand', 'b');
        if($enableOnly) {
            $qb->where('b.enabled = :enabled')->setParameter('enabled', true);
        }
        $addOrderBy = function ($order, $sort) use ($qb) {
            $qb->addOrderBy('b.'.$sort, $order);
        };
        array_walk($orderBy, $addOrderBy);

        /** @var Statement $statement */
        $statement = $qb->execute();
        $brands = [];
        while ($row = $statement->fetch()) {
            $brands[] = new BrandDTO($row);
        }

        return $brands;
    }
}
