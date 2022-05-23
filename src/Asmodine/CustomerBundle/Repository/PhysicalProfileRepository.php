<?php

namespace Asmodine\CustomerBundle\Repository;

use Asmodine\CommonBundle\DTO\PhysicalProfileDTO;
use Asmodine\CommonBundle\Model\Morphoprofile\Gender;
use Asmodine\CommonBundle\Model\Profile\Body;
use Asmodine\CommonBundle\Repository\AbstractAsmodineRepository;
use Asmodine\CommonBundle\Util\Str;

/**
 * Class PhysicalProfileRepository.
 */
class PhysicalProfileRepository extends AbstractAsmodineRepository
{
    /**
     * Get Current PhysicalProfileDTO.
     *
     * @param int $userId
     * @return PhysicalProfileDTO|null
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function getByUserId(int $userId): ?PhysicalProfileDTO
    {
        $dto = null;
        $sql = 'SELECT * FROM `cim_customer` WHERE user_id = :user_id';
        $stmt = $this->execute($sql, ['user_id' => $userId]);
        if ($row = $stmt->fetch()) {
            $dto = new PhysicalProfileDTO($row);
        }

        $sql = 'SELECT * FROM `cim_customer_measure` WHERE user_id = :user_id';
        $stmt = $this->execute($sql, ['user_id' => $userId]);
        while ($row = $stmt->fetch()) {
            $key = ucwords($row['body_part'], '_');
            $dto->$key = $row['value'];
        }

        return $dto;
    }

    /**
     * @return array
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function findAllOrderDesc(){
        $usersId = [];
        $sql = 'SELECT user_id FROM `cim_customer` ORDER BY updated_at DESC';
        $stmt = $this->execute($sql, []);
        while ($row = $stmt->fetch()) {
            $usersId[] = $row['user_id'];
        }
        return $usersId;
    }

    /**
     * @param PhysicalProfileDTO $physicalProfileDTO
     * @throws \Asmodine\CommonBundle\Exception\RepositoryException
     */
    public function insertOrUpdate(PhysicalProfileDTO $physicalProfileDTO): void
    {
        $sql = 'INSERT INTO `cim_customer` (
                  `user_id`, `physical_profile_id`, `gender`, `height`, `weight`,
                  `skin`, `hair`, `eyes`, `size`, `morphotype`,`morphoprofile`, `morpho_weight`,
                  `created_at`, `updated_at`)
                VALUES (:user_id, :physical_profile_id, :gender, :height, :weight, 
                  :skin, :hair, :eyes, :size, :morphotype, :morphoprofile, :morpho_weight,
                  :created_at, :updated_at)'
            .'ON DUPLICATE KEY UPDATE '
            .'`physical_profile_id` = :physical_profile_id, '
            .'`gender` = :gender, '
            .'`height` = :height, '
            .'`weight` = :weight, '
            .'`skin` = :skin, '
            .'`hair` = :hair, '
            .'`eyes` = :eyes, '
            .'`size` = :size, '
            .'`morphotype` = :morphotype, '
            .'`morphoprofile` = :morphoprofile, '
            .'`morpho_weight` = :morpho_weight, '
            .'`updated_at` = :updated_at';

        $params = [
            'user_id' => $physicalProfileDTO->userId,
            'physical_profile_id' => $physicalProfileDTO->id,
            'gender' => Gender::getLetter($physicalProfileDTO->gender),
            'height' => $physicalProfileDTO->height,
            'weight' => $physicalProfileDTO->weight,
            'skin' => $physicalProfileDTO->skin,
            'hair' => $physicalProfileDTO->hair,
            'eyes' => $physicalProfileDTO->eyes,
            'size' => $physicalProfileDTO->size,
            'morphotype' => $physicalProfileDTO->morphotype,
            'morphoprofile' => $physicalProfileDTO->morphoprofile,
            'morpho_weight' => $physicalProfileDTO->morphoWeight,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ];
        $this->execute($sql, $params);

        $slugs = Body::getSlugs();
        $sql = 'INSERT INTO cim_customer_measure (`user_id`,`body_part`,`value`)
                  VALUES (:user_id, :body, :measure)
                  ON DUPLICATE KEY UPDATE `value` = :measure';
        array_map(
            function ($bodySlug) use ($sql, $physicalProfileDTO) {
                $camelCase = lcfirst(Str::toCamelCase($bodySlug));
                $snakeCase = lcfirst(Str::toSnakeCase($bodySlug));
                $measure = null;
                if (isset($physicalProfileDTO->$camelCase)) {
                    $measure = $physicalProfileDTO->$camelCase;
                }
                if (isset($physicalProfileDTO->$snakeCase)) {
                    $measure = $physicalProfileDTO->$snakeCase;
                }
                if (!is_null($measure)) {
                    $params = [
                        'user_id' => $physicalProfileDTO->userId,
                        'body' => $bodySlug,
                        'measure' => $measure,
                    ];
                    $this->execute($sql, $params);
                }
            },
            $slugs
        );
    }
}
