<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\Core\Form\FormStateInterface;
use Drupal\media\MediaForm;

class MediaFormAlterSubscriber
{
    public function mediaFormAlter(array &$form, FormStateInterface $formState): void
    {
        $formObject = $formState->getFormObject();

        $this->removeRevisionElement($form);

        if ($formObject instanceof MediaForm && $formObject->getEntity()->bundle() === 'file') {
            $form['actions']['submit']['#submit'][] = [static::class, 'fileRedirect'];
        }
    }

    public function entityBrowserImagesFormAlter(array &$form, FormStateInterface $formState): void
    {
        $buildInfo = $formState->getBuildInfo();
        $entityBrowsers = [
            'entity_browser_images_form',
            'entity_browser_files_form',
            'entity_browser_files_editor_form',
        ];

        if (!isset($buildInfo['form_id']) || !in_array($buildInfo['form_id'], $entityBrowsers, true)) {
            return;
        }

        if (!isset($form['revision_log_message'])) {
            return;
        }

        $form['revision_log_message']['#access'] = false;
    }

    public static function fileRedirect(array $form, FormStateInterface $formState): void
    {
        $formState->setRedirect('wmmedia.file.overview');
    }

    protected function removeRevisionElement(array &$form): void
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
