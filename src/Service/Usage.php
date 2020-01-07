<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

class Usage
{
    /** @var int */
    protected $id;
    /** @var int */
    protected $mediaId;
    /** @var string */
    protected $mediaType;
    /** @var int */
    protected $entityId;
    /** @var string */
    protected $entityType;
    /** @var string */
    protected $fieldName;
    /** @var string */
    protected $fieldType;
    /** @var bool */
    protected $required;
    /** @var string */
    protected $languageCode;

    public function __construct(
        int $id,
        int $mediaId,
        string $mediaType,
        int $entityId,
        string $entityType,
        string $fieldName,
        string $fieldType,
        bool $required,
        string $languageCode
    ) {
        $this->id = $id;
        $this->mediaId = $mediaId;
        $this->mediaType = $mediaType;
        $this->entityId = $entityId;
        $this->entityType = $entityType;
        $this->fieldName = $fieldName;
        $this->fieldType = $fieldType;
        $this->required = $required;
        $this->languageCode = $languageCode;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMediaId(): int
    {
        return $this->mediaId;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function getEntityId(): int
    {
        return $this->entityId;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getFieldType(): string
    {
        return $this->fieldType;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public static function createFromEntityAndField(EntityInterface $entity, FieldDefinitionInterface $fieldDefinition, $mediaId, $mediaType): self
    {
        return new static(
            0,
            $mediaId,
            $mediaType,
            $entity->id(),
            $entity->getEntityTypeId(),
            $fieldDefinition->getName(),
            $fieldDefinition->getType(),
            $fieldDefinition->isRequired(),
            $entity->language()->getId()
        );
    }
}
