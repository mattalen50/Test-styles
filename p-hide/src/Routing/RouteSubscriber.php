<?php

namespace Drupal\ums_custom_hesh\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $routes = $collection->all();
    foreach ($routes as $route_name => $route) {
        switch ($route_name) {
            case 'field_ui.field_storage_config_reuse_paragraph':
            case 'field_ui.field_storage_config_add_paragraph':
            case 'entity.paragraph.field_ui_fields':
            case 'entity.entity_form_display.paragraph.default':
            case 'entity.entity_view_display.paragraph.default':
            case 'entity.paragraphs_type.delete_form':
                $route->setRequirements(['_custom_access' => '\Drupal\ums_custom_hesh\Access\ParagraphAdminMenuAccess::menuItemAccess']);
            break;
        }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run after content_translation, which has priority -210.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -220];
    return $events;
  }

}
