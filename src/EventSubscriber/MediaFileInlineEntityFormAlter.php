<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\hook_event_dispatcher\Event\Form\BaseFormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MediaFileInlineEntityFormAlter implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'hook_event_dispatcher.form_base_inline_entity_form.alter' => 'removeRevisionLog',
        ];
    }

    public function removeRevisionLog(BaseFormEvent $event): void
    {
        $formState = $event->getFormState();

        $buildInfo = $formState->getBuildInfo();

        $entityBrowsers = ['entity_browser_files_form', 'entity_browser_files_editor_form'];

        if (!isset($buildInfo['form_id']) || !in_array($buildInfo['form_id'], $entityBrowsers, true)) {
            return;
        }

        $form = &$event->getForm();

        if (!isset($form['revision_log_message'])) {
            return;
        }

        $form['revision_log_message']['#access'] = false;
    }
}
