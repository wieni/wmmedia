<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\file\FileInterface;
use Drupal\media\Entity\MediaType;
use Drupal\media\MediaSourceInterface;
use Drupal\media\MediaTypeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ImageOverviewFormBuilder extends OverviewFormBuilderBase
{
    protected const SIZE_SMALL = 'small';
    protected const SIZE_MEDIUM = 'medium';
    protected const SIZE_LARGE = 'large';

    /** @var EntityFieldManagerInterface */
    protected $entityFieldManager;
    /** @var EntityTypeManagerInterface */
    protected $entityTypeManager;
    /** @var ImageRepository */
    protected $imageRepository;

    public function __construct(
        RequestStack $requestStack,
        RouteMatchInterface $routeMatch,
        ImageRepository $imageRepository,
        EntityTypeManagerInterface $entityTypeManager,
        EntityFieldManagerInterface $entityFieldManager
    ) {
        parent::__construct($requestStack, $routeMatch);
        $this->imageRepository = $imageRepository;
        $this->entityTypeManager = $entityTypeManager;
        $this->entityFieldManager = $entityFieldManager;
    }

    public function setForm(array &$form, FormOptions $options, ?array $configuration = null): void
    {
        $this->setFormContainer($form, $options);

        $filters = $this->getFilters();
        $images = $this->imageRepository->getImages($filters);

        $form['container']['list'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['wmmedia__list'],
            ],
        ];

        foreach ($images as $image) {
            if (empty($image['mid'])) {
                continue;
            }

            $key = sprintf('media:%s', $image['mid']);

            $form['container']['list'][$key] = [
                '#type' => 'container',
                '#attributes' => [
                    'class' => 'wmmedia__list__select',
                ],
            ];

            $sourceField = $this->getSourceField();
            $sourceFieldColumn = sprintf('%s_target_id', $sourceField);

            if (isset($image[$sourceFieldColumn])) {
                $file = $this->entityTypeManager
                    ->getStorage('file')
                    ->load($image[$sourceFieldColumn]);

                if ($file instanceof FileInterface) {
                    $form['container']['list'][$key]['preview'] = [
                        '#weight' => -10,
                        '#theme' => 'image_style',
                        '#style_name' => $configuration['image_style'] ?? 'thumbnail',
                        '#uri' => $file->getFileUri(),
                        '#title' => '',
                        '#caption' => '',
                    ];
                }
            }

            $form['container']['list'][$key]['checkbox'] = [
                '#type' => 'checkbox',
                '#title' => ' ',
                '#title_display' => 'after',
                '#return_value' => $key,
                '#attributes' => ['name' => "entity_browser_select[$key]"],
                '#default_value' => null,
            ];

            $form['container']['list'][$key]['file'] = [
                '#markup' => '<p>' . $image['name'] . '</p>',
            ];

            if (isset($image['field_width'], $image['field_height'])) {
                $form['container']['list'][$key]['dimensions'] = [
                    '#markup' => sprintf('<p>%s x %s</p>', $image['field_width'], $image['field_height']),
                ];
            }
        }

        $form['container']['pager'] = [
            '#type' => 'pager',
        ];
    }

    public function setFormFilters(array &$form, FormOptions $options): void
    {
        $filters = $this->getFilters();

        $form['filters'] = [
            '#attributes' => [
                'class' => ['wmmedia__filters'],
            ],
            '#tree' => true,
            '#type' => 'container',
        ];

        $enabledFields = [$this->t('title', [], ['Field to search in on wmmedia image overview'])];
        $fieldStorages = $this->entityFieldManager->getFieldStorageDefinitions('media');

        if (isset($fieldStorages['field_copyright'])) {
            $enabledFields[] = $this->t('copyright', [], ['Field to search in on wmmedia image overview']);
        }

        if (isset($fieldStorages['field_description'])) {
            $enabledFields[] = $this->t('description', [], ['Field to search in on wmmedia image overview']);
        }

        $form['filters']['search'] = [
            '#attributes' => [
                'class' => ['wmmedia__filters__search'],
                'placeholder' => sprintf('%s, ...', implode(', ', $enabledFields)),
            ],
            '#default_value' => $filters['search'] ?? '',
            '#title' => $this->t('Search'),
            '#type' => 'textfield',
        ];

        $form['filters']['size'] = [
            '#attributes' => [
                'class' => ['wmmedia__filters__size'],
            ],
            '#default_value' => $filters['size'] ?? '',
            '#empty_option' => '- ' . $this->t('Any') . ' -',
            '#options' => static::getMediaSizeOptions(),
            '#title' => $this->t('Image size'),
            '#type' => 'select',
        ];

        $this->setFormFilterDefaults($form, $options, $filters);
    }

    public function getImages(): array
    {
        $filters = $this->getFilters();
        return $this->imageRepository->getImages($filters);
    }

    public function getImagesCount(): int
    {
        $filters = $this->getFilters();
        return $this->imageRepository->getImagesCount($filters);
    }

    protected function getSourceField(): string
    {
        /** @var MediaTypeInterface $mediaType */
        $mediaType = MediaType::load('image');
        /** @var MediaSourceInterface $source */
        $source = $mediaType->getSource();

        return $source->getConfiguration()['source_field'];
    }

    public static function getFilterKeys(): array
    {
        return [
            'search',
            'size',
            'in_use',
        ];
    }

    public static function getMediaSizeOptions(): array
    {
        $result = [];
        $labels = array_map(
            static function ($size, $label) {
                [$min, $max] = $size;

                if (is_int($min) && is_int($max)) {
                    return "$label (> $min, < $max)";
                }

                if (is_int($min)) {
                    return "$label (> $min)";
                }

                if (is_int($max)) {
                    return "$label (< $max)";
                }

                return $label;
            },
            self::getMediaSizes(),
            self::getMediaSizeLabels()
        );

        foreach ($labels as $index => $label) {
            $key = array_keys(self::getMediaSizes())[$index];
            $result[$key] = $label;
        }

        return $result;
    }

    public static function getMediaSizes(): array
    {
        return [
            self::SIZE_SMALL => [null, 300],
            self::SIZE_MEDIUM => [300, 600],
            self::SIZE_LARGE => [600, null],
        ];
    }

    public static function getMediaSizeLabels(): array
    {
        return [
            self::SIZE_SMALL => t('Small'),
            self::SIZE_MEDIUM => t('Medium'),
            self::SIZE_LARGE => t('Large'),
        ];
    }
}
