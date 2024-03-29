<?php

namespace Drupal\law_base;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * NodeManager service.
 */
class NodeManager {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a NodeManager object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get the node entity from the current route, or FALSE if none.
   *
   * @return NodeInterface|bool
   */
  public function getViewedNode() {
    // Check if we're viewing a node revision first.
    $revision = $this->routeMatch->getParameter('node_revision');
    if (is_numeric($revision)) {
      return $this->entityTypeManager->getStorage('node')->loadRevision($revision);
    }
    if ($revision instanceof NodeInterface) {
      return $revision;
    }
    // The 'node' parameter may be a NodeInterface object or a string numeric ID.
    $node = $this->routeMatch->getParameter('node');
    if (!empty($node) && is_numeric($node)) {
      $node = Node::load($node);
    }
    if ($node instanceof NodeInterface) {
      return $node;
    }
    return FALSE;
  }
}
