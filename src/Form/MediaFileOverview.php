<?php

namespace Drupal\wmmedia\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wmmedia\Service\FileOverviewFormBuilder;
use Drupal\wmmedia\Service\FormOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MediaFileOverview implements FormInterface, ContainerInjectionInterface
{
    /** @var FileOverviewFormBuilder */
    protected $overviewFormBuilder;

    public static function create(ContainerInterface $container)
    {
        $instance = new static();
        $instance->overviewFormBuilder = $container->get('wmmedia.file.form_builder');

        return $instance;
    }

    public function getFormId(): string
    {
        return 'wmmedia_file_overview';
    }

    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $this->overviewFormBuilder->setForm($form, FormOptions::createForOverview());
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
