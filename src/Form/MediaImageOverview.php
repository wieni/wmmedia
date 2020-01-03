<?php

namespace Drupal\wmmedia\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wmmedia\Service\FormOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MediaImageOverview extends FormBase
{

    /**
     * @var \Drupal\wmmedia\Service\ImageOverviewFormBuilder
     */
    protected $overviewFormBuilder;

    /**
     * @inheritDoc
     */
    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->overviewFormBuilder = $container->get('wmmedia.image.form_builder');
        return $instance;
    }

    /**
     * @inheritDoc
     */
    public function getFormId(): string
    {
        return 'wm_media_content_overview';
    }

    /**
     * @inheritDoc
     */
    public function buildForm(array $form, FormStateInterface $formState): array
    {
        $this->overviewFormBuilder->setFormFilters($form, FormOptions::createForOverview());
        $form['#attached']['library'][] = 'wmmedia/overview';
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $formState): void
    {
    }
}
