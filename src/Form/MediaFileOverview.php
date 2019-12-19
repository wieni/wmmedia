<?php

namespace Drupal\wmmedia\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\wmmedia\Service\FormOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MediaFileOverview extends FormBase
{

    /**
     * @var \Drupal\wmmedia\Service\FileOverviewFormBuilder
     */
    protected $overviewFormBuilder;

    /**
     * @inheritDoc
     */
    public static function create(ContainerInterface $container): FormBase
    {
        $instance = parent::create($container);
        $instance->overviewFormBuilder = $container->get('wmmedia.file.form_builder');
        return $instance;
    }

    /**
     * @inheritDoc
     */
    public function getFormId(): string
    {
        return 'wmmedia_file_overview';
    }

    /**
     * @inheritDoc
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $this->overviewFormBuilder->setForm($form, FormOptions::createForOverview());
        return $form;
    }

    /**
     * @inheritDoc
     */
    public function submitForm(array &$form, FormStateInterface $formState): void
    {
    }
}
