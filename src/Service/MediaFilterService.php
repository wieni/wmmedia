<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;

class MediaFilterService
{
    const MEDIA_SMALL = 'small';
    const MEDIA_MEDIUM = 'medium';
    const MEDIA_LARGE = 'large';

    /** @var Connection */
    protected $db;

    public function __construct(
        Connection $db
    ) {
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
            self::MEDIA_SMALL => 'Small',
            self::MEDIA_MEDIUM => 'Medium',
            self::MEDIA_LARGE => 'Large',
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

        return (int) $q->countQuery()->execute()->fetchField();
    }

    protected function mediaSearchConditions(array $conditions, SelectInterface $q)
    {
        if (empty($conditions['search'])) {
            return;
        }

        $searchString = '%' . $q->escapeLike(strtolower($conditions['search'])) . '%';

        $q->leftJoin('media__field_copyright', 'copyright', 'copyright.entity_id = m.mid');
        $q->leftJoin('media__field_description', 'description', 'description.entity_id = m.mid');

        // We use a custom where to be able to LOWER.
        $q->where(
            '
                LOWER(m.name) LIKE :search1 OR
                LOWER(copyright.field_copyright_value) LIKE :search2 OR   
                LOWER(description.field_description_value) LIKE :search3    
            ',
            [
                ':search1' => $searchString,
                ':search2' => $searchString,
                ':search3' => $searchString,
            ]
        );
    }

    protected function mediaSizeConditions(array $conditions, SelectInterface $q)
    {
        if (empty($conditions['size'])) {
            return;
        }

        $q->leftJoin('media__field_width', 'width', 'width.entity_id = m.mid');
        $q->leftJoin('media__field_height', 'height', 'height.entity_id = m.mid');

        foreach ($this->getMediaSizes() as $key => $size) {
            if ($conditions['size'] === $key) {
                list($min, $max) = $size;

                if (is_integer($min)) {
                    $q->condition('width.field_width_value', $min, '>=');
                    $q->condition('height.field_height_value', $min, '>=');
                }

                if (is_integer($max)) {
                    $q->condition('width.field_width_value', $max, '<');
                    $q->condition('height.field_height_value', $max, '<');
                }
            }
        }
    }
}
