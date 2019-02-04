<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hook_event_dispatcher\Event\Form\FormIdAlterEvent;
use Drupal\media\MediaInterface;
use Drupal\wmmedia\Service\MediaReferenceDiscovery;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MediaDeleteFormAlterSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    /** @var MediaReferenceDiscovery */
    protected $referenceDiscovery;

    public function __construct(
        MediaReferenceDiscovery $mediaReferenceDiscovery
    ) {
        $this->referenceDiscovery = $mediaReferenceDiscovery;
    }

    public static function getSubscribedEvents()
    {
        return [
            'hook_event_dispatcher.form_media_image_delete_form.alter' => 'onAlter',
        ];
    }

    public function onAlter(FormIdAlterEvent $event)
    {
        $form = &$event->getForm();
        /** @var ContentEntityDeleteForm $formObject */
        $formObject = $event->getFormState()->getFormObject();
        /** @var MediaInterface $entity */
        $entity = $formObject->getEntity();
        $usages = $this->referenceDiscovery->getUsages($entity);

        $count = 0;
        array_walk_recursive($usages, function () use (&$count) {
            $count++;
        });

        if ($count === 0) {
            return;
        }

        $markup = sprintf(
            '<p>%s</p>',
            $this->formatPlural($count, 'Caution, this image is being referenced by 1 entity:', 'Caution, this image is being referenced by @count entities.')
        );

        $markup .= '<ul>';

        foreach ($usages as $entityTypeId => $bundles) {
            foreach ($bundles as $bundle => $fields) {
                /** @var FieldableEntityInterface $entity */
                foreach ($fields as $fieldName => $entity) {
                    $label = $entity->label();

                    if (empty($entity->label())) {
                        $label = sprintf('<i>%s</i>', $this->t('Entity of type :bundle', [':bundle' => $entity->bundle()]));
                    }

                    if ($entity->hasLinkTemplate('edit-form')) {
                        $markup .= sprintf('<li><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></li>', $entity->toUrl('edit-form')->toString(), $label);
                    } else {
                        $markup .= sprintf('<li>%s</li>', $label);
                    }
                }
            }
        }

        $markup .= '</ul>';

        if ($this->hasRequiredUsages($usages)) {
            $form['actions']['submit']['#disabled'] = true;
            $markup .= sprintf(
                '<p>%s</p>',
                $this->formatPlural(count($usages), 'This field is required. Please delete it before deleting the image.', 'One of these fields is required. Please remove all required fields before deleting the image.')
            );
        } else {
            $markup .= sprintf(
                '<p>%s</p><p><b>%s</b></p>',
                $this->t('When deleting this image, all referencing fields will be deleted.'),
                $this->t('Are you sure you want to proceed? This action cannot be undone.')
            );
        }

        $form['description']['#markup'] = $markup;
    }

    /** @param $usages FieldableEntityInterface[] */
    protected function hasRequiredUsages(array $usages)
    {
        foreach ($usages as $entityTypeId => $bundles) {
            foreach ($bundles as $bundle => $fields) {
                /** @var FieldableEntityInterface $entity */
                foreach ($fields as $fieldName => $entity) {
                    /** @var FieldItemInterface $field */
                    foreach ($entity->get($fieldName) as $field) {
                        $fieldDefinition = $field->getFieldDefinition();

                        if ($fieldDefinition->isRequired()) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}
