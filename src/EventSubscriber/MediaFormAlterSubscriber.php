<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\hook_event_dispatcher\Event\Form\BaseFormEvent;
use Drupal\hook_event_dispatcher\Event\Form\FormBaseAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MediaFormAlterSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents(): array
    {
        return [
            'hook_event_dispatcher.form_base_media_form.alter' => 'mediaFormAlter',
            'hook_event_dispatcher.form_base_inline_entity_form.alter' => 'entityBrowserImagesFormAlter',
        ];
    }

    public function mediaFormAlter(FormBaseAlterEvent $event): void
    {
        $form = &$event->getForm();

        $this->removeRevisionElement($form);
    }

    public function entityBrowserImagesFormAlter(BaseFormEvent $event): void
    {
        $formState = $event->getFormState();
        $buildInfo = $formState->getBuildInfo();
        $entityBrowsers = ['entity_browser_images_form', 'entity_browser_files_form', 'entity_browser_files_editor_form'];

        if (!isset($buildInfo['form_id']) || !in_array($buildInfo['form_id'], $entityBrowsers, true)) {
            return;
        }

        $form = &$event->getForm();

        if (!isset($form['revision_log_message'])) {
            return;
        }

        $form['revision_log_message']['#access'] = false;
    }

    protected function removeRevisionElement(&$form): void
    {
        if (isset($form['revision'])) {
            $form['revision']['#access'] = false;
        }

        if (isset($form['revision_information'])) {
            $form['revision_information']['#access'] = false;
        }

        if (isset($form['revision_log'])) {
            $form['revision_log']['#access'] = false;
        }
    }
}
