<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaSourceInterface;
use Drupal\media\MediaTypeInterface;

class ImageRepository
{
    public const PAGER_LIMIT = 20;

    /** @var Connection */
    protected $connection;
    /** @var EntityFieldManagerInterface */
    protected $entityFieldManager;

    public function __construct(
        Connection $connection,
        EntityFieldManagerInterface $entityFieldManager
    ) {
        $this->connection = $connection;
        $this->entityFieldManager = $entityFieldManager;
    }

    public function getImages(array $filters = [], int $pagerLimit = 0): array
    {
        $query = $this->getQuery();

        if (!empty($filters)) {
            $this->setFilters($query, $filters);
        }

        /* @var PagerSelectExtender $query */
        $query = $query->extend(PagerSelectExtender::class);
        $query->limit($pagerLimit ?: self::PAGER_LIMIT);

        $result = $query->execute();

        if (!$result) {
            return [];
        }

        return $result->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getImagesCount(array $filters = []): int
    {
        $query = $this->getQuery();

        if (!empty($filters)) {
            $this->setFilters($query, $filters);
        }

        $result = $query->countQuery()->execute();

        if (!$result) {
            return 0;
        }

        return (int) $result->fetchField();
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
            foreach (ImageOverviewFormBuilder::getMediaSizes() as $key => $size) {
                if ($filters['size'] !== $key) {
                    continue;
                }

                [$min, $max] = $size;

                if (is_int($min)) {
                    $query->condition('field_width.field_width_value', $min, '>=');
                    $query->condition('field_height.field_height_value', $min, '>=');
                }

                if (is_int($max)) {
                    $query->condition('field_width.field_width_value', $max, '<');
                    $query->condition('field_height.field_height_value', $max, '<');
                }
            }
        }

        if (!empty($filters['in_use'])) {
            $query->having($filters['in_use'] === 'yes' ? 'in_use = 1' : 'in_use = 0');
        }
    }

    protected function getQuery(): SelectInterface
    {
        $sourceField = $this->getSourceField();
        $fieldStorages = $this->getFieldStorages();

        $query = $this->connection->select('media', 'm');
        $query->fields('m', ['mid']);
        $query->innerJoin('media_field_data', 'd', 'm.mid = d.mid');
        $query->fields('d', ['name']);
        $query->condition('m.bundle', 'image');

        $fields = [
            'field_copyright' => 'field_copyright_value',
            'field_description' => 'field_description_value',
            $sourceField => sprintf('%s_target_id', $sourceField),
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

    protected function getSourceField(): string
    {
        /** @var MediaTypeInterface $mediaType */
        $mediaType = MediaType::load('image');
        /** @var MediaSourceInterface $source */
        $source = $mediaType->getSource();

        return $source->getConfiguration()['source_field'];
    }
}
