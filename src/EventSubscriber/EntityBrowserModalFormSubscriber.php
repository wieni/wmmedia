<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\hook_event_dispatcher\Event\Form\FormBaseAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EntityBrowserModalFormSubscriber implements EventSubscriberInterface
{

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'hook_event_dispatcher.form_base_entity_browser_form.alter' => 'modifyModalForm',
        ];
    }

    public function modifyModalForm(FormBaseAlterEvent $event): void
    {
        $form = &$event->getForm();
        $formId = $form['#form_id'];

        if (!in_array($formId, ['entity_browser_files_form', 'entity_browser_files_editor_form'])) {
            return;
        }

        $form['#attached']['library'][] = 'wmcustom/media_file_browser_modal';
    }
}