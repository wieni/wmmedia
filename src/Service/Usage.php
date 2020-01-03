<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

class Usage
{

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $mediaId;

    /**
     * @var string
     */
    protected $mediaType;

    /**
     * @var int
     */
    protected $entityId;

    /**
     * @var string
     */
    protected $entityType;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var string
     */
    protected $fieldType;

    /**
     * @var bool
     */
    protected $required;

    /**
     * @var string
     */
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

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getMediaId(): int
    {
        return $this->mediaId;
    }

    /**
     * @return string
     */
    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    /**
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * @return string
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * @return string
     */
    public function getFieldType(): string
    {
        return $this->fieldType;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return string
     */
    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public static function createFromEntityAndField(EntityInterface $entity, FieldDefinitionInterface $fieldDefinition, $mediaId, $mediaType): Usage
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
