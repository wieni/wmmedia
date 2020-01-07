<?php

namespace Drupal\wmmedia\Service;

use DOMXPath;
use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\wmmedia\Plugin\QueueWorker\MediaUsageQueueWorker;

class UsageManager
{

    use StringTranslationTrait;

    /**
     * @var \Drupal\Core\Database\Connection
     */
    protected $connection;

    /**
     * @var \Drupal\Core\Entity\EntityFieldManagerInterface
     */
    protected $entityFieldManager;

    /**
     * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
     */
    protected $entityTypeBundleInfo;

    /**
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var \Drupal\Core\Queue\QueueInterface
     */
    protected $queue;

    /**
     * @var \Drupal\wmmedia\Service\UsageRepository
     */
    protected $repository;

    public function __construct(
        EntityFieldManagerInterface $entityFieldManager,
        EntityTypeManagerInterface $entityTypeManager,
        EntityTypeBundleInfoInterface $entityTypeBundleInfo,
        Connection $connection,
        QueueFactory $queueFactory,
        UsageRepository $repository
    ) {
        $this->entityFieldManager = $entityFieldManager;
        $this->entityTypeManager = $entityTypeManager;
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
        $this->connection = $connection;
        $this->queue = $queueFactory->get(MediaUSageQueueWorker::ID);
        $this->repository = $repository;
    }

    public function hasUsage(Media $media): bool
    {
        return !empty($this->getUsage($media));
    }

    /**
     * @param \Drupal\media\Entity\Media $media
     * @return array|\Drupal\wmmedia\Service\Usage[]
     */
    public function getUsage(Media $media): array
    {
        static $usages = [];

        if (isset($usages[$media->id()])) {
            return $usages[$media->id()];
        }

        return $usages[$media->id()] = $this->repository->getUsageByMedia($media);
    }

    public function track(EntityInterface $entity): void
    {
        if (!$entity instanceof ContentEntityInterface) {
            return;
        }

        $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
        foreach ($fieldDefinitions as $fieldDefinition) {
            if ($fieldDefinition->getType() === 'entity_reference') {
                $this->trackReference($entity, $fieldDefinition);
                continue;
            }

            if ($fieldDefinition->getType() === 'text_long') {
                $this->trackText($entity, $fieldDefinition);
                continue;
            }

            if ($fieldDefinition->getType() === 'wmmedia_media_image_extras') {
                $this->trackMedia($entity, $fieldDefinition);
                continue;
            }
        }
    }

    public function clear(EntityInterface $entity): void
    {
        if ($entity instanceof Media) {
            $this->repository->deleteByMedia($entity);
            return;
        }

        $this->repository->deleteByEntity($entity);
    }

    public function generate(): void
    {
        $entityTypeDefinitions = $this->entityTypeManager->getDefinitions();
        $excludedEntityTypes = ['media', 'menu_link_content', 'redirect', 'webform_submission'];

        foreach ($entityTypeDefinitions as $definition) {
            $entityType = $definition->id();
            $idKey = $definition->getKey('id');
            $bundleKey = $definition->getKey('bundle');
            $bundles = $this->entityTypeBundleInfo->getBundleInfo($entityType);
            $table = $definition->getBaseTable();
            $group = $definition->getGroup();

            if (
                $group !== 'content' ||
                !$bundleKey ||
                !$table ||
                in_array($entityType, $excludedEntityTypes, true)
            ) {
                continue;
            }

            $this->queue($entityType, $idKey, $bundleKey, array_keys($bundles), $table);
        }
    }

    public function setOperations(Media $media, array &$operations): void
    {
        if (!$this->hasUsage($media)) {
            return;
        }

        $operations['usage'] = [
            'title' => $this->t('Usage'),
            'weight' => 20,
            'url' => Url::fromRoute('wmmedia.usage', ['media' => $media->id()]),
        ];
    }

    public function getUsageAsTable(Media $media, bool $showOperations = true): array
    {
        $usage = $this->getUsage($media);

        $header = [
            $this->t('Type'),
            $this->t('Title'),
            $this->t('Field'),
            $this->t('Required'),
        ];

        if ($showOperations) {
            $header[] = ['#markup' => '&nbsp;'];
        }

        $rows = [];

        foreach ($usage as $row) {
            $storage = $this->entityTypeManager->getStorage($row->getEntityType());
            $entity = $storage->load($row->getEntityId());

            if (!$entity) {
                continue;
            }

            $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($row->getEntityType(), $entity->bundle());

            $field = $fieldDefinitions[$row->getFieldName()] ?? null;

            if (!$field) {
                continue;
            }

            $row = [
                'type' => $entity->getEntityType()->getLabel(),
                'label' => $entity->label(),
                'field' => $field->getLabel(),
                'required' => $field->isRequired() ? $this->t('Yes') : $this->t('No'),
            ];

            if (!$showOperations) {
                $rows[] = $row;
                continue;
            }

            $destination = Url::fromRoute('wmmedia.usage', ['media' => $media->id()])->toString();

            $operations = [];
            if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
                $operations['edit'] = [
                    'title' => $this->t('Edit'),
                    'weight' => 10,
                    'url' => $entity->toUrl('edit-form', ['query' => ['destination' => $destination]]),
                ];
            }

            $row['operations'] = [
                'data' => [
                    '#links' => $operations,
                    '#type' => 'operations',
                ],
            ];

            $rows[] = $row;
        }

        return [
            '#empty' => $this->t(':label is not in use.', [':label' => $media->getName()]),
            '#header' => $header,
            '#rows' => $rows,
            '#type' => 'table',
        ];
    }

    protected function trackText(EntityInterface $entity, FieldDefinitionInterface $fieldDefinition): void
    {
        $value = $entity->get($fieldDefinition->getName())->value;

        if (empty($value)) {
            $this->setUsage($entity, $fieldDefinition, []);
            return;
        }

        $mediaIds = $this->getIdsFromText($value);

        $this->setUsage($entity, $fieldDefinition, $mediaIds);
    }

    protected function trackReference(EntityInterface $entity, FieldDefinitionInterface $fieldDefinition): void
    {
        $handler = $fieldDefinition->getSetting('handler');

        if (!$handler || $handler !== 'default:media') {
            return;
        }

        $list = $entity->get($fieldDefinition->getName());

        if (!$list instanceof EntityReferenceFieldItemListInterface) {
            return;
        }

        $mediaIds = array_reduce($list->referencedEntities(), static function($mediaIds, EntityInterface $item) {
            $mediaIds[(int) $item->id()] = $item->bundle();
            return $mediaIds;
        }, []);

        $this->setUsage($entity, $fieldDefinition, $mediaIds);
    }

    protected function trackMedia(EntityInterface $entity, FieldDefinitionInterface $fieldDefinition): void
    {
        $list = $entity->get($fieldDefinition->getName());

        if (!$list instanceof FieldItemListInterface) {
            return;
        }

        $imageIds = [];
        $textIds = [];

        foreach ($list->getValue() as $value) {
            if (isset($value['description'])) {
                $textIds[] = $this->getIdsFromText($value['description']);
            }

            if (isset($value['target_id'])) {
                $imageIds[(int) $value['target_id']] = 'image';
            }
        }

        $mediaIds = array_replace($imageIds, array_replace(...$textIds));

        $this->setUsage($entity, $fieldDefinition, $mediaIds);
    }

    protected function setUsage(EntityInterface $entity, FieldDefinitionInterface $fieldDefinition, array $mediaIds): void
    {
        $existingUsage = $this->repository->getUsageByField($entity, $fieldDefinition);
        $existingMediaIds = [];

        foreach ($existingUsage as $usage) {
            if (!isset($mediaIds[$usage->getMediaId()])) {
                $this->repository->delete($usage);
                continue;
            }

            $existingMediaIds[] = $usage->getMediaId();
        }

        foreach ($mediaIds as $mediaId => $mediaType) {
            if (in_array($mediaId, $existingMediaIds, true)) {
                continue;
            }

            $usage = Usage::createFromEntityAndField($entity, $fieldDefinition, $mediaId, $mediaType);
            $this->repository->write($usage);
        }
    }

    protected function queue(string $entityType, string $idKey, string $bundleKey, array $bundles, string $table): void
    {
        $activeBundles = array_filter($bundles, function(string $bundle) use($entityType) {
            return $this->bundleHasUsageFields($entityType, $bundle);
        });

        if (empty($activeBundles)) {
            return;
        }

        $select = $this->connection->select($table);
        $select->fields($table, [$idKey]);
        $select->condition($bundleKey, $activeBundles, 'IN');

        $result = $select->execute();

        if (!$result) {
            return;
        }

        foreach ($result->fetchCol() as $id) {
            $this->queue->createItem(['type' => $entityType, 'id' => $id]);
        }
    }

    protected function bundleHasUsageFields(string $entityType, string $bundle): bool
    {
        $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);

        foreach ($fieldDefinitions as $definition) {
            $fieldType = $definition->getType();

            if (in_array($fieldType, ['entity_reference', 'text_long', 'wmmedia_media_image_extras'])) {
                return true;
            }
        }

        return false;
    }

    protected function getIdsFromText(string $text): array
    {
        $mediaIds = [];
        $dom = Html::load($text);
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//a[@data-media-file-link]') as $element) {
             /* @var \DOMElement $element */
            $mediaIds[(int) $element->getAttribute('data-media-file-link')] = 'file';
        }

        return $mediaIds;
    }
}
