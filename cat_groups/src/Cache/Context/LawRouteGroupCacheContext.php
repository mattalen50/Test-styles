<?php

namespace Drupal\law_groups\Cache\Context;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Cache\Context\RouteGroupCacheContext as original_RouteGroupCacheContext;
use Drupal\law_groups\Context\GroupRouteContext;

/**
 * Overrides (decorates) Group module's RouteGroupCacheContext.
 *
 * Sets the current group as a cache context on group OR node routes.
 */
class LawRouteGroupCacheContext extends original_RouteGroupCacheContext {

  /**
   * @var \Drupal\law_groups\Context\GroupRouteContext
   */
  protected $groupRouteContext;

  /**
   * Overrides the original constructor.
   *
   * Add our own GroupRouteContext service, which has the "getGroupFromNode"
   * implementation we need.
   */
  public function __construct(RouteMatchInterface $current_route_match, EntityTypeManagerInterface $entity_type_manager, GroupRouteContext $group_route_context) {
    $this->currentRouteMatch = $current_route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->groupRouteContext = $group_route_context;
  }

  /**
   * Get the parent group entity for the currently viewed node.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   */
  public function getGroupFromRoute() {
    return $this->groupRouteContext->getGroupFromNode();
  }

}
