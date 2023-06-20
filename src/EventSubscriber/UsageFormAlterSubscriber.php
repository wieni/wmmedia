<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Drupal\wmmedia\Service\UsageManager;

/**
 * called in @see wmmedia_form_media_file_delete_form_alter()
 */
class UsageFormAlterSubscriber
{
    use StringTranslationTrait;

    /** @var UsageManager */
    protected $usageManager;

    public function __construct(UsageManager $usageManager)
    {
        $this->usageManager = $usageManager;
    }

    public function addWarning(array &$form, FormStateInterface $formState): void
    {
        $callBackObject = $formState->getFormObject();

        if (!$callBackObject instanceof ContentEntityDeleteForm) {
            return;
        }

        $entity = $callBackObject->getEntity();

        if (!$entity instanceof MediaInterface) {
            return;
        }

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
