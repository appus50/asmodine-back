<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\AdminBundle\Model\Category;
use Asmodine\CommonBundle\DTO\CategoryDTO;
use Asmodine\CommonBundle\Exception\NotFoundEntityException;
use Asmodine\CommonBundle\Model\Morphoprofile\Gender;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;

/**
 * Class CategoryRepository.
 */
class CategoryRepository extends AbstractSynonymAssociationRepository
{
    /**
     * Insert new category.
     *
     * @param Category $category
     */
    public function insert(Category $category): void
    {
        $path = null;
        $parent_id = null;

        $sql = 'INSERT INTO `back_category_asmodine` '
            .'(`parent_id`, `name`, `slug`, `gender`, `path`, `depth`, `position`, `enabled`, `icon`) '
            .'VALUES (:parent, :catname, :slug, :gender, :path, :depth, :position, :enabled, :icon)';

        if ('/' == $category->getParentPath()) {
            $path = '/'.$category->getSlug();
        }
        if (is_null($path)) {
            $parent = $this->findOneByPath($category->getParentPath());
            $parent_id = $parent->id;
            $path = $parent->path.'/'.$category->getSlug();
        }

        $params = [
            'parent' => $parent_id,
            'catname' => $category->getName(),
            'slug' => $category->getSlug(),
            'gender' => $category->getGender(),
            'path' => $path,
            'depth' => substr_count($path, '/') - 1,
            'position' => $category->getPosition(),
            'enabled' => $category->isEnabled() ? 1 : 0,
            'icon' => $category->getIcon(),
        ];

        $this->execute($sql, $params);
    }

    /**
     * Find Category with its path.
     *
     * @param string $path
     *
     * @return CategoryDTO
     *
     * @throws NotFoundEntityException
     */
    public function findOneByPath(string $path): CategoryDTO
    {
        $sql = 'SELECT * FROM `back_category_asmodine` WHERE path = :path';

        $stmt = $this->execute($sql, ['path' => $path]);
        if ($row = $stmt->fetch()) {
            return new CategoryDTO($row);
        }
        throw new NotFoundEntityException('back_category_asmodine', 'path = '.$path);
    }

    /**
     * Find All Categories.
     *
     * @param array $orderBy
     *
     * @return array
     */
    public function findAll($orderBy = ['parent_id' => 'ASC', 'position' => 'ASC', 'id' => 'ASC']): array
    {
        $qb = new QueryBuilder($this->conn);
        $qb->select('c.*');
        $qb->from('back_category_asmodine', 'c');

        $addOrderBy = function ($order, $sort) use ($qb) {
            $qb->addOrderBy('c.'.$sort, $order);
        };
        array_walk($orderBy, $addOrderBy);

        /** @var Statement $statement */
        $statement = $qb->execute();
        $categories = [];
        while ($row = $statement->fetch()) {
            $categories[] = new CategoryDTO($row);
        }

        return $categories;
    }

    /**
     * Associate with synonyms.
     */
    public function runSynonymAssociation(): void
    {
        $tmpGenderTable = 'tmp_back_synonym_category_gender';
        $tmpCategoryTable = 'tmp_back_synonym_category_depth2';
        // Detection of categories not to be supported
        $this->execute(
            'UPDATE `'.$this->getSynonymAssociationTable().'` AS i SET `'.$this->getSynonymAssociationIdColumn().'` = ('
            .'  SELECT `external_id` FROM `back_utils_synonyms` AS s '
            ."      WHERE s.type ='category' "
            ."      AND s.external_id = (SELECT id FROM `back_category_asmodine` AS c WHERE c.depth = 0 AND c.gender = '".Gender::NOBODY."') "
            ."      AND LOWER(i.name) LIKE CONCAT('%', s.value, '%') LIMIT 1"
            .') WHERE i.'.$this->getSynonymAssociationIdColumn().' IS NULL'
        );

        // Detection of supported categories
        $this->dropTable($tmpGenderTable, true);
        $this->dropTable($tmpCategoryTable, true);

        // Search for gender matches
        $this->execute(
            "CREATE TEMPORARY TABLE `{$tmpGenderTable}` "
            .' (INDEX `priority_idx` (`priority`), INDEX `ids_idx` (`external_id`, `id`))'
            .' AS SELECT c.priority AS max_priority, c.* FROM ( '
            .'    SELECT s.priority, s.external_id, i.id '
            ."      FROM `{$this->getSynonymAssociationTable()}` AS i "
            ."      LEFT JOIN `back_utils_synonyms` AS s ON LOWER(i.name) LIKE CONCAT('%', s.value, '%') "
            .'      LEFT JOIN `back_category_asmodine` AS c_gender ON c_gender.id = s.external_id'
            ."      WHERE s.type = 'category' AND i.category_asmodine_id IS NULL AND c_gender.depth = 0 AND c_gender.gender != '".Gender::NOBODY."' "
            .'      GROUP BY s.priority, s.external_id, i.id'
            .'      ORDER BY s.priority DESC) AS c'
            .'    GROUP BY c.external_id, c.id'
        );
        $this->findBestMatch($tmpGenderTable);

        $this->execute(
            "CREATE TEMPORARY TABLE `{$tmpCategoryTable}` "
            .' (INDEX `priority_idx` (`priority`), INDEX `ids_idx` (`external_id`, `id`))'
            .' AS SELECT c.priority AS max_priority, c.* FROM ( '
            .'    SELECT s.priority, s.external_id, i.id '
            ."      FROM `{$this->getSynonymAssociationTable()}` AS i "
            ."      LEFT JOIN `back_utils_synonyms` AS s ON LOWER(i.name) LIKE CONCAT('%', s.value, '%') "
            .'      INNER JOIN `back_category_asmodine` AS c2 ON c2.id = s.external_id'
            .'      INNER JOIN `back_category_asmodine` AS c1 ON c2.parent_id = c1.id'
            ."      INNER JOIN `{$tmpGenderTable}` AS s_gender ON s_gender.external_id = c1.parent_id"
            ."      WHERE s.type = 'category' AND i.category_asmodine_id IS NULL AND c2.depth = 2 AND s_gender.id = i.id "
            .'      GROUP BY s.priority, s.external_id, i.id'
            .'      ORDER BY s.priority DESC) AS c'
            .'    GROUP BY c.external_id, c.id'
        );
        $this->findBestMatch($tmpCategoryTable);
        $this->updateSynonymAssociationTable($tmpCategoryTable);
        $this->dropTable($tmpGenderTable, true);
        $this->dropTable($tmpCategoryTable, true);
    }

    /**
     * Returns the name of the table to which to apply the synonyms.
     *
     * @return string
     */
    public function getSynonymAssociationTable(): string
    {
        return 'back_category_brand';
    }

    /**
     * Returns the name of the column to which to apply the synonyms.
     *
     * @return string
     */
    public function getSynonymAssociationIdColumn(): string
    {
        return 'category_asmodine_id';
    }
}
