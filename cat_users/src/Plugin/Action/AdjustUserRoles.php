<?php

namespace Drupal\law_users\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\law_users\UserRoleAdjuster;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adjust user roles for a user.
 *
 * @Action(
 *   id = "adjust_user_roles",
 *   label = @Translation("Automatically adjust user roles"),
 *   type = "user"
 * )
 */
class AdjustUserRoles extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The UserRoleAdjuster service.
   *
   * @var \Drupal\law_users\UserRoleAdjuster
   */
  protected $adjuster;

  /**
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\law_users\UserRoleAdjuster $adjuster
   *   The adjuster service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserRoleAdjuster $adjuster) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->adjuster = $adjuster;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('law_users.user_role_adjuster')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    try {
      /** @var \Drupal\user\UserInterface $entity */
      $this->adjuster->adjustUserRoles($entity);
      $entity->save();
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
