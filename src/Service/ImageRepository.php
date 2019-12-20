<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use PDO;

class ImageRepository
{

    public const PAGER_LIMIT = 20;

    /**
     * @var \Drupal\Core\Database\Connection
     */
    protected $connection;

    /**
     * @var \Drupal\Core\Entity\EntityFieldManagerInterface
     */
    protected $entityFieldManager;

    public function __construct(
        Connection $connection,
        EntityFieldManagerInterface $entityFieldManager
    )
    {
        $this->connection = $connection;
        $this->entityFieldManager = $entityFieldManager;
    }

    public function getImages(array $filters = [], int $pagerLimit = 0): array
    {
        $query = $this->getQuery();

        if (!empty($filters)) {
            $this->setFilters($query, $filters);
        }

        /* @var \Drupal\Core\Database\Query\PagerSelectExtender $query */
        $query = $query->extend(PagerSelectExtender::class);
        $query->limit($pagerLimit ?: self::PAGER_LIMIT);

        $result = $query->execute();

        if (!$result) {
            return [];
        }

        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function setFilters(SelectInterface $query, array $filters): void
    {
        $fieldStorages = $this->getFieldStorages();

        if (!empty($filters['search'])) {
            $pattern = '%' . $filters['search'] . '%';
            $conditionGroup = $query->orConditionGroup();
            $conditionGroup->condition('d.name', $pattern, 'LIKE');

            if (isset($fieldStorages['field_copyright'])) {
                $conditionGroup->condition('field_copyright.field_copyright_value', $pattern, 'LIKE');
            }

            if (isset($fieldStorages['field_description'])) {
                $conditionGroup->condition('field_description.field_description_value', $pattern, 'LIKE');
            }

            $query->condition($conditionGroup);
        }

        if (isset($fieldStorages['field_width'], $fieldStorages['field_height']) && !empty($filters['size'])) {
            switch ($filters['size']) {
                case 'small': // < 400x400
                    $conditionGroup = $query->andConditionGroup();
                    $conditionGroup->condition('field_width.field_width_value', 400, '<');
                    $conditionGroup->condition('field_height.field_height_value', 400, '<');
                    $query->condition($conditionGroup);
                    break;
                case 'medium': // > 400x400 & < 1200w
                    $conditionGroup = $query->andConditionGroup();
                    $conditionGroup->condition('field_width.field_width_value', 400, '>');
                    $conditionGroup->condition('field_height.field_height_value', 400, '>');
                    $conditionGroup->condition('field_width.field_width_value', 1200, '<');
                    $query->condition($conditionGroup);
                    break;
                case 'large': // > 1200w
                    $query->condition('field_width.field_width_value', 1200, '>');
                    break;
            }
        }
    }

    protected function getQuery(): SelectInterface
    {
        $fieldStorages = $this->getFieldStorages();

        $query = $this->connection->select('media', 'm');
        $query->fields('m', ['mid']);
        $query->innerJoin('media_field_data', 'd', 'm.mid = d.mid');
        $query->fields('d', ['name']);
        $query->condition('m.bundle','image');

        $fields = [
            'field_copyright' => 'field_copyright_value',
            'field_description' => 'field_description_value',
            'field_media_imgix' => 'field_media_imgix_target_id',
            'field_width' => 'field_width_value',
            'field_height' => 'field_height_value',
        ];

        foreach ($fields as $fieldName => $columnName) {
            if (isset($fieldStorages[$fieldName])) {
                $query->leftJoin("media__{$fieldName}", $fieldName, "m.mid = {$fieldName}.entity_id");
                $query->fields($fieldName, [$columnName]);
            }
        }

        $query->addExpression(sprintf('EXISTS(SELECT u.media_id FROM %s AS u WHERE u.media_id = m.mid)', UsageRepository::TABLE), 'in_use');

        $query->orderBy('m.mid', 'DESC');

        return $query;
    }

    protected function getFieldStorages(): array
    {
        static $fieldStorages;

        if (!isset($fieldStorages)) {
             $fieldStorages = $this->entityFieldManager->getFieldStorageDefinitions('media');
        }

        return $fieldStorages;
    }

}