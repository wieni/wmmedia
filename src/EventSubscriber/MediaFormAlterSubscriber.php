<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\Core\Form\FormStateInterface;
use Drupal\hook_event_dispatcher\Event\Form\BaseFormEvent;
use Drupal\hook_event_dispatcher\Event\Form\FormBaseAlterEvent;
use Drupal\media\MediaForm;
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
        $formObject = $event->getFormState()->getFormObject();


        $this->removeRevisionElement($form);

        if ($formObject instanceof MediaForm && $formObject->getEntity()->bundle() === 'file') {
            $this->setRedirect($form);
        }
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

    public static function fileRedirect(array $form, FormStateInterface $formState): void
    {
        $formState->setRedirect('wmmedia.file.overview');
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

    protected function setRedirect(array &$form): void
    {
        $form['actions']['submit']['#submit'][] = [static::class, 'fileRedirect'];
    }
}
