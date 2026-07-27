<?php

namespace Drupal\wmmedia\Service;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Statement\FetchAs;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\media\MediaInterface;

class UsageRepository
{
    public const TABLE = 'wmmedia_usage';

    /** @var Connection */
    protected $connection;

    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    /** @return Usage[] */
    public function getUsageByField(EntityInterface $entity, FieldDefinitionInterface $field): array
    {
        $select = $this->connection->select(self::TABLE, 'u');
        $select->fields('u');
        $select->condition('entity_type', $entity->getEntityTypeId());
        $select->condition('entity_id', $entity->id());
        $select->condition('field_name', $field->getName());
        $select->condition('field_type', $field->getType());
        $select->condition('language_code', $entity->language()->getId());

        $result = $select->execute();

        if (!$result) {
            return [];
        }

        return array_map(
            static function ($row) {
                return new Usage(...array_values($row));
            },
            $this->fetchAllAssociative($result)
        );
    }

    /** @return Usage[] */
    public function getUsageByMedia(MediaInterface $media): array
    {
        $select = $this->connection->select(self::TABLE, 'u');
        $select->fields('u');
        $select->condition('media_id', $media->id());

        $result = $select->execute();

        if (!$result) {
            return [];
        }

        return array_map(
            static function ($row) {
                return new Usage(...array_values($row));
            },
            $this->fetchAllAssociative($result)
        );
    }

    /**
     * @param array $mediaIds Array of media IDs.
     * @return array<int, bool> Array with media IDs as keys and a boolean
     *   indicating if the media is used.
     */
    public function hasUsage(array $mediaIds): array
    {
        if (empty($mediaIds)) {
            return [];
        }
        $select = $this->connection->select(self::TABLE, 'u');
        $select->fields('u', ['media_id']);
        $select->addExpression(true, 'used');
        $select->condition('media_id', $mediaIds, 'IN');
        $select->groupBy('media_id');

        $result = array_fill_keys($mediaIds, false);
        $data = $select->execute()->fetchAllKeyed();
        foreach ($data as $mediaId => $used) {
            $result[$mediaId] = (bool) $used;
        }

        return $result;
    }

    public function write(Usage $usage): void
    {
        $insert = $this->connection->insert(self::TABLE);
        $insert->fields([
            'media_id' => $usage->getMediaId(),
            'media_type' => $usage->getMediaType(),
            'entity_id' => $usage->getEntityId(),
            'entity_type' => $usage->getEntityType(),
            'field_name' => $usage->getFieldName(),
            'field_type' => $usage->getFieldType(),
            'required' => (int) $usage->isRequired(),
            'language_code' => $usage->getLanguageCode(),
        ]);
        $insert->execute();
    }

    public function delete(Usage $usage): void
    {
        $delete = $this->connection->delete(self::TABLE);
        $delete->condition('id', $usage->getId());
        $delete->execute();
    }

    public function deleteByEntity(EntityInterface $entity): void
    {
        $delete = $this->connection->delete(self::TABLE);
        $delete->condition('entity_id', $entity->id());
        $delete->condition('entity_type', $entity->getEntityTypeId());
        $delete->execute();
    }

    public function deleteByMedia(MediaInterface $media): void
    {
        $delete = $this->connection->delete(self::TABLE);
        $delete->condition('media_id', $media->id());
        $delete->execute();
    }

    private function fetchAllAssociative(StatementInterface $result): array
    {
        // The FetchAs enum only exists since Drupal 11.2, where the integer
        // fetch modes were deprecated (they are removed in Drupal 12).
        return DeprecationHelper::backwardsCompatibleCall(
            \Drupal::VERSION,
            '11.2',
            static fn () => $result->fetchAll(FetchAs::Associative),
            static fn () => $result->fetchAll(\PDO::FETCH_ASSOC),
        );
    }
}
