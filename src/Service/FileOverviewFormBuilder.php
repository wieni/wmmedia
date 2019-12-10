<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\Entity\Media;
use Drupal\wmmedia\Plugin\EntityBrowser\Widget\MediaBrowserBase;
use Symfony\Component\HttpFoundation\RequestStack;

class FileOverviewFormBuilder
{

    use StringTranslationTrait;
    use RenderFileTrait;

    /**
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var \Drupal\wmmedia\Service\FileRepository
     */
    protected $fileRepository;

    /**
     * @var \Symfony\Component\HttpFoundation\Request|null
     */
    protected $request;

    /**
     * @var \Drupal\Core\Routing\RouteMatchInterface
     */
    protected $routeMatch;

    /**
     * @param \Drupal\wmmedia\Service\FileRepository $fileRepository
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
     */
    public function __construct(
        FileRepository $fileRepository,
        EntityTypeManagerInterface $entityTypeManager,
        RequestStack $requestStack,
        RouteMatchInterface $routeMatch
    ) {
        $this->fileRepository = $fileRepository;
        $this->entityTypeManager = $entityTypeManager;
        $this->request = $requestStack->getCurrentRequest();
        $this->routeMatch = $routeMatch;
    }

    public function setForm(array &$form, FormOptions $options): void
    {
        $form['options'] = [
            '#type' => 'value',
            '#value' => $options,
        ];

        $form['container'] = [
            '#attributes' => [
                'class' => ['media-browser-container', 'media-browser-container-' . $options->getContext()],
                'data-multiple' => $options->isMultiple(),
            ],
            '#type' => 'container',
        ];

        $this->setFormFilters($form['container'], $options);

        $header = $this->getTableHeader($options);
        $rows = $this->getTableRows($header, $options);

        $form['container']['list'] = [
            '#attributes' => [
                'class' => ['media-browser-container__list'],
            ],
            '#empty' => $this->t('No files available.'),
            '#header' => $header,
            '#rows' => $rows,
            '#type' => 'table',
        ];

        $form['container']['pager'] = [
            '#type' => 'pager',
        ];
    }

    public static function filterSubmit(array $form, FormStateInterface $formState): void
    {
        $routeMatch = \Drupal::routeMatch();
        $request = \Drupal::request();

        $triggeringElement = $formState->getTriggeringElement();
        $parents = $triggeringElement['#parents'] ?? [];
        $parent = array_pop($parents);
        $routeName = $routeMatch->getRouteName();
        $routeParameters = $routeMatch->getParameters()->all();

        $query = $request->query->all();

        if ($parent === 'reset') {
            $reset = array_merge(['page'], self::getFilterKeys());
            $query = array_diff_key($query, array_flip($reset));
            $formState->setRedirect($routeName, $routeParameters, ['query' => $query]);
            return;
        }

        $values = $formState->getValues();
        $filters = $values['filters'] ?? [];

        foreach (static::getFilterKeys() as $key) {
            $filter = $filters[$key] ?? '';
            if ($filter) {
                $query[$key] = $filter;
                continue;
            }

            unset($query[$key]);
        }

        $formState->setRedirect($routeName, $routeParameters, ['query' => $query]);
    }

    public static function getFilterKeys(): array
    {
        return [
            'name',
            'in_use',
        ];
    }

    protected function setFormFilters(array &$form, FormOptions $options): void
    {
        $filters = $this->getFilters();

        $form['filters'] = [
            '#attributes' => [
                'class' => ['media-browser-container__filters'],
            ],
            '#type' => 'container',
            '#tree' => true,
        ];

        $form['filters']['name'] = [
            '#attributes' => [
                'class' => ['media-browser-container__filters__name'],
            ],
            '#default_value' => $filters['name'] ?? '',
            '#title' => $this->t('Name'),
            '#type' => 'textfield',
        ];

        if ($options->showUsage()) {
            $form['filters']['in_use'] = [
                '#default_value' => $filters['in_use'] ?? '',
                '#empty_option' => '- ' . $this->t('All') . ' -',
                '#options' => [
                    'yes' => $this->t('Yes'),
                    'no' => $this->t('No'),
                ],
                '#title' => $this->t('In use'),
                '#type' => 'select',
            ];
        }

        $form['filters']['submit'] = [
            '#attributes' => [
                'class' => ['media-browser-container__filters__submit'],
            ],
            '#submit' => [[static::class, 'filterSubmit']],
            '#type' => 'submit',
            '#value' => $this->t('Search'),
        ];

        $form['filters']['reset'] = [
            '#submit' => [[static::class, 'filterSubmit']],
            '#type' => 'submit',
            '#value' => $this->t('Reset'),
        ];
    }

    protected function getTableHeader(FormOptions $options): array
    {
        $header = [];

        $empty = [
            'data' => [
                '#markup' => '&nbsp;',
            ],
        ];

        if ($options->isSelectable()) {
            $header[] = $empty;
        }

        $header = array_merge($header, [
            [
                'data' => $this->t('Name'),
                'field' => 'name',
            ],
            [
                'data' => $this->t('Created'),
                'field' => 'created',
                'sort' => 'desc',
            ],
            [
                'data' => $this->t('Size'),
                'field' => 'size',
            ],
            [
                'data' => $this->t('Extension'),
                'field' => 'extension',
            ],
        ]);

        if ($options->showUsage()) {
            $header[] = [
                'data' => $this->t('In use'),
                'field' => 'in_use',
            ];
        }

        if ($options->showOperations()) {
            $header[] = $empty;
        }

        return $header;
    }

    protected function getTableRows(array $header, FormOptions $options): array
    {
        $filters = $this->getFilters();

        $files = $this->fileRepository->getFiles($filters, $header, $options->getPagerLimit());

        $storage = $this->entityTypeManager->getStorage('media');
        $listBuilder = $this->entityTypeManager->getListBuilder('media');

        return array_filter(array_map(function(array $data) use ($storage, $listBuilder, $options) {
            $mid = $data['mid'] ?? '';

            if (!$mid) {
                return null;
            }

            $media = $storage->load($mid);

            if (!$media instanceof Media) {
                return null;
            }

            $key = sprintf('media:%s', $media->id());

            /* @var \Drupal\file\Entity\File $file */
            $file = $media->get('field_media_file')->entity;

            $operations = $listBuilder->getOperations($media);
            $timestamp = $data['created'] ?? '';
            $date = $timestamp ? (new \DateTime())->setTimestamp($timestamp)->format('d/m/Y H:i') : '';

            $row = [];

            if ($options->isSelectable()) {
                $browserKey = MediaBrowserBase::BROWSER_KEY;
                $name = $options->isMultiple() ? sprintf('%s[%s]', $browserKey, $key) : $browserKey;

                $row[$key] = [
                    'data' => [
                        [
                            '#attributes' => [
                                'name' => $name,
                            ],
                            '#return_value' => $key,
                            '#type' => $options->isMultiple() ? 'checkbox' : 'radio',
                            '#value' => 0,
                        ],
                    ],
                    'class' => ['media-browser-container__list__select'],
                ];
            }

            $row = array_merge($row, [
                'name' => [
                    'data' => $this->renderFile($file, $data['name']),
                ],
                'created' => $date,
                'size' => !empty($data['size']) ? format_size($data['size']) : '',
                'extension' => $data['extension'] ?? '',
            ]);

            if ($options->showUsage()) {
                $row['in_use'] = $data['in_use'] ? $this->t('Yes') : $this->t('No');
            }

            if ($options->showOperations()) {
                $row['operations'] = [
                    'data' => [
                        '#links' => $operations,
                        '#type' => 'operations',
                    ],
                ];
            }

            return $row;
        }, $files));
    }

    protected function getFilters(): array
    {
        $filters = [];

        if (!$this->request) {
            return $filters;
        }

        foreach (self::getFilterKeys() as $key) {
            $filter = $this->request->query->get($key);
            if ($filter) {
                $filters[$key] = $filter;
            }
        }

        return $filters;
    }
}
