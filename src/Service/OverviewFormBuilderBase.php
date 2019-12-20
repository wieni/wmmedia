<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

abstract class OverviewFormBuilderBase
{

    use StringTranslationTrait;

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
            $reset = array_merge(['page'], static::getFilterKeys());
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

    abstract public static function getFilterKeys(): array;

    protected function getFilters(): array
    {
        $filters = [];

        if (!$this->request) {
            return $filters;
        }

        foreach (static::getFilterKeys() as $key) {
            $filter = $this->request->query->get($key);
            if ($filter) {
                $filters[$key] = $filter;
            }
        }

        return $filters;
    }

    protected function setFormContainer(array &$form, FormOptions $options): void
    {
        $form['options'] = [
            '#type' => 'value',
            '#value' => $options,
        ];

        $form['container'] = [
            '#attributes' => [
                'class' => ['wmmedia', 'wmmedia--' . $options->getContext()],
                'data-multiple' => $options->isMultiple(),
            ],
            '#type' => 'container',
        ];

        $this->setFormFilters($form['container'], $options);
    }

    protected function setFormFilterDefaults(&$form, FormOptions $options): void
    {
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
                'class' => ['wmmedia__filters__submit'],
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
}
