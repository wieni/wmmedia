<?php

namespace Drupal\wmmedia\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @QueueWorker(
 *     id = \Drupal\wmmedia\Plugin\QueueWorker\MediaUsageQueueWorker::ID,
 *     title = @Translation("Media usage"),
 *     cron = {"time" = 30}
 * )
 */
class MediaUsageQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface
{

    public const ID = 'wmmedia.usage';

    /**
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var \Drupal\wmmedia\Service\UsageManager
     */
    protected $usageManager;

    /**
     * @inheritDoc
     */
    public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition)
    {
        $instance = new static($configuration, $pluginId, $pluginDefinition);
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->usageManager = $container->get('wmmedia.usage');
        return $instance;
    }

    /**
     * @inheritDoc
     */
    public function processItem($data)
    {
        if (empty($data['type']) || empty($data['id'])) {
            return;
        }

        $storage = $this->entityTypeManager->getStorage($data['type']);
        $entity = $storage->load($data['id']);

        if (!$entity instanceof EntityInterface) {
            return;
        }

        $this->usageManager->track($entity);

        if (!$entity instanceof TranslatableInterface) {
            return;
        }

        foreach ($entity->getTranslationLanguages() as $language) {
            $translation = $entity->getTranslation($language->getId());
            $this->usageManager->track($translation);
        }
    }
}
