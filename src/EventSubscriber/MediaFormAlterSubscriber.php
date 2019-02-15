<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\hook_event_dispatcher\Event\Form\FormBaseAlterEvent;
use Drupal\hook_event_dispatcher\Event\Form\FormIdAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MediaFormAlterSubscriber implements EventSubscriberInterface
{
    /** @var AccountProxyInterface */
    protected $user;

    public function __construct(
        AccountProxyInterface $user
    ) {
        $this->user = $user;
    }

    public static function getSubscribedEvents()
    {
        return [
            'hook_event_dispatcher.form_base_media_form.alter' => 'mediaFormAlter',
            'hook_event_dispatcher.form_entity_browser_images_form.alter' => 'entityBrowserImagesFormAlter',
        ];
    }

    public function mediaFormAlter(FormBaseAlterEvent $event)
    {
        $form = &$event->getForm();

        $this->removeRevisionElement($form);
    }

    public function entityBrowserImagesFormAlter(FormIdAlterEvent $event)
    {
        $form = &$event->getForm();

        if (!isset($form['widget']['entities'])) {
            return;
        }

        $entities = Element::children($form['widget']['entities']);

        if (empty($entities)) {
            return;
        }

        foreach ($entities as $entity) {
            $element = &$form['widget']['entities'][$entity];
            if (
                !isset($element['#type'], $element['#entity_type'])
                || $element['#type'] !== 'inline_entity_form'
                || $element['#entity_type'] !== 'media'
            ) {
                continue;
            }

            $element['#after_build'][] = [self::class, 'entityBrowserImagesFormAfterBuild'];
        }
    }

    public static function entityBrowserImagesFormAfterBuild($form)
    {
        $form['revision_log_message']['#access'] = false;
        return $form;
    }

    protected function removeRevisionElement(&$form)
    {
        if (isset($form['revision'])) {
            $form['revision']['#access'] = false;
        }

        if (isset($form['revision_information'])) {
            $form['revision_information']['#access'] = false;
        }

        if (isset($form['revision_log'])) {
            $form['revision_log']['#access'] = false;
        }
    }
}
