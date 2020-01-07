<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\file\FileInterface;
use Drupal\media\Entity\Media;
use Drupal\wmmedia\Plugin\EntityBrowser\Widget\MediaBrowserBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class FileOverviewFormBuilder extends OverviewFormBuilderBase
{
    use RenderFileTrait;

    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var FileRepository */
    protected $fileRepository;
    /** @var Request|null */
    protected $request;
    /** @var RouteMatchInterface */
    protected $routeMatch;

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
        $this->setFormContainer($form, $options);

        $header = $this->getTableHeader($options);
        $rows = $this->getTableRows($header, $options);

        $form['container']['list'] = [
            '#attributes' => [
                'class' => ['wmmedia__list'],
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

    public static function getFilterKeys(): array
    {
        return [
            'search',
            'in_use',
        ];
    }

    protected function setFormFilters(array &$form, FormOptions $options): void
    {
        $filters = $this->getFilters();

        $form['filters'] = [
            '#attributes' => [
                'class' => ['wmmedia__filters'],
            ],
            '#type' => 'container',
            '#tree' => true,
        ];

        $form['filters']['search'] = [
            '#attributes' => [
                'class' => ['wmmedia__filters__search'],
            ],
            '#default_value' => $filters['search'] ?? '',
            '#title' => $this->t('Search'),
            '#type' => 'textfield',
        ];

        $this->setFormFilterDefaults($form, $options, $filters);
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

        return array_filter(array_map(function (array $data) use ($storage, $listBuilder, $options) {
            $mid = $data['mid'] ?? '';

            if (!$mid) {
                return null;
            }

            $media = $storage->load($mid);

            if (!$media instanceof Media) {
                return null;
            }

            $key = sprintf('media:%s', $media->id());

            /* @var FileInterface $file */
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
                    'class' => ['wmmedia__list__select'],
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
}
