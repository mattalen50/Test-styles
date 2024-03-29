<?php

namespace Drupal\law_editing;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Defines a service provider for the Law School Editing module.
 */
class LawEditingServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Override the menu_position module's active trail, which itself
    // overrides (decorates) the core menu.active_trail service.
    // @see MenuPositionActiveTrail
    $definition = $container->getDefinition('menu_position.menu.active_trail');
    $definition->setClass('Drupal\law_editing\MenuActiveTrail');
  }

}
