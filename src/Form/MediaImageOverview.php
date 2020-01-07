<?php

namespace Drupal\wmmedia\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wmmedia\Service\FormOptions;
use Drupal\wmmedia\Service\ImageOverviewFormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MediaImageOverview implements FormInterface, ContainerInjectionInterface
{
    /** @var ImageOverviewFormBuilder */
    protected $overviewFormBuilder;

    public static function create(ContainerInterface $container)
    {
        $instance = new static();
        $instance->overviewFormBuilder = $container->get('wmmedia.image.form_builder');

        return $instance;
    }

    public function getFormId(): string
    {
        return 'wm_media_content_overview';
    }

    public function buildForm(array $form, FormStateInterface $formState): array
    {
        $this->overviewFormBuilder->setFormFilters($form, FormOptions::createForOverview());
        $form['#attached']['library'][] = 'wmmedia/overview';

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $formState): void
    {
    }

    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
    }
}
