<?php

namespace Drupal\wmmedia\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\wmmedia\Service\MediaFilterService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class MediaContentFilterForm extends FormBase
{
    /** @var SessionInterface */
    protected $session;

    public function __construct(
        SessionInterface $session
    ) {
        $this->session = $session;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('session')
        );
    }

    public static function getSessionVarName()
    {
        return 'wm_media_content_overview';
    }

    public function getFormId()
    {
        return 'wm_media_content_overview';
    }

    public function buildForm(array $form, FormStateInterface $formState)
    {
        $stored = $this->session->get(static::getSessionVarName());

        $form['search'] = [
            '#type' => 'textfield',
            '#title' => 'Search',
            '#attributes' => [
                'placeholder' => $this->t('Filename, title, description, copyright, ...'),
            ],
        ];

        $form['size'] = [
            '#type' => 'select',
            '#title' => 'Size',
            '#options' => MediaFilterService::getMediaSizeOptions(),
            '#empty_option' => t('None'),
            '#empty_value' => '',
        ];

        foreach (Element::children($form) as $k) {
            if (isset($stored[$k])) {
                $form[$k]['#default_value'] = $form[$k]['#type'] === 'select' ?
                    [$stored[$k] => $stored[$k]] : $stored[$k];
            }
        }

        $form['actions']['wrapper'] = [
            '#type' => 'container',
        ];

        $form['actions']['wrapper']['submit'] = [
            '#type' => 'submit',
            '#value' => 'Search',
        ];

        $form['actions']['wrapper']['reset'] = [
            '#type' => 'submit',
            '#attributes' => ['class' => ['reset']],
            '#value' => 'Reset',
            '#submit' => [[$this, 'reset']],
        ];

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $formState)
    {
        $values = $formState->getValues();
        unset(
            $values['form_build_id'],
            $values['form_token'],
            $values['form_id'],
            $values['submit'],
            $values['reset'],
            $values['op']
        );

        $this->session->set(static::getSessionVarName(), $values);
    }

    public function reset()
    {
        $this->session->set(static::getSessionVarName(), []);
    }
}
