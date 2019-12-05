<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

class MediaFilterService
{
    const MEDIA_SMALL = 'small';
    const MEDIA_MEDIUM = 'medium';
    const MEDIA_LARGE = 'large';

    /** @var EntityFieldManagerInterface */
    protected $entityFieldManager;
    /** @var Connection */
    protected $db;

    public function __construct(
        EntityFieldManagerInterface $entityFieldManager,
        Connection $db
    ) {
        $this->entityFieldManager = $entityFieldManager;
        $this->db = $db;
    }

    protected static function getMediaSizes(): array
    {
        return [
            self::MEDIA_SMALL => [null, 300],
            self::MEDIA_MEDIUM => [300, 600],
            self::MEDIA_LARGE => [600, null],
        ];
    }

    protected static function getMediaSizeLabels(): array
    {
        return [
            self::MEDIA_SMALL => t('Small'),
            self::MEDIA_MEDIUM => t('Medium'),
            self::MEDIA_LARGE => t('Large'),
        ];
    }

    public static function getMediaSizeOptions(): array
    {
        $result = [];
        $labels = array_map(
            function ($size, $label) {
                list($min, $max) = $size;

                if (is_integer($min) && is_integer($max)) {
                    return "$label (> $min, < $max)";
                } else if (is_integer($min)) {
                    return "$label (> $min)";
                } else if (is_integer($max)) {
                    return "$label (< $max)";
                }

                return $label;
            },
            self::getMediaSizes(),
            self::getMediaSizeLabels()
        );

        foreach ($labels as $index => $label) {
            $key = array_keys(self::getMediaSizes())[$index];
            $result[$key] = $label;
        }

        return $result;
    }

    public function filter(array $conditions, $limit = 100): array
    {
        $q = $this->db->select('media_field_data', 'm')
            ->fields('m', ['mid', 'langcode'])
            ->extend('Drupal\Core\Database\Query\PagerSelectExtender');

        $q->groupBy('mid');
        $q->limit($limit);

        $this->mediaSearchConditions($conditions, $q);
        $this->mediaSizeConditions($conditions, $q);
        $this->mediaBundleConditions($conditions, $q);

        $q->orderBy('m.changed', 'desc');

        return $q->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function mediaCount(array $conditions): int
    {
        /** @var SelectInterface $q */
        $q = $this->db->select('media_field_data', 'm')
            ->fields('m', ['mid', 'changed', 'langcode']);

        $q->distinct();

        $this->mediaSearchConditions($conditions, $q);
        $this->mediaSizeConditions($conditions, $q);
        $this->mediaBundleConditions($conditions, $q);

        return (int) $q->countQuery()->execute()->fetchField();
    }

    protected function mediaSearchConditions(array $conditions, SelectInterface $q)
    {
        if (empty($conditions['search'])) {
            return;
        }

        $fieldStorages = $this->entityFieldManager->getFieldStorageDefinitions('media');
        $searchString = '%' . $q->escapeLike(strtolower($conditions['search'])) . '%';

        $fields = [
            'field_copyright',
            'field_description',
        ];

        foreach ($fields as $fieldName) {
            if (isset($fieldStorages[$fieldName])) {
                $q->leftJoin("media__{$fieldName}", $fieldName, "{$fieldName}.entity_id = m.mid");
            }
        }

        // We use a custom where to be able to LOWER.
        $where = 'LOWER(m.name) LIKE :search1';
        $args = [':search1' => $searchString];

        if (isset($fieldStorages['field_copyright'])) {
            $where .= ' OR LOWER(field_copyright.field_copyright_value) LIKE :search2';
            $args[':search2'] = $searchString;
        }

        if (isset($fieldStorages['field_description'])) {
            $where .= ' OR LOWER(field_description.field_description_value) LIKE :search3';
            $args[':search3'] = $searchString;
        }

        $q->where($where, $args);
    }

    protected function mediaSizeConditions(array $conditions, SelectInterface $q)
    {
        if (empty($conditions['size'])) {
            return;
        }

        $fieldStorages = $this->entityFieldManager->getFieldStorageDefinitions('media');

        $fields = [
            'media__field_width',
            'media__field_height',
        ];

        foreach ($fields as $fieldName) {
            if (isset($fieldStorages[$fieldName])) {
                $q->leftJoin("media__{$fieldName}", $fieldName, "{$fieldName}.entity_id = m.mid");
            }
        }

        foreach ($this->getMediaSizes() as $key => $size) {
            if ($conditions['size'] !== $key) {
                continue;
            }

            list($min, $max) = $size;

            if (is_integer($min) && isset($fieldStorages['field_width'])) {
                $q->condition('width.field_width_value', $min, '>=');
            }

            if (is_integer($min) && isset($fieldStorages['field_height'])) {
                $q->condition('height.field_height_value', $min, '>=');
            }

            if (is_integer($max) && isset($fieldStorages['field_width'])) {
                $q->condition('width.field_width_value', $max, '<');
            }

            if (is_integer($max) && isset($fieldStorages['field_height'])) {
                $q->condition('height.field_height_value', $max, '<');
            }
        }
    }

    protected function mediaBundleConditions(array $conditions, SelectInterface $q)
    {
        if (empty($conditions['bundle'])) {
            return;
        }

        $q->condition('m.bundle', $conditions['bundle']);
    }
}
