<?php

namespace Drupal\law_citations\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fix unwanted NULL values for citation node's field_forthcoming_citation,
 * converting them to 0.
 *
 * @Action(
 *   id = "fix_null_forthcoming_citation",
 *   label = @Translation("Fix NULL in forthcoming citation"),
 *   type = "node"
 * )
 */
class FixNullForthcomingCitation extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {
    try {
      /** @var \Drupal\node\NodeInterface $node */
      if ($node->field_forthcoming_citation->value === NULL) {
//        if (!$node->getTitle()) {
//          return FALSE;
//        }
        $node->field_forthcoming_citation->value = 0;
        // Law Base module allows this "preserve" flag to prevent the entity's
        // changed property from being updated upon save.
        $node->changed->preserve = TRUE;
        $node->save();
      }
    }
    catch (\Exception $e) {
      watchdog_exception('law_citations', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
