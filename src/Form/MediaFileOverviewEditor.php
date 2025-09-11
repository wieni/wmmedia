<?php

namespace Drupal\wmmedia\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
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
            // When using our deprecated custom ckeditor4 plugin, we need to return
            // the media entity ID. This ID will be stored in the link element's
            // "data-media-file-link" attribute and as 'entity:media/<id>' href
            // attribute. We replace it with the asset URL at render time.
            // @see /js/ckeditor/plugins/media_file_link/plugin.js
            // @see \Drupal\wmmedia\Plugin\CKEditorPlugin\MediaFileBrowser
            $value = $entity->id();

            // When using drupal/ckeditor5_entity_browser, we need to return the
            // canonical URL of the entity. At render time, we replace the url
            // with the asset URL.
            // @see \Drupal\wmmedia\Plugin\Filter\MediaFileLinkFilter
            if ($this->isCkeditor5EntityBrowserContext()) {
                $value = $entity->toUrl()->toString();
            }
        }

        $response = new AjaxResponse();
        $response->addCommand(new EditorDialogSave([$value]));
        $response->addCommand(new CloseModalDialogCommand());

        return $response;
    }

    public function registerJSCallback(RegisterJSCallbacks $event): void
    {
        if ($event->getBrowserID() === 'files_editor') {
            $event->registerCallback('Drupal.wmmediaBrowserDialog.selectionCompleted');
        }
    }

    /**
     * Determine whether we are in a ckeditor5_entity_browser context.
     * The module sets a ?uuid= query parameter when the editor is opened,
     * which we can use to retrieve entity_browser config.
     * @see \Drupal\ckeditor5_entity_browser\Plugin\CKEditor5Plugin\CkeditorEntityBrowser::getDynamicPluginConfig
     *
     * @return bool
     */
    private function isCkeditor5EntityBrowserContext(): bool
    {
        $container = \Drupal::getContainer();
        $uuid = \Drupal::request()->query->get('uuid');

        if (
            !$container
            || empty($uuid)
            || !$container->has('entity_browser.selection_storage')
        ) {
            return false;
        }

        /** @var KeyValueStoreExpirableInterface $entityBrowserSelectionStorage */
        $entityBrowserSelectionStorage = \Drupal::service('entity_browser.selection_storage');

        // Check if the entity_browser config is set, if it is, we are in the
        // ckeditor5_entity_browser context.
        return $entityBrowserSelectionStorage->has($uuid);
    }

}
