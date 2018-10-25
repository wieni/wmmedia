<?php

namespace Drupal\wmmedia\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class CollectionRouteSubscriber extends RouteSubscriberBase
{
    public static function getSubscribedEvents()
    {
        return [
            RoutingEvents::ALTER => ['onAlterRoutes', -9999],
        ];
    }

    protected function alterRoutes(RouteCollection $collection)
    {
        $own = new Route(
            '/admin/content/media',
            ['_controller' => 'Drupal\wmmedia\Controller\GalleryController::show', '_title' => 'Media'],
            ['_permission' => 'access media overview'],
            ['_admin_route' => true]
        );

        if ($core = $collection->get('entity.media.collection')) {
            $core->setDefaults($own->getDefaults());
            $core->setOptions($own->getOptions());
            $core->setRequirements($own->getRequirements());
            $core->setCondition($own->getCondition());
        } else {
            $collection->add('entity.media.collection', $own);
        }
    }
}
