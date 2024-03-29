<?php

namespace Drupal\override_media_options;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\media\Entity\MediaType;

/**
 * Provides dynamic override permissions for medias of different types.
 */
class MediaPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of additional permissions.
   *
   * @return array
   *   An array of permissions.
   */
  public function mediaTypePermissions() {
    $permissions = [];

    if (\Drupal::config('override_media_options.settings')->get('general_permissions')) {
      $this->addGeneralPermissions($permissions);
    }

    if (\Drupal::config('override_media_options.settings')->get('specific_permissions')) {
      $this->addSpecificPermissions($permissions);
    }

    return $permissions;
  }

  /**
   * Add general permissions.
   *
   * @param array $permissions
   *   The permissions array, passed by reference.
   */
  private function addGeneralPermissions(array &$permissions) {
    $permissions['override all media published option'] = [
      'title' => $this->t('Override all published options.'),
    ];

    $permissions['override all media revision option'] = [
      'title' => $this->t('Override all revision option.'),
    ];

    $permissions['override all media authored by option'] = [
      'title' => $this->t('Override all authored by option.'),
    ];

    $permissions['override all media authored on option'] = [
      'title' => $this->t('Override all authored on option.'),
    ];
  }

  /**
   * Add media type specific permissions.
   *
   * @param array $permissions
   *   The permissions array, passed by reference.
   */
  private function addSpecificPermissions(array &$permissions) {
    /** @var Drupal\media\Entity\MediaType $media_type */
    foreach (MediaType::loadMultiple() as $media_type) {
      $type = $media_type->id();
      $label = $media_type->label();

      $permissions["override media $type published option"] = [
        'title' => $this->t("Override %name published option.", ["%name" => $label]),
      ];

      $permissions["override media $type revision option"] = [
        'title' => $this->t("Override %name revision option.", ["%name" => $label]),
      ];

      $permissions["override media $type authored by option"] = [
        'title' => $this->t("Override %name authored by option.", ["%name" => $label]),
      ];

      $permissions["override media $type authored on option"] = [
        'title' => $this->t("Override %name authored on option.", ["%name" => $label]),
      ];

    }
  }

}
