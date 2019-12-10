<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\hook_event_dispatcher\Event\Form\BaseFormEvent;
use Drupal\media\Entity\Media;
use Drupal\wmmedia\Service\UsageManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MediaUsageFormAlterSubscriber implements EventSubscriberInterface
{

    use StringTranslationTrait;

    /**
     * @var \Drupal\wmmedia\Service\UsageManager
     */
    protected $usageManager;

    public function __construct(UsageManager $usageManager)
    {
        $this->usageManager = $usageManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'hook_event_dispatcher.form_media_file_delete_form.alter' => 'addWarning',
        ];
    }

    public function addWarning(BaseFormEvent $event): void
    {
        $formState = $event->getFormState();
        $callBackObject = $formState->getFormObject();

        if (!$callBackObject instanceof ContentEntityDeleteForm) {
            return;
        }

        $entity = $callBackObject->getEntity();

        if (!$entity instanceof Media) {
            return;
        }

        $form = &$event->getForm();

        if (!isset($form['description']['#markup'])) {
            return;
        }

        $usage = $this->usageManager->getUsage($entity);

        if (empty($usage)) {
            return;
        }

        $form['description']['#markup'] = $this->t('Are you sure you want to delete :file ?  It is still <a href="@link">in use</a>.', [
            ':file' => $entity->label(),
            '@link' => Url::fromRoute('wmmedia.usage', ['media' => $entity->id()])->toString(),
        ]);

        $form['usage'] = $this->usageManager->getUsageAsTable($entity, false);
    }
}
