<?php

namespace Drupal\law_groups\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Sets the _admin_route for specific group-related routes.
 */
class GroupAdminRouteSubscriber extends RouteSubscriberBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new GroupAdminRouteSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($this->configFactory->get('group.settings')->get('use_admin_theme')) {
      $routes_to_make_admin = [
        'entity.group.canonical',
        'view.group_members.page_1',
        'view.group_nodes.page_1',
        'view.group_menus.page_1',
      ];
      $routes_to_make_nonadmin = [
        'entity.group_content.create_form',
      ];
      foreach ($collection->all() as $name => $route) {
        // Use the admin theme for Group entity pages.
        if (in_array($name, $routes_to_make_admin)) {
          $route->setOption('_admin_route', TRUE);
        }
        // Use the regular theme for selected Group routes.
        if (in_array($name, $routes_to_make_nonadmin)) {
          $route->setOption('_admin_route', FALSE);
        }
      }
    }
  }

}
