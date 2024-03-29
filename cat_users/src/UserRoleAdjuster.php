<?php

namespace Drupal\law_users;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\law_users\Exception\MultipleProfilesException;
use Drupal\law_users\Exception\NoGradYearException;
use Drupal\node\Entity\Node;
use Drupal\user\UserInterface;

class UserRoleAdjuster {

  use StringTranslationTrait;

  /**
   * @var \Drupal\node\NodeInterface|null
   */
  private $profile;

  /**
   * @var \Drupal\node\NodeInterface|null
   */
  private $zoobook;

  /**
   * Adjust the user's basic roles, according to the properties of its
   * corresponding Profile and/or Zoobook nodes.
   *
   * This adjusts the user object, but does not save it. It's the responsibility
   * of the calling code to save the entity.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user entity whose roles to adjust.
   *
   * @throws \Drupal\law_users\Exception\MultipleProfilesException
   * @throws \Drupal\law_users\Exception\NoGradYearException
   */
  public function adjustUserRoles(UserInterface $account) {
    $this->profile = $this->getProfileEntity($account, 'profile');
    $this->zoobook = $this->getProfileEntity($account, 'zoobook');
    if (!$this->profile && !$this->zoobook) {
      return;
    }

    if ($this->isProfileFaculty()) {
      $account->addRole('faculty');
    }
    else {
      $account->removeRole('faculty');
    }

    if ($this->isProfileStaff()) {
      $account->addRole('staff');
    }
    else {
      $account->removeRole('staff');
    }

    if ($this->isProfileStudent()) {
      $account->addRole('student');
    }
    else {
      $account->removeRole('student');
    }

    if ($this->isProfileAlumni()) {
      $account->addRole('alumni');
    }
    else {
      $account->removeRole('alumni');
    }
  }

  /**
   * Look up the corresponding profile node associated with a user entity.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user entity to get a corresponding profile from.
   * @param $type
   *   The bundle name of the type of profile to fetch, either 'profile' or 'zoobook'.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The profile node, or NULL if no profile node could be found.
   * @throws \Drupal\law_users\Exception\MultipleProfilesException
   */
  public function getProfileEntity(UserInterface $account, $type) {
    $results = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', $type)
      ->condition('status', 1)
      ->condition('field_x500_reference', $account->getAccountName())
      ->execute();
    if (!count($results)) {
      return NULL;
    }
    if (count($results) > 1) {
      throw new MultipleProfilesException($this->t('More than one @type has the value "@name" for field_x500_reference: node ids @ids', ['@type' => $type, '@name' => $account->getAccountName(), '@ids' => implode(', ', $results)]));
    }
    $profile_nid = reset($results);
    return Node::load($profile_nid);
  }

  /**
   * Determine if the account profile represents a Staff person.
   *
   * @return bool
   */
  protected function isProfileStaff() {
    if (!empty($this->profile->field_staff_role_override->value)) {
      return TRUE;
    }
    if (!empty($this->profile->field_role_staff->value)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Determine if the account profile represents a Faculty person.
   *
   * @return bool
   */
  protected function isProfileFaculty() {
    if (!empty($this->profile->field_faculty_role_override->value)) {
      return TRUE;
    }
    if (!empty($this->profile->field_role_faculty->value)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Determine if the account profile represents a Student person.
   *
   * @return bool
   * @throws \Drupal\law_users\Exception\NoGradYearException
   */
  protected function isProfileStudent() {
    if (!empty($this->profile->field_student_role_override->value)) {
      return TRUE;
    }
    if (empty($this->zoobook)) {
      return FALSE;
    }
    if (empty($this->zoobook->field_graduation_year->entity)) {
      throw new NoGradYearException($this->t('Zoobook @nid does not have a grad year, making it impossible to determine if the user is a current Student or an Alumni.', [
        '@nid' => $this->zoobook->id()
      ]));
    }
    if ($this->zoobook->field_graduation_year->entity->field_hide_year->value == 0) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Determine if the account profile represents an Alumni person.
   *
   * @return bool
   * @throws \Drupal\law_users\Exception\NoGradYearException
   */
  protected function isProfileAlumni() {
    if (!empty($this->profile->field_alumni_role_override->value)) {
      return TRUE;
    }
    if (empty($this->zoobook)) {
      return FALSE;
    }
    if (empty($this->zoobook->field_graduation_year->entity)) {
      throw new NoGradYearException($this->t('Profile @nid does not have a grad year, making it impossible to determine if the user is a current Student or an Alumni.', [
        '@nid' => $this->zoobook->id()
      ]));
    }
    if ($this->zoobook->field_graduation_year->entity->field_hide_year->value == 1) {
      return TRUE;
    }
    return FALSE;
  }

}
