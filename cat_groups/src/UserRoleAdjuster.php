<?php

namespace Drupal\law_groups;

use Drupal\group\GroupMembershipLoader;
use Drupal\user\UserInterface;

/**
 * UserRoleAdjuster service.
 */
class UserRoleAdjuster {

  /**
   * @var \Drupal\group\GroupMembershipLoader
   */
  protected $membership_loader;

  /**
   * Constructor.
   */
  public function __construct(GroupMembershipLoader $membership_loader) {
    $this->membership_loader = $membership_loader;
  }
  /**
   * Check a user account's group memberships to see if the global roles
   * "group_editor" or "group_manager" need to be added or removed.
   */
  public function adjustRoles(UserInterface $account) {
    $memberships = $this->membership_loader->loadByUser($account);
    $roles_to_set = [
      'group_manager' => 0,
      'group_editor' => 0,
    ];
    foreach ($memberships as $membership) {
      foreach($membership->getRoles() as $role_name => $role) {
        switch ($role_name) {
          case 'microsite-publisher':
          case 'institute-manager':
            $roles_to_set['group_manager'] = 1;
            break;
          case 'microsite-editor':
          case 'institute-editor':
            $roles_to_set['group_editor'] = 1;
            break;
        }
      }
    }
    foreach ($roles_to_set as $role_name => $setting) {
      if ($setting) {
        $account->addRole($role_name);
      }
      else {
        $account->removeRole($role_name);
      }
    }

    $account->save();
  }

}
