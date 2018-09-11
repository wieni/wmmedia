<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\media\MediaInterface;

class MediaReferenceDiscovery
{
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var EntityFieldManagerInterface */
    protected $entityFieldManager;
    /** @var EntityTypeBundleInfoInterface */
    protected $entityTypeBundleInfo;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        EntityFieldManagerInterface $entityFieldManager,
        EntityTypeBundleInfoInterface $entityTypeBundleInfo
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->entityFieldManager = $entityFieldManager;
        $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    }

    public function getUsages(MediaInterface $media)
    {
        $fields = $this->getMediaEntityFields();
        $results = [];

        foreach ($fields as $entityTypeId => $bundles) {
            $storage = $this->entityTypeManager->getStorage($entityTypeId);

            foreach ($bundles as $bundle => $fields) {
                foreach ($fields as $fieldName) {
                    $ids = $storage->getQuery()
                        ->condition($fieldName, $media->id())
                        ->execute();

                    if (empty($ids)) {
                        continue;
                    }

                    foreach ($storage->loadMultiple($ids) as $entity) {
                        $results[$fieldName] = $entity;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Return all media entity reference fields, optionally for a given entity type or bundle
     *
     * @param string|null $entityTypeId
     * @param string|null $bundle
     *
     * @return array|bool
     */
    public function getMediaEntityFields($entityTypeId = null, $bundle = null)
    {
        if ($entityTypeId && $bundle) {
            return $this->getMediaEntityFieldsByBundle($entityTypeId, $bundle);
        }

        if ($entityTypeId) {
            return $this->getMediaEntityFieldsByEntityType($entityTypeId);
        }

        $results = [];
        $entityTypes = $this->entityTypeManager->getDefinitions();

        foreach (array_keys($entityTypes) as $entityTypeId) {
            $fields = $this->getMediaEntityFields($entityTypeId);

            if (empty($fields)) {
                continue;
            }

            $results[$entityTypeId] = $fields;
        }

        return $results;
    }

    protected function getMediaEntityFieldsByBundle(string $entityTypeId, string $bundle)
    {
        $entityType = $this->entityTypeManager->getDefinition($entityTypeId);

        if (!$entityType->entityClassImplements(FieldableEntityInterface::class)) {
            return [];
        }

        $fields = $this->entityFieldManager->getFieldDefinitions($entityTypeId, $bundle);
        $results = [];

        foreach ($fields as $fieldName => $fieldDefinition) {
            if (!$this->isFieldMediaReference($fieldDefinition)) {
                continue;
            }

            $results[] = $fieldName;
        }

        return $results;
    }

    protected function getMediaEntityFieldsByEntityType(string $entityTypeId)
    {
        $results = [];
        $bundles = $this->entityTypeBundleInfo->getBundleInfo($entityTypeId);

        foreach (array_keys($bundles) as $bundle) {
            $fields = $this->getMediaEntityFieldsByBundle($entityTypeId, $bundle);

            if (empty($fields)) {
                continue;
            }

            $results[$bundle] = $fields;
        }

        return $results;
    }

    protected function isFieldMediaReference(FieldDefinitionInterface $fieldDefinition)
    {
        return in_array($fieldDefinition->getType(), ['entity_reference', 'wmmedia_media_image_extras'])
            && $fieldDefinition->getSetting('handler') === 'default:media';
    }
}