<?php

namespace Asmodine\AdminBundle\Repository;

use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;
use Doctrine\DBAL\Statement;

/**
 * Class CatalogRepository.
 */
class CatalogImportRepository extends AbstractAsmodineRepository
{
    /**
     * Create temporary table.
     *
     * @param string $tableName
     * @param array  $columns
     *
     * @return Statement
     *
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function createTable(string $tableName, array $columns): Statement
    {
        $this->dropTable($tableName);

        $adjustTypeFunction = function ($name) {
            $type = 'VARCHAR(256)';
            if (in_array(strtolower($name), ['ean', 'ean13', 'upc', 'isbn', 'aisn'])) {
                $type = 'VARCHAR(16)';
            }
            if (in_array(strtolower($name), ['sku'])) {
                $type = 'VARCHAR(40)';
            }
            if (false !== strpos(strtolower($name), 'description')) {
                $type = 'TEXT';
            }
            if (in_array(strtolower($name), ['fields'])) {
                $type = 'TEXT';
            }
            if (false !== strpos(strtolower($name), 'link')) {
                $type = 'VARCHAR(512)';
            }
            if (0 === strcmp($name, 'UPC')) {
                $name .= 'UPC2';
            }

            return "`$name` ".$type.' NULL';
        };

        $fields = array_map($adjustTypeFunction, $columns);
        $sql = "CREATE TEMPORARY TABLE `$tableName` (".implode(', ', $fields).') CHARACTER SET UTF8 ENGINE=InnoDB';

        return $this->execute($sql);
    }

    /**
     * Create Format Table.
     *
     * @param string $tableName
     * @param array  $columns
     *
     * @return Statement
     *
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function createFormatTable(string $tableName, array $columns): Statement
    {
        $this->dropTable($tableName);
        $sql = "CREATE TABLE `$tableName` (".implode(', ', $columns).') CHARACTER SET UTF8 ENGINE=InnoDB';

        return $this->execute($sql);
    }

    /**
     * Copy Datas from catalog table to format table.
     *
     * @param string $formatTableName
     * @param string $catalogTableName
     * @param array  $tableColumns
     * @param array  $formatSelectColumns
     *
     * @return Statement
     *
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function insertDatasInFormatTable(string $formatTableName, string $catalogTableName, array $tableColumns, array $formatSelectColumns): Statement
    {
        $sql = "INSERT IGNORE INTO `$formatTableName` (".implode(', ', $tableColumns).') SELECT ';
        for ($i = 0; $i < count($tableColumns); ++$i) {
            if ($i > 0) {
                $sql .= ', ';
            }
            if (isset($formatSelectColumns[$tableColumns[$i]])) {
                $sql .= $formatSelectColumns[$tableColumns[$i]];
                continue;
            }
            $sql .= 'NULL';
        }
        $sql .= " FROM `$catalogTableName`";

        return $this->execute($sql);
    }

    /**
     * Remove entry with null value in $notNullColumns.
     *
     * @param string $formatTableName
     * @param array  $notNullColumns
     *
     * @return Statement|null
     *
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function removeNullValueIn(string $formatTableName, array $notNullColumns): ?Statement
    {
        if (0 == count($notNullColumns)) {
            return null;
        }
        $sql = "DELETE FROM `$formatTableName` WHERE `".implode('` IS NULL OR `', $notNullColumns).'` IS NULL';

        return $this->execute($sql);
    }

    /**
     * Drop non format table.
     *
     * @param string $tableName
     * @param bool   $temporary
     *
     * @return Statement
     */
    public function dropTable(string $tableName, bool $temporary = false): Statement
    {
        return parent::dropTable($tableName, $temporary);
    }

    /**
     * Load CSV in DB.
     *
     * @param string $tableName
     * @param string $csvPath
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     *
     * @return Statement
     *
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function loadCSV(string $tableName, string $csvPath, string $delimiter, string $enclosure, string $escape): Statement
    {
        $delimiter = substr($delimiter, 0, 2);
        $enclosure = substr($enclosure, 0, 2);
        $escape = substr($escape, 0, 2);

        $sql = "LOAD DATA LOCAL INFILE '{$csvPath}' INTO TABLE `{$tableName}` CHARACTER SET utf8 "
            ."FIELDS TERMINATED BY '$delimiter' ENCLOSED BY '\\$enclosure' ESCAPED BY '\\$escape' IGNORE 1 LINES";

        return $this->execute($sql);
    }

    /**
     * Load XML in DB.
     *
     * @param string $tableName
     * @param string $xmlPath
     * @param string $lineTag
     * @param array  $rows
     *
     * @return Statement
     *
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function loadXML(string $tableName, string $xmlPath, string $lineTag, array $rows): Statement
    {
        /*  $sql = "LOAD XML LOCAL INFILE '{$xmlPath}' INTO TABLE `{$tableName}` CHARACTER SET utf8 "
              .'ROWS IDENTIFIED BY :line_tag';

          return $this->execute($sql, ['line_tag' => '<'.$lineTag.'>']);*/

        $sql = "LOAD DATA LOCAL INFILE '{$xmlPath}' INTO TABLE `{$tableName}` CHARACTER SET utf8 ";
        $sql .= 'LINES STARTING BY :tag_begin TERMINATED BY :tag_end (@tmp) SET ';
        $sql .= implode(', ', array_map(function ($col) {
            return $col." = ExtractValue(@tmp, '//".$col."')";
        }, $rows));

        return $this->execute($sql, ['tag_begin' => '<'.$lineTag.'>', 'tag_end' => '</'.$lineTag.'>']);
    }

    /**
     * Count Nb Rows.
     *
     * @param string $tableName
     *
     * @return int
     */
    public function countRows(string $tableName): int
    {
        return $this->count($tableName, false);
    }
}
