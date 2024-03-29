<?php

namespace Drupal\law_users\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Law School Users route subscriber.
 */
class LawUsersRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Don't allow use of Drupal core's password reset form.
    if ($route = $collection->get('user.pass')) {
      $route->setRequirements(['_access' => 'FALSE']);
    }
    // Only administrators can edit user accounts.
    if ($route = $collection->get('entity.user.edit_form')) {
      $route->setRequirements(['_permission' => 'administer users']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();

    // Use a lower priority than \Drupal\views\EventSubscriber\RouteSubscriber
    // to ensure the requirement will be added to its routes.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];

    return $events;
  }

}
