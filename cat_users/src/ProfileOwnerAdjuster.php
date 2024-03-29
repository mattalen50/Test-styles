<?php

namespace Drupal\law_users;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\Error;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * ProfileOwnerAdjuster service.
 */
class ProfileOwnerAdjuster {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ProfileOwnerAdjuster object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Find the corresponding profile node for a user account, and change the
   * profile owner to the user.
   */
  public function adjustByUser(UserInterface $user) {
    try {
      /** @var NodeInterface $profile */
      $profile = $this->getProfileByX500($user->getAccountName());
      if ($profile->getOwnerId() != $user->id()) {
        $profile->setOwner($user);
        $profile->save();
        return TRUE;
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('law_users')
        ->error('%type: @message in %function (line %line of %file).', Error::decodeException($e));
    }
  }

  /**
   * Find the corresponding user account for a profile node, and change the
   * profile owner to the user.
   */
  public function adjustByProfile(NodeInterface $profile, $do_save = TRUE) {
    if (!in_array($profile->getType(), ['profile', 'zoobook'])) {
      return;
    }
    $x500 = $profile->field_x500_reference->value;
    if (!$x500) {
      $this->setProfileOwner($profile, 0, $do_save);
      return;
    }
    $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $x500]);
    if (!$users) {
      $this->setProfileOwner($profile, 0, $do_save);
      return;
    }
    $user = reset($users);
    $this->setProfileOwner($profile, $user->id(), $do_save);
  }

  protected function setProfileOwner(NodeInterface $profile, $user_id, $do_save) {
    if ($profile->getOwnerId() != $user_id) {
      $profile->setOwnerId($user_id);
      if ($do_save) {
        $profile->save();
      }
      return TRUE;
    }
    return NULL;
  }

  /**
   * Get a profile node by its x500 identifier.
   *
   * @param string $x500
   *   The x500 identifier to find a profile for.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\law_users\Exception\MultipleProfilesException
   * @throws \Drupal\law_users\Exception\NoProfileException
   */
  public function getProfileByX500(string $x500) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $nids = $query->accessCheck(FALSE)->condition('field_x500_reference', $x500)->execute();
    if (empty($nids)) {
      throw new Exception\NoProfileException('No profile found for ' . $x500);
    }
    if (count($nids) > 1) {
      throw new Exception\MultipleProfilesException("More than one profile found for $x500: " . implode(', ', $nids));
    }
    $profile = $this->entityTypeManager->getStorage('node')->load(reset($nids));
    return $profile;
  }
}
