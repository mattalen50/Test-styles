<?php

namespace Drupal\ums_custom_hesh\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Checks access for displaying administer menu pages.
 */
class ParagraphAdminMenuAccess {

  /**
   * {@inheritdoc}
   */
  public function menuItemAccess(AccountInterface $account, MenuLinkContent $menu_link_content = NULL) {
    $paragraphs_type = \Drupal::routeMatch()->getParameter('paragraphs_type');
    $paragraph_type_editability = $paragraphs_type->getThirdPartySetting('ums_custom_hesh', 'read_only_option');
    if ($paragraph_type_editability) {
      return AccessResult::forbidden();
    } else {
      // @TODO: Identify why neutral doesn't work
      return AccessResult::allowed();
    }
  }

}
