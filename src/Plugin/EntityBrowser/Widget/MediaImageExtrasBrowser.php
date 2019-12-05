<?php

namespace Drupal\wmmedia\Plugin\EntityBrowser\Widget;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\entity_browser\WidgetValidationManager;
use Drupal\imgix\ImgixManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Uses a view to provide entity listing in a browser's widget.
 *
 * @EntityBrowserWidget(
 *   id = "wmmedia_media_image_browser",
 *   label = @Translation("Media image browser"),
 *   provider = "wmmedia",
 *   description = @Translation("Image listings for media browser"),
 *   auto_select = TRUE
 * )
 */
class MediaImageExtrasBrowser extends WidgetBase implements ContainerFactoryPluginInterface
{
    const PAGER_LIMIT = 20;

    /** @var EntityFieldManagerInterface */
    protected $entityFieldManager;
    /** @var ImgixManagerInterface */
    protected $imgixManager;

    /** @var SessionInterface */
    protected $session;

    /** @var Connection */
    protected $database;

    /**
     * Constructs a new View object.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance
     * @param string $plugin_id
     *   The plugin_id for the plugin instance
     * @param mixed $plugin_definition
     *   The plugin implementation definition
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
     *   Event dispatcher service
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
     *   The entity type manager
     * @param \Drupal\entity_browser\WidgetValidationManager $validation_manager
     *   The Widget Validation Manager service
     * @param ImgixManagerInterface $imgixManager
     * @param SessionInterface $session
     * @param Connection $database
     * @param EntityFieldManagerInterface $entityFieldManager
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        EventDispatcherInterface $event_dispatcher,
        EntityTypeManagerInterface $entity_type_manager,
        WidgetValidationManager $validation_manager,
        ImgixManagerInterface $imgixManager,
        SessionInterface $session,
        Connection $database,
        EntityFieldManagerInterface $entityFieldManager
    ) {
        parent::__construct(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $event_dispatcher,
            $entity_type_manager,
            $validation_manager
        );
        $this->imgixManager = $imgixManager;
        $this->session = $session;
        $this->database = $database;
        $this->entityFieldManager = $entityFieldManager;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration()
    {
        return [
                'preset' => null,
            ] + parent::defaultConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('event_dispatcher'),
            $container->get('entity_type.manager'),
            $container->get('plugin.manager.entity_browser.widget_validation'),
            $container->get('imgix.manager'),
            $container->get('session'),
            $container->get('database'),
            $container->get('entity_field.manager')
        );
    }

    /**
     * @param array $original_form
     * @param $filter
     * @return array
     */
    public function getFilterForm(array $original_form, $filter)
    {
        $element = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['container-inline', 'media-browser-filter']
            ]
        ];

        $enabledFields = ['title'];
        $fieldStorages = $this->entityFieldManager->getFieldStorageDefinitions('media');

        if (isset($fieldStorages['field_copyright'])) {
            $enabledFields[] = 'copyright';
        }

        if (isset($fieldStorages['field_description'])) {
            $enabledFields[] = 'description';
        }

        $element['search'] = [
            '#type' => 'textfield',
            '#title' => $this->t(sprintf('Search (%s)', implode(', ', $enabledFields))),
            '#attributes' => [
                'class' => ['media-browser-filter-input-search']
            ],
            '#default_value' => $filter['search'] ? $filter['search']: ''
        ];

        $element['size'] = [
            '#type' => 'select',
            '#title' => $this->t('Image size'),
            '#attributes' => [
                'class' => ['media-browser-filter-input-size'],
            ],
            '#options' => [
                '' => $this->t('Any'),
                'small' => $this->t('Small'),
                'medium' => $this->t('Medium'),
                'large' => $this->t('Large'),
            ],
            '#default_value' => $filter['size'] ? $filter['size']: ''
        ];

        $element['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Filter'),
            '#attributes' => [
                'class' => ['media-browser-filter-submit']
            ],
            '#submit' => [[$this, 'filterFormSubmit']],
        ];
        $element['reset'] = [
            '#type' => 'submit',
            '#value' => $this->t('Reset'),
            '#attributes' => [
                'class' => ['media-browser-filter-reset']
            ],
            '#submit' => [[$this, 'filterFormReset']],
        ];

        return $element;
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function filterFormSubmit(array &$form, FormStateInterface $form_state)
    {
        $this->session->set(
            $this->filterSessionName(),
            [
                'search' => $form_state->getValue('search'),
                'size' => $form_state->getValue('size'),
            ]
        );
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function filterFormReset(array &$form, FormStateInterface $form_state)
    {
        $this->session->remove($this->filterSessionName());
    }

    /**
     * @return string
     */
    private function filterSessionName()
    {
        return 'media-browser-filter';
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters)
    {
        $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

        if (!empty($form['actions']['submit'])) {
            $form['actions']['submit']['#attributes']['class'][] = 'media-image-browser-submit';
        }

        $form['#attached']['library'][] = 'wmmedia/media.browser';

        $filter = $this->session->get(
            $this->filterSessionName(),
            ['search' => '', 'size' => '']
        );

        $form['filter'] = $this->getFilterForm($original_form, $filter);
        $form['filter']['#weight'] = -1;

        // Get the selected preset.
        $presets = $this->imgixManager->getPresets();
        $params = [];
        if (!empty($presets[$this->configuration['preset']])) {
            foreach (explode('&', $presets[$this->configuration['preset']]['query']) as $item1) {
                $item2 = explode('=', $item1);
                $params[$item2[0]] = $item2[1];
            }
        }

        $images = $this->doQuery($filter);

        $form['view']['view'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => 'imgix-browser-container',
                'data-cardinality' => $form_state->get(['entity_browser', 'validators', 'cardinality', 'cardinality']),
            ],
            '#tree' => true,
        ];

        foreach ($images as $image) {
            $entityBrowserKey = 'media:' . $image->mid;

            $form['view']['view'][$entityBrowserKey] = [
                '#type' => 'container',
                '#attributes' => [
                    'class' => 'imgix-browser-item',
                ],
            ];

            if ($image->field_media_imgix_target_id) {
                $file = $this->entityTypeManager->getStorage('file')->load($image->field_media_imgix_target_id);
                if ($file) {
                    $form['view']['view'][$entityBrowserKey]['preview'] = [
                        '#weight' => -10,
                        '#theme' => 'imgix_image',
                        '#url' => $this->imgixManager->getImgixUrl($file, $params),
                        '#title' => '',
                        '#caption' => '',
                    ];
                }
            }

            $form['view']['view'][$entityBrowserKey]['checkbox'] = [
                '#type' => 'checkbox',
                '#title' => ' ',
                '#title_display' => 'after',
                '#return_value' => $entityBrowserKey,
                '#attributes' => ['name' => "entity_browser_select[$entityBrowserKey]"],
                '#default_value' => null,
            ];
            $form['view']['view'][$entityBrowserKey]['file'] = [
                '#markup' => '<p>' . $image->name . '</p>',
            ];
            if ($image->field_width_value) {
                $form['view']['view'][$entityBrowserKey]['dimensions'] = [
                    '#markup' => '<p>' . $image->field_width_value . ' x ' . $image->field_height_value . '</p>',
                ];
            }
        }

        $form['view']['pager_pager'] = [
            '#type' => 'pager',
        ];

        return $form;
    }

    /**
     * @param $filter
     * @return mixed
     */
    protected function doQuery($filter)
    {
        $fieldStorages = $this->entityFieldManager->getFieldStorageDefinitions('media');

        /** @var SelectInterface $query */
        $query = $this->database->select('media')
            ->fields('media', ['mid'])
            ->condition('media.bundle', 'image')
            ->extend('Drupal\Core\Database\Query\PagerSelectExtender');

        $query->leftJoin('media_field_data', 'data', 'media.mid = data.mid');
        $query->fields('data', ['name']);

        $fields = [
            'field_copyright' => 'field_copyright_value',
            'field_description' => 'field_description_value',
            'field_media_imgix' => 'field_media_imgix_target_id',
            'field_width' => 'field_width_value',
            'field_height' => 'field_height_value',
        ];

        foreach ($fields as $fieldName => $columnName) {
            if (isset($fieldStorages[$fieldName])) {
                $query->leftJoin("media__{$fieldName}", $fieldName, "media.mid = {$fieldName}}.entity_id");
                $query->fields($fieldName, [$columnName]);
            }
        }

        if (!empty($filter['search'])) {
            $searchCondition = $query->orConditionGroup()
                ->condition('data.name', '%' . $filter['search'] . '%', 'LIKE');

            if (isset($fieldStorages['field_copyright'])) {
                $searchCondition->condition('field_copyright.field_copyright_value', '%' . $filter['search'] . '%', 'LIKE');
            }

            if (isset($fieldStorages['field_description'])) {
                $searchCondition->condition('field_description.field_description_value', '%' . $filter['search'] . '%', 'LIKE');
            }

            $query->condition($searchCondition);
        }

        if (isset($fieldStorages['field_width'], $fieldStorages['field_height']) && !empty($filter['size'])) {
            switch ($filter['size']) {
                case 'small': // < 400x400
                    $sizeCondition = $query->andConditionGroup()
                        ->condition('width.field_width_value', 400, '<')
                        ->condition('height.field_height_value', 400, '<');
                    $query->condition($sizeCondition);
                    break;
                case 'medium': // > 400x400 & < 1200w
                    $sizeCondition = $query->andConditionGroup()
                        ->condition('width.field_width_value', 400, '>')
                        ->condition('height.field_height_value', 400, '>')
                        ->condition('width.field_width_value', 1200, '<');
                    $query->condition($sizeCondition);
                    break;
                case 'large': // > 1200w
                    $query->condition('width.field_width_value', 1200, '>');
                    break;
            }
        }

        $query->limit(self::PAGER_LIMIT);

        $query->orderBy('media.mid', 'DESC');

        return $query->execute()->fetchAll();
    }

    /**
     * Sets the #checked property when rebuilding form.
     *
     * Every time when we rebuild we want all checkboxes to be unchecked.
     *
     * @see \Drupal\Core\Render\Element\Checkbox::processCheckbox()
     * @param $element
     * @param FormStateInterface $form_state
     * @param $complete_form
     * @return array
     */
    public static function processCheckbox(&$element, FormStateInterface $form_state, &$complete_form)
    {
        $element['#checked'] = false;
        return $element;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array &$form, FormStateInterface $form_state)
    {
        $user_input = $form_state->getUserInput();

        if (isset($user_input['entity_browser_select'])) {
            $selected_rows = array_values(array_filter($user_input['entity_browser_select']));
            foreach ($selected_rows as $row) {
                if (is_string($row) && $parts = explode(':', $row, 2)) {
                    // Make sure we have a type and id present.
                    if (count($parts) == 2) {
                        try {
                            $storage = $this->entityTypeManager->getStorage($parts[0]);
                            if (!$storage->load($parts[1])) {
                                $message = $this->t('The @type Entity @id does not exist.', [
                                    '@type' => $parts[0],
                                    '@id' => $parts[1],
                                ]);
                                $form_state->setError($form['widget']['view']['entity_browser_select'], $message);
                            }
                        } catch (PluginNotFoundException $e) {
                            $message = $this->t('The Entity Type @type does not exist.', [
                                '@type' => $parts[0],
                            ]);
                            $form_state->setError($form['widget']['view']['entity_browser_select'], $message);
                        }
                    }
                }
            }

            // If there weren't any errors set, run the normal validators.
            if (empty($form_state->getErrors())) {
                parent::validate($form, $form_state);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submit(array &$element, array &$form, FormStateInterface $form_state)
    {
        $entities = $this->prepareEntities($form, $form_state);
        $this->selectEntities($entities, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $options = [];
        // Get the list of available presets.
        foreach ($this->imgixManager->getPresets() as $preset) {
            $options[$preset['key']] = $preset['key'];
        }

        $form['preset'] = [
            '#type' => 'select',
            '#title' => $this->t('Imgix preset'),
            '#default_value' => $this->configuration['preset'],
            '#options' => $options,
            '#empty_option' => $this->t('- Select a preset -'),
            '#required' => true,
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValues()['table'][$this->uuid()]['form'];
        $this->configuration['submit_text'] = $values['submit_text'];
        $this->configuration['auto_select'] = $values['auto_select'];
        $this->configuration['preset'] = $values['preset'];
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareEntities(array $form, FormStateInterface $form_state)
    {
        $entities = [];

        $input = $form_state->getUserInput();
        if (empty($input['entity_browser_select'])) {
            return $entities;
        }
        $selected_rows = array_values(array_filter($input['entity_browser_select']));
        if (!empty($selected_rows)) {
            foreach ($selected_rows as $row) {
                list($type, $id) = explode(':', $row);
                $storage = $this->entityTypeManager->getStorage($type);
                if ($entity = $storage->load($id)) {
                    $entities[] = $entity;
                }
            }
        }
        return $entities;
    }
}
