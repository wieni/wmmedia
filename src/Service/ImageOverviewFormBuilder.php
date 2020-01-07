<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\file\FileInterface;
use Drupal\imgix\ImgixManagerInterface;
use Symfony\Component\HttpFoundation\Request;
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
    /** @var ImgixManagerInterface */
    protected $imgixManager;
    /** @var Request|null */
    protected $request;
    /** @var RouteMatchInterface */
    protected $routeMatch;

    public function __construct(
        ImageRepository $imageRepository,
        ImgixManagerInterface $imgixManager,
        EntityTypeManagerInterface $entityTypeManager,
        EntityFieldManagerInterface $entityFieldManager,
        RequestStack $requestStack,
        RouteMatchInterface $routeMatch
    ) {
        $this->imageRepository = $imageRepository;
        $this->imgixManager = $imgixManager;
        $this->entityTypeManager = $entityTypeManager;
        $this->entityFieldManager = $entityFieldManager;
        $this->request = $requestStack->getCurrentRequest();
        $this->routeMatch = $routeMatch;
    }

    public function setForm(array &$form, FormOptions $options, ?array $configuration = null): void
    {
        $this->setFormContainer($form, $options);

        $presets = $this->imgixManager->getPresets();
        $parameters = [];

        if (!empty($presets[$configuration['preset']])) {
            parse_str($presets[$configuration['preset']]['query'], $parameters);
        }

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

            if ($image['field_media_imgix_target_id']) {
                /* @var FileInterface $file */
                $file = $this->entityTypeManager->getStorage('file')->load($image['field_media_imgix_target_id']);
                if ($file) {
                    $form['container']['list'][$key]['preview'] = [
                        '#weight' => -10,
                        '#theme' => 'imgix_image',
                        '#url' => $this->imgixManager->getImgixUrl($file, $parameters),
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

            if ($image['field_width_value']) {
                $form['container']['list'][$key]['dimensions'] = [
                    '#markup' => sprintf('<p>%s x %s</p>', $image['field_width_value'], $image['field_height_value']),
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
                'class' => ['container-inline', 'wmmedia__filters'],
            ],
            '#tree' => true,
            '#type' => 'container',
        ];

        $enabledFields = ['title'];
        $fieldStorages = $this->entityFieldManager->getFieldStorageDefinitions('media');

        if (isset($fieldStorages['field_copyright'])) {
            $enabledFields[] = 'copyright';
        }

        if (isset($fieldStorages['field_description'])) {
            $enabledFields[] = 'description';
        }

        $form['filters']['search'] = [
            '#attributes' => [
                'class' => ['wmmedia__filters__search'],
                'placeholder' => $this->t(sprintf('%s, ...', implode(', ', $enabledFields))),
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
