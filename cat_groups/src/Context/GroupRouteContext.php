<?php

namespace Drupal\law_groups\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\group\Context\GroupRouteContext as original_GroupRouteContext;

//// TODO: resume "use" of dependency injection once Group 2.x everywhere
// use Drupal\group\Entity\GroupRelationship;
//// End TODO

use Drupal\node\Entity\Node;

/**
 * Overrides (decorates) Group module's GroupRouteContext.
 *
 * Sets the current group as a context on group OR node routes. The original
 * GroupRouteContext only works for group routes.
 */
class GroupRouteContext extends original_GroupRouteContext {
  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $contexts = parent::getRuntimeContexts($unqualified_context_ids);

    // The parent method checks the current route to see if it's a Group route.
    // If it's not, also check if it's a Node route that has a parent group.
    if (!$contexts['group']->hasContextValue() && $group = $this->getGroupFromNode() ?? $this->getGroupFromRoute()) {
      $context_definition = EntityContextDefinition::fromEntityTypeId('group')
        ->setRequired(FALSE);
      $cacheability = new CacheableMetadata();
      $cacheability->setCacheContexts(['url.path']);
      $context = new Context($context_definition, $group);
      $context->addCacheableDependency($cacheability);
      $contexts['group'] = $context;
    }

    return $contexts;
  }

  /**
   * Get the parent group entity for the currently viewed node.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   */
  public function getGroupFromNode() {
    $node = \law_base_get_node();
    if (!$node) {
      return NULL;
    }

    //// TODO: remove conditions & return to "use" dependency injection once
    //// Group 2.x is in place everywhere
    if (class_exists('\Drupal\group\Entity\GroupRelationship')) {
      $group_relation_entities = \Drupal\group\Entity\GroupRelationship::loadByEntity($node);
    }
    // NO: if (class_exists('\Drupal\group\Entity\GroupContent')){
    // Just presume if GroupRelationship doesn't exist, GroupContent does
    else {
      $group_relation_entities = \Drupal\group\Entity\GroupContent::loadByEntity($node);
    }
    //// TODO: resume dependency injection once 2.x is everywhere
    // $group_relation_entities = GroupRelationship::loadByEntity($node);
    //// END TODO

    if (!$group_relation_entities) {
      return NULL;
    }
    $group_relation_entity = reset($group_relation_entities);
    return $group_relation_entity->getGroup();
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    // Layout Builder requires available contexts to provide a value at this point.
    // Seems like a core bug:
    // @see https://www.drupal.org/project/drupal/issues/3194198
    // @see https://www.drupal.org/project/drupal/issues/3099968
    if ($group = $this->getGroupFromNode()) {
      $entityContext = EntityContext::fromEntity($group, $this->t('Group from group or node URL'));
    }
    else {
      $entityContext = EntityContext::fromEntityTypeId('group', $this->t('Group from group or node URL'));
    }
    return ['group' => $entityContext];
  }
}
