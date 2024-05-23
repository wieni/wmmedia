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
                $conditionGroup->condition('d.field_copyright__value', $pattern, 'LIKE');
            }

            if (isset($fieldStorages['field_description'])) {
                $conditionGroup->condition('d.field_description__value', $pattern, 'LIKE');
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
                    $query->condition('d.field_width', $min, '>=');
                    $query->condition('d.field_height', $min, '>=');
                }

                if (is_int($max)) {
                    $query->condition('d.field_width', $max, '<');
                    $query->condition('d.field_height', $max, '<');
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
            'field_copyright' => 'field_copyright__value',
            'field_description' => 'field_description__value',
            'field_width' => 'field_width',
            'field_height' => 'field_height',
        ];

        foreach ($fields as $alias => $columnName) {
            $query->addField('d', $columnName, $alias);
        }

        if (isset($fieldStorages[$sourceField])) {
            $query->innerJoin("media__{$sourceField}", $sourceField, "m.mid = {$sourceField}.entity_id");
            $query->fields($sourceField, [sprintf('%s_target_id', $sourceField)]);
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
