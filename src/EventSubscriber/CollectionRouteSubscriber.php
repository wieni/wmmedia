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

    protected function alterRoutes(RouteCollection $collection): void
    {
        $hasDefaultRoute = false;

        $default = [
            'entity.media.collection',
            'view.media.media_page_list',
        ];

        $own = new Route(
            '/admin/content/media',
            ['_controller' => 'Drupal\wmmedia\Controller\GalleryController::show', '_title' => 'Media'],
            ['_permission' => 'access media overview'],
            ['_admin_route' => true]
        );

        foreach ($default as $route) {
            if ($route = $collection->get($route)) {
                $hasDefaultRoute = true;

                $route->setDefaults($own->getDefaults());
                $route->setOptions($own->getOptions());
                $route->setRequirements($own->getRequirements());
                $route->setCondition($own->getCondition());
            }
        }

        if (!$hasDefaultRoute) {
            $collection->add('entity.media.collection', $own);
        }
    }
}
