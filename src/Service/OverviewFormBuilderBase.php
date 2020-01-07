<?php

namespace Drupal\wmmedia\Service;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class OverviewFormBuilderBase
{
    use DependencySerializationTrait;
    use StringTranslationTrait;

    /** @var RequestStack */
    protected $requestStack;
    /** @var RouteMatchInterface */
    protected $routeMatch;

    public function __construct(
        RequestStack $requestStack,
        RouteMatchInterface $routeMatch
    ) {
        $this->requestStack = $requestStack;
        $this->routeMatch = $routeMatch;
    }

    public function filterSubmit(array $form, FormStateInterface $formState): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $triggeringElement = $formState->getTriggeringElement();
        $parents = $triggeringElement['#parents'] ?? [];
        $parent = array_pop($parents);
        $routeName = $this->routeMatch->getRouteName();
        $routeParameters = $this->routeMatch->getParameters()->all();

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
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return $filters;
        }

        foreach (static::getFilterKeys() as $key) {
            $filter = $request->query->get($key);
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

    protected function setFormFilterDefaults(&$form, FormOptions $options, array $filters): void
    {
        if ($options->showUsage()) {
            $form['filters']['in_use'] = [
                '#default_value' => $filters['in_use'] ?? '',
                '#empty_option' => '- ' . $this->t('Any') . ' -',
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
            '#submit' => [[$this, 'filterSubmit']],
            '#type' => 'submit',
            '#value' => $this->t('Search'),
        ];

        $form['filters']['reset'] = [
            '#submit' => [[$this, 'filterSubmit']],
            '#type' => 'submit',
            '#value' => $this->t('Reset'),
        ];
    }
}
