<?php

namespace Drupal\law_editing;

use Drupal\menu_position\Menu\MenuPositionActiveTrail;

/**
 * Overrides Drupal\menu_position\Menu\MenuPositionActiveTrail, which
 * overrides Drupal\Core\Menu\MenuActiveTrail.
 *
 * On a node edit page, we want to represent the current active trail as if we
 * were on the node's view page. This is so that Menu Blocks in the Layout
 * Paragraphs interface will show the correct menu context.
 */
class MenuActiveTrail extends MenuPositionActiveTrail {

  /**
   * {@inheritdoc}
   */
  public function getActiveLink($menu_name = NULL) {
    $active_link = parent::getActiveLink($menu_name);
    // If the Menu Position service found an active link, use it.
    if ($active_link) {
      return $active_link;
    }
    // If we're on an edit form, pretend we're on the canonical entity page instead.
    $route_name = $this->routeMatch->getRouteName();
    if ($route_name == 'entity.node.edit_form') {
      $route_name = 'entity.node.canonical';
      $route_parameters = $this->routeMatch->getRawParameters()->all();
      $links = $this->menuLinkManager->loadLinksByRoute($route_name, $route_parameters, $menu_name);
      if ($links) {
        return reset($links);
      }
    }
    // Otherwise, bail.
    return NULL;
  }

}
