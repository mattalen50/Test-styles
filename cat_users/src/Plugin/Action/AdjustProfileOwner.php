<?php

namespace Drupal\law_users\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\law_users\ProfileOwnerAdjuster;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adjust the owner of a profile.
 *
 * @Action(
 *   id = "adjust_profile_owner",
 *   label = @Translation("Adjust profile ownership"),
 *   type = "node"
 * )
 */
class AdjustProfileOwner extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The ProfileOwnerAdjuster service.
   *
   * @var \Drupal\law_users\ProfileOwnerAdjuster
   */
  protected $adjuster;

  /**
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\law_users\ProfileOwnerAdjuster $adjuster
   *   The adjuster service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ProfileOwnerAdjuster $adjuster) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->adjuster = $adjuster;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('law_users.profile_owner_adjuster')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    try {
      /** @var \Drupal\node\NodeInterface $entity */
      $this->adjuster->adjustByProfile($entity);
    }
    catch (\Exception $e) {
      watchdog_exception('law_users', $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return TRUE;
  }

}
