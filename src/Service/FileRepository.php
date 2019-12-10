<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use PDO;

class FileRepository
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

    /**
     * @param \Drupal\Core\Database\Connection $connection
     * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
     */
    public function __construct(
        Connection $connection,
        EntityFieldManagerInterface $entityFieldManager
    ) {
        $this->connection = $connection;
        $this->entityFieldManager = $entityFieldManager;
    }

    public function getFiles(array $filters = [], array $header = [], int $pagerLimit = 0): array
    {
        $query = $this->getQuery();

        if (!empty($header)) {
            /* @var \Drupal\Core\Database\Query\TableSortExtender $query */
            $query = $query->extend(TableSortExtender::class);
            $query->orderByHeader($header);
        }

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
        $fieldStorages = $this->entityFieldManager->getFieldStorageDefinitions('media');

        if (!empty($filters['name']) && isset($fieldStorages['name'])) {
            $pattern = '%' . $filters['name'] . '%';
            $conditionGroup = $query->orConditionGroup();
            $conditionGroup->condition('d.name', $pattern, 'LIKE');
            $conditionGroup->condition('fm.filename', $pattern, 'LIKE');
            $query->condition($conditionGroup);
        }

        if (!empty($filters['in_use'])) {
            $query->having($filters['in_use'] === 'yes' ? 'in_use = 1' : 'in_use = 0');
        }
    }

    protected function getQuery(): SelectInterface
    {
        $query = $this->connection->select('media', 'm');
        $query->innerJoin('media_field_data', 'd', 'm.mid = d.mid');
        $query->innerJoin('media__field_media_file', 'mf', 'm.mid = mf.entity_id');
        $query->innerJoin('file_managed', 'fm', 'mf.field_media_file_target_id = fm.fid');
        $query->condition('m.bundle','file');
        $query->addField('m', 'mid');
        $query->addField('d', 'name');
        $query->addField('d', 'created');
        $query->addField('fm', 'filename');
        $query->addField('fm', 'uri');
        $query->addField('fm', 'filesize', 'size');
        $query->addExpression("SUBSTRING_INDEX(fm.filename, '.', -1)", 'extension');
        $query->addExpression(sprintf('EXISTS(SELECT u.media_id FROM %s AS u WHERE u.media_id = m.mid)', UsageRepository::TABLE), 'in_use');
        return $query;
    }
}
