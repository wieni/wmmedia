<?php

namespace Drupal\wmmedia\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_browser\Plugin\EntityBrowser\Widget\Upload as FileUpload;
use Drupal\media\MediaInterface;

/**
 * Uses upload to create media images.
 *
 * @EntityBrowserWidget(
 *   id = "wmmedia_media_image_add",
 *   label = @Translation("Media image upload"),
 *   description = @Translation("Upload widget that will create media entities of the uploaded images."),
 *   auto_select = FALSE
 * )
 */
class MediaImageExtrasAdd extends FileUpload
{

    /**
     * {@inheritdoc}
     */
    public function defaultConfiguration()
    {
        return [
                'extensions' => 'jpg jpeg png gif',
                'media_type' => 'image',
                'upload_location' => 's3://[date:custom:Y]-[date:custom:m]',
                'multiple' => true,
                'submit_text' => $this->t('Add'),
            ] + parent::defaultConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters)
    {
        /** @var \Drupal\media\MediaTypeInterface $media_type */
        if (!$this->configuration['media_type'] || !($media_type = $this->entityTypeManager->getStorage('media_type')->load($this->configuration['media_type']))) {
            return ['#markup' => $this->t('The media type is not configured correctly.')];
        }

        if ($media_type->getSource()->getPluginId() != 'imgix') {
            return ['#markup' => $this->t('The configured media type is not using the imgix plugin.')];
        }

        $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

        if (!empty($form['upload'])) {
            $form['upload']['#multiple'] = false;
        }

        $form['title'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#default_value' => null,
            '#size' => 45,
            '#maxlength' => 256,
            '#required' => false,
        ];

        $form['alternate'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Alternate (Alt) text'),
            '#default_value' => null,
            '#size' => 45,
            '#maxlength' => 256,
            '#required' => false,
        ];

        $form['description'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Caption'),
            '#default_value' => null,
            '#size' => 45,
            '#required' => false,
            '#allowed_format_hide_settings' => [
                'hide_guidelines' => 1,
                'hide_help' => 1,
            ],
            '#allowed_formats' => ['plain_text'],
            '#after_build' => ['_allowed_formats_remove_textarea_help'],
        ];

        $form['copyright'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Copyright'),
            '#default_value' => null,
            '#size' => 45,
            '#required' => false,
            '#allowed_format_hide_settings' => [
                'hide_guidelines' => 1,
                'hide_help' => 1,
            ],
            '#allowed_formats' => ['plain_text'],
            '#after_build' => ['_allowed_formats_remove_textarea_help'],
        ];

        $form['upload']['#upload_validators']['file_validate_extensions'] = [$this->configuration['extensions']];

        $form['#attached']['library'][] = 'wmmedia/media.upload';

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareEntities(array $form, FormStateInterface $form_state)
    {
        $files = parent::prepareEntities($form, $form_state);

        /** @var \Drupal\media\MediaTypeInterface $media_type */
        $media_type = $this->entityTypeManager
            ->getStorage('media_type')
            ->load($this->configuration['media_type']);

        $images = [];
        foreach ($files as $file) {
            /** @var \Drupal\media\MediaInterface $image */
            $image = $this->entityTypeManager->getStorage('media')->create([
                'bundle' => $media_type->id(),
                $media_type->getSource()->getConfiguration()['source_field'] => $file,
            ]);
            $images[] = $image;
        }

        return $images;
    }

    /**
     * {@inheritdoc}
     */
    public function submit(array &$element, array &$form, FormStateInterface $form_state)
    {
        if (!empty($form_state->getTriggeringElement()['#eb_widget_main_submit'])) {
            $images = $this->prepareEntities($form, $form_state);
            /** @var MediaInterface $image */
            foreach ($images as $image) {
                $title = $form_state->getValue('title');
                $description = $form_state->getValue('description');
                $copyright = $form_state->getValue('copyright');
                $alternative = $form_state->getValue('alternate');

                $image->set('name', $title);
                $image->set('field_copyright', $copyright);
                $image->set('field_description', $description);
                $image->set('field_alternate', $alternative);

                $image->save();
            }

            $this->selectEntities($images, $form_state);
            $this->clearFormValues($element, $form_state);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);
        $form['extensions'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Allowed extensions'),
            '#default_value' => $this->configuration['extensions'],
            '#required' => true,
        ];

        $media_type_options = [];
        $media_types = $this
            ->entityTypeManager
            ->getStorage('media_type')
            ->loadByProperties();

        foreach ($media_types as $media_type) {
            $media_type_options[$media_type->id()] = $media_type->label();
        }

        if (empty($media_type_options)) {
            $url = Url::fromRoute('entity.media_type.add_form')->toString();
            $form['media_type'] = [
                '#markup' => $this->t("You don't have media type of the Image type. You should <a href='!link'>create one</a>", ['!link' => $url]),
            ];
        } else {
            $form['media_type'] = [
                '#type' => 'select',
                '#title' => $this->t('Media type'),
                '#default_value' => $this->configuration['media_type'],
                '#options' => $media_type_options,
            ];
        }

        return $form;
    }
}
