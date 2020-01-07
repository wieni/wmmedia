<?php

namespace Drupal\wmmedia\Plugin\EntityBrowser\Widget;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\WidgetBase;

class MediaBrowserBase extends WidgetBase
{
    public const BROWSER_KEY = 'entity_browser_select';

    public function validate(array &$form, FormStateInterface $formState): void
    {
        $selectedRows = $this->getSelectionFromUserInput($formState);

        foreach ($selectedRows as $key => $row) {
            if (!is_string($row)) {
                continue;
            }

            $parts = explode(':', $row, 2);

            try {
                $storage = $this->entityTypeManager->getStorage($parts[0]);
                if (!$storage->load($parts[1])) {
                    $message = $this->t('The @type entity @id does not exist.', [
                        '@type' => $parts[0],
                        '@id' => $parts[1],
                    ]);
                    $formState->setError($form['widget'], $message);
                }
            } catch (PluginNotFoundException $e) {
                $message = $this->t('The entity type @type does not exist.', [
                    '@type' => $parts[0],
                ]);
                $formState->setError($form['widget'], $message);
            }
        }

        if (empty($formState->getErrors())) {
            parent::validate($form, $formState);
        }
    }

    public function submit(array &$element, array &$form, FormStateInterface $form_state)
    {
        $entities = $this->prepareEntities($form, $form_state);
        $this->selectEntities($entities, $form_state);
    }

    protected function prepareEntities(array $form, FormStateInterface $formState): array
    {
        $entities = [];

        $userInput = $formState->getUserInput();
        if (empty($userInput[self::BROWSER_KEY])) {
            return $entities;
        }

        $selectedRows = $this->getSelectionFromUserInput($formState);

        if (empty($selectedRows)) {
            return $entities;
        }

        foreach ($selectedRows as $row) {
            [$type, $id] = explode(':', $row);
            $storage = $this->entityTypeManager->getStorage($type);
            if ($entity = $storage->load($id)) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    protected function getSelectionFromUserInput(FormStateInterface $formState): array
    {
        $userInput = $formState->getUserInput();

        if (!isset($userInput[self::BROWSER_KEY])) {
            return [];
        }

        return is_array($userInput[self::BROWSER_KEY])
            ? array_values(array_filter($userInput[self::BROWSER_KEY]))
            : [$userInput[self::BROWSER_KEY]];
    }
}
