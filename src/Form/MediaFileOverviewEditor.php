<?php

namespace Drupal\wmmedia\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\entity_browser\Events\Events;
use Drupal\entity_browser\Events\RegisterJSCallbacks;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MediaFileOverviewEditor implements FormInterface, ContainerInjectionInterface
{
    use DependencySerializationTrait;
    use StringTranslationTrait;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    public static function create(ContainerInterface $container)
    {
        $instance = new static();
        $instance->eventDispatcher = $container->get('event_dispatcher');

        return $instance;
    }

    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $this->eventDispatcher->addListener(Events::REGISTER_JS_CALLBACKS, [$this, 'registerJSCallback']);

        $form['entity_browser'] = [
            '#type' => 'entity_browser',
            '#entity_browser' => 'files_editor',
            '#cardinality' => 1,
        ];

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['save_modal'] = [
            '#attributes' => [
                'class' => ['is-editor-entity-browser-submit'],
            ],
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#submit' => [],
            '#ajax' => [
                'callback' => '::submitSelect',
                'event' => 'click',
            ],
        ];

        $form['#attached']['library'][] = 'wmmedia/editor_file_browser';

        return $form;
    }

    public function getFormId(): string
    {
        return 'wmcmedia_file_browser_editor';
    }

    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
    }

    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
    }

    public function submitSelect(array $form, FormStateInterface $formState): AjaxResponse
    {
        $value = '';

        $values = $formState->getValues();
        $entity = $values['entity_browser']['entities'][0] ?? null;

        if ($entity instanceof MediaInterface) {
            $value = $entity->id();
        }

        $response = new AjaxResponse();
        $response->addCommand(new EditorDialogSave($value));
        $response->addCommand(new CloseModalDialogCommand());

        return $response;
    }

    public function registerJSCallback(RegisterJSCallbacks $event): void
    {
        if ($event->getBrowserID() === 'files_editor') {
            $event->registerCallback('Drupal.wmmediaBrowserDialog.selectionCompleted');
        }
    }
}
