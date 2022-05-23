<?php

namespace Asmodine\AdminBundle\Command;

use Asmodine\AdminBundle\DTO\CatalogBrandDTO;
use Asmodine\AdminBundle\DTO\CatalogDTO;
use Asmodine\AdminBundle\Model\Brand;
use Asmodine\AdminBundle\Model\Catalog;
use Asmodine\AdminBundle\Model\CatalogBrand;
use Asmodine\AdminBundle\Model\Category;
use Asmodine\AdminBundle\Repository\BrandRepository;
use Asmodine\AdminBundle\Repository\CatalogBrandRepository;
use Asmodine\AdminBundle\Repository\CatalogRepository;
use Asmodine\AdminBundle\Service\AssociateService;
use Asmodine\AdminBundle\Service\ImportCatalogService;
use Asmodine\CommonBundle\Command\AbstractAsmodineCommand;
use Asmodine\CommonBundle\DTO\BrandDTO;
use Asmodine\CommonBundle\DTO\CategoryDTO;
use Asmodine\CommonBundle\Util\FileUtils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class AsmodineAdminExportSQLCommand.
 */
class AsmodineAdminExportSQLCommand extends AbstractAsmodineCommand
{

    /**
     * @see Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('asmodine:admin:export:sql')
            ->setDescription('Generates SQL file for V1');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Asmodine\CommonBundle\Exception\FileException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sql = [];


        /** @var BrandRepository $brandsRepo */
        $brandsRepo = $this->getContainer()->get('asmodine.admin.repository.brand');
        $brands = $brandsRepo->findAll(['slug' => 'ASC'], false);
        $sql[] = "TRUNCATE TABLE  `pim_brand`";
        /** @var BrandDTO $brand */
        foreach ($brands as $brand) {
            $sql[] = sprintf(
                'INSERT INTO `pim_brand` (`id`, `name`, `slug`, `description`, `logo`, `iframe`, `is_activated_manually`, `is_activated_automatically`, `created_at`, `updated_at`, `activated_manually_at`) VALUES (%s, "%s", "%s", "%s", "%s", %s, TRUE, TRUE, NOW(), NOW(), NOW())',
                $brand->id, $brand->name, $brand->slug, $brand->description, $brand->logo, $brand->iframe ? 'TRUE' : 'FALSE'
            );
        }


        $sql[] = '';
        $sql[] = '';
        $sql[] = '';
        $sql[] = "TRUNCATE TABLE  `pim_category`";
        /** @var CatalogRepository $catsRepo */
        $catsRepo = $this->getContainer()->get('asmodine.admin.repository.category');
        $cats = $catsRepo->findAll(['depth' => 'ASC']);
        $completeSlugs = [];
        /** @var CategoryDTO $cat */
        foreach ($cats as $cat) {
            $completeSlugs[$cat->id] = is_null($cat->parentId) ? $cat->slug : $completeSlugs[$cat->parentId].'/'.$cat->slug;
            $sql[] = sprintf(
                'INSERT INTO `pim_category` (`id`, `parent_id`, `name`, `slug`, `complete_slug`,  `description`, `icon`, `position`, `is_activated_manually`, `is_activated_automatically`, `created_at`, `updated_at`, `activated_manually_at`) VALUES (%s, %s, "%s", "%s", "%s", "%s","%s","%s", TRUE, TRUE, NOW(), NOW(), NOW())',
                $cat->id, is_null($cat->parentId) ? "NULL" : $cat->parentId, $cat->name, $cat->slug, $completeSlugs[$cat->id], $cat->description, $cat->icon, $cat->position, 'FALSE', "TRUE", "TRUE"
            );
        }


        $sql[] = '';
        $sql[] = '';
        $sql[] = '';
        $sql[] = "TRUNCATE TABLE  `pim_catalog`";
        $sql[] = "TRUNCATE TABLE  `pim_catalog_action`";
        $sql[] = "TRUNCATE TABLE  `pim_catalog_simple_filter`";
        $sql[] = "TRUNCATE TABLE  `pim_catalog_columns`";
        $sql[] = "TRUNCATE TABLE  `pim_import_catalog_brand`";
        $sql[] = "TRUNCATE TABLE  `pim_import_catalog_brand_action`";
        $sql[] = "TRUNCATE TABLE  `pim_import_catalog_brand_simple_filter`";
        /** @var CatalogRepository $catalogRepo */
        $catalogRepo = $this->getContainer()->get('asmodine.admin.repository.catalog');
        /** @var ImportCatalogService $catalogServiceImport */
        $catalogServiceImport = $this->getContainer()->get('asmodine.admin.catalog.import');
        $catalogs = $catalogRepo->findAll(['slug'=> 'ASC'], false);
        /** Serializer $serializer */
        $serializer = $this->getContainer()->get('jms_serializer');


        /** @var CatalogBrandRepository $catalogBrandRepo */
        $catalogBrandRepo = $this->getContainer()->get('asmodine.admin.repository.catalog_brand');

        /** @var CatalogDTO $catalogDTO */
        foreach ($catalogs as $catalogDTO) {
            /** @var Catalog\Configuration $configuration */
            $configuration = $serializer->deserialize($catalogDTO->configuration, Catalog\Configuration::class, 'json');
            $catalog = Catalog::loadDTO($catalogDTO, $configuration);
            $catBs = $catalogBrandRepo->findByCatalog($catalogDTO);
            if (count($catBs) > 0) {
                /** @var CatalogBrandDTO $catBrand */
                $catBrand = $catBs[0];
                /** @var CatalogBrand\Configuration $configurationCatBrand */
                $configurationCatBrand = $serializer->deserialize($catBrand->configuration, CatalogBrand\Configuration::class, 'json');
                $catConfig = $catalog->getConfiguration();
                if ($catalog->getOrigin() == 'manual') {
                    $affConfig = $catalog->getConfiguration()->getManualConfiguration();
                } else {
                    $affConfig = $catalog->getConfiguration()->getAffiliateConfiguration();
                }
                $csvConfig = $catConfig->getCSVConfig();
                $tmp = sprintf(
                    'INSERT INTO pim_catalog (`id`,            `name`,           `slug`,            `url`,                                   `origin`,             `last_downloaded_file`,  `initial_columns`, `nb_import_to_keep`, `state`,  `work_in_progress`, `work_pending`, `message`, `cron`, `next_import_at`, `imported_at`,  `is_activated_manually`,              `activated_manually_at`, `is_activated_automatically`, `created_at`, `updated_at`,  `is_model`,                                   `separator_size`,                                       `separator_color`,                                                        `separator_ean`, `formatconfiguration_extension`, `formatconfiguration_archive`, `formatconfiguration_csv_delimiter` , `formatconfiguration_csv_enclosure`, `formatconfiguration_csv_escape` , `formatconfiguration_xml_root_tag`) 
                    VALUES                   (%s,              "%s",              "%s",              "%s",                                   "%s",                  null,                   "%s",               5,                  "new",    false,              false,          "",        null,    null,             null,           %s,                                   NOW(),                  TRUE,                         NOW(),        NOW(),          %s,                                           "%s",                                                   "%s",                                                                     "%s"          , "%s",                            "%s",                            "%s",                            "%s"                           , "%s"                        , "%s")',
                    $catalogDTO->id, $catalogDTO->name, $catalogDTO->slug, $catalog->getConfiguration()->getUrl(), $catalog->getOrigin(), 'a:0:{}', $catalogDTO->enabled ? 'TRUE' : 'FALSE', $configuration->isModelLine() ? 'TRUE' : 'FALSE', addcslashes(serialize($configurationCatBrand->getSeparatorSize()), '"'), addcslashes(serialize($configurationCatBrand->getSeparatorColor()), '"'), 'a:0:{}', substr($affConfig->getFileExtension(), 1), $affConfig->getArchiveFormat() == '.zip' ? 'zip' : 'gzip', $csvConfig['csv_delimiter'], addslashes($csvConfig['csv_enclosure']), addslashes($csvConfig['csv_escape']), 'xml'
                );
                while (strpos($tmp, '  ') !== false) {
                    $tmp = str_replace('  ', ' ', $tmp);
                }
                $sql[] = $tmp;


                // CatlaogBrandConfig
                $sql[] = sprintf(
                    'INSERT INTO `pim_import_catalog_brand` (`id`, `catalog_id`, `brand_id`,`created_at`,`updated_at`)
                    VALUES (NULL, %s, %s, NOW(), NOW())', $catalogDTO->id, $catBrand->brandId
                );


                // Filter
                $filters = $configurationCatBrand->getSimpleFilters();
                $n = 0;
                /** @var CatalogBrand\Configuration\SimpleFilter $filter */
                foreach ($filters as $filter) {
                    $betaColumnName = str_replace(['unit_price','category', 'brand','stock_amount','model_id'],['price','category_name','brand_name','stock_amount_online','model_number'], $filter->getColumnName());

                    $sql[] = sprintf(
                        'INSERT INTO `pim_import_catalog_brand_simple_filter` (`id`, `import_catalog_brand_id`, `column_name`, `contents`, `keep`,`position`,`active`,`glue`, `created_at`,`updated_at`)
                    VALUES (NULL,%s, "%s", "%s", %s, "%s", %s, "%s", NOW(), NOW())', '(SELECT id FROM pim_import_catalog_brand WHERE catalog_id = '.$catalogDTO->id.' AND brand_id = '.$catBrand->brandId.')', $betaColumnName, addcslashes(serialize($filter->getContents()), '"'), $filter->isKeep() ? 'TRUE' : 'FALSE', ++$n, 'TRUE', $filter->getGlue()
                    );
                }

                // Action
                $actions = $configurationCatBrand->getActions();
                /** @var CatalogBrand\Configuration\Action $action */
                foreach ($actions as $action) {
                    $betaColumnName = str_replace(['unit_price','category', 'brand','stock_amount','model_id'],['price','category_name','brand_name','stock_amount_online','model_number'],$action->getColumnName());
                    $contents = json_decode($action->getContents());
                    foreach ($contents as $input => $output) {
                        $sql[] = sprintf(
                            'INSERT INTO `pim_import_catalog_brand_action` (`id`, `import_catalog_brand_id`, `column_name`, `input_parameter`, `output_parameter`,`active`,`name`, `created_at`,`updated_at`)
                    VALUES (NULL,%s, "%s", "%s", "%s", TRUE, "replace", NOW(), NOW())', '(SELECT id FROM pim_import_catalog_brand WHERE catalog_id = '.$catalogDTO->id.' AND brand_id = '.$catBrand->brandId.')', $betaColumnName, addcslashes($input, '"'), addcslashes($output, '"')
                        );
                    }
                }


                // Colonne
                $columns = ['name', 'description', 'url',

                    'brand_name', 'category_name',
                    'model_number', 'ean', 'external_id',

                    'price', 'currency',
                    'avaibility',
                    'is_discount', 'discount_type', 'discount_percent', 'discount_amount', 'discount_old_price', 'discount_from', 'discount_to',
                    'delivery_cost', 'delivery_information',
                    'further_information',
                    'active_from', 'active_to',
                    'size', 'color',

                    'image_1', 'image_2', 'image_3', 'image_4', 'image_5', 'image_6', 'image_7', 'image_8', 'image_9', 'image_10',
                    'stock_amount_online', 'stock_in', 'composition'
                ];
               $catColumns =  $catalog->getConfiguration()->getColumns();
                foreach ($columns as $columnName){
                    $type1 = $type2 = $type3 = $type4 = $type5 = 'none';
                    $value1= $column1= $column1Begin=$column1End= $value2= $column2= $column2Begin=$column2End=$value3=$column3=$column3Begin=$column3End=$value4=$column4= $column4Begin=$column4End=$value5=$column5=$column5Begin=$column5End = '';

                    $betaColumnName = str_replace(['price','category_name','brand_name','stock_amount_online','model_number'], ['unit_price','category', 'brand','stock_amount','model_id'],$columnName);
                    if(isset($catColumns[$betaColumnName])){

                        /** @var Catalog\Configuration\Column\Catalog $columnCatalog */
                        $columnCatalog = $catColumns[$betaColumnName];
                    for($i = 1;$i<=5;$i++){
                       try{
                           $content =  $columnCatalog->getContent($i-1);
                           ${"type" . $i} = $content->getType();
                           if(Catalog\Configuration\Column\Catalog\Content::TYPE_STRING== $content->getType()){
                               ${"value" . $i} = $content->getValue() ??'';
                           }
                           if(Catalog\Configuration\Column\Catalog\Content::TYPE_COLUMN== $content->getType()){
                               ${"column" . $i} = $content->getValue() ??'';
                           }
                           if(Catalog\Configuration\Column\Catalog\Content::TYPE_INSIDE== $content->getType()){
                               $d = json_decode($content->getValue(), true);
                                ${"column" . $i} = $d['column'];
                                ${"column" . $i.'Begin'} =$d['start'] ;
                                ${"column" . $i.'End'} = $d['end'];
                       }
                       }catch (\Exception $e){
                           // Silence is golden
                       }
                    }
                    }
                    $sql[] = sprintf(
                        'INSERT INTO `pim_catalog_columns` (`id`, catalog_id, `name`, created_at, updated_at,
                        config_1_type, config_1_value, config_1_catalog_column_name, config_1_inside_column_begin, config_1_inside_column_end,
                        config_2_type, config_2_value, config_2_catalog_column_name, config_2_inside_column_begin, config_2_inside_column_end,
                        config_3_type, config_3_value, config_3_catalog_column_name, config_3_inside_column_begin, config_3_inside_column_end,
                        config_4_type, config_4_value, config_4_catalog_column_name, config_4_inside_column_begin, config_4_inside_column_end,
                        config_5_type, config_5_value, config_5_catalog_column_name, config_5_inside_column_begin, config_5_inside_column_end)
                      VALUES (NULL, %s, "%s", NOW(), NOW(), 
                       "%s","%s","%s","%s","%s",
                       "%s","%s","%s","%s","%s",
                       "%s","%s","%s","%s","%s",
                       "%s","%s","%s","%s","%s",
                       "%s","%s","%s","%s","%s"
                      )',
                        $catalogDTO->id, $columnName,
                        $type1, $value1, $column1, $column1Begin,$column1End,
                        $type2, $value2, $column2, $column2Begin,$column2End,
                        $type3, $value3, $column3, $column3Begin,$column3End,
                        $type4, $value4, $column4, $column4Begin,$column4End,
                        $type5, $value5, $column5, $column5Begin,$column5End
                    );

                }
            }
        }


        array_unshift($sql, 'SET FOREIGN_KEY_CHECKS = 0');
        $sql[] = 'SET FOREIGN_KEY_CHECKS = 1';
        $path = $this->getContainer()->getParameter('kernel.project_dir').'/var/files/sql/'.date('YmdHis').'_export_for_v1.sql';
        $pathLast = $this->getContainer()->getParameter('kernel.project_dir').'/var/files/sql/last_export_for_v1.sql';
        $file = new FileUtils($path);
        $file2 = new FileUtils($pathLast);
        $sql = implode(";\n", $sql).';';
        $sql = utf8_decode($sql);
        $file->saveDatas($sql);
        if ($file2->exists()) {
            unlink($file2->getRealpath());
        }
        $file2->saveDatas($sql);
    }
}
