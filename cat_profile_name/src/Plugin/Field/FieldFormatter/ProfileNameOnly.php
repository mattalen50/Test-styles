<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\ProfileNameOnly.
 */

namespace Drupal\law_profile_name\Plugin\Field\FieldFormatter;

use Drupal\computed_field\Plugin\Field\FieldFormatter\ComputedPhpFormatterBase;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Plugin implementation of the 'Profile Name' formatter for computed fields.
 *
 * @FieldFormatter(
 *   id = "computed_profile_name_only",
 *   label = @Translation("Profile Name Only"),
 *   field_types = {
 *     "computed_integer",
 *     "computed_decimal",
 *     "computed_float",
 *     "computed_string",
 *     "computed_string_long",
 *   }
 * )
 */

class ProfileNameOnly extends ComputedPhpFormatterBase {

  /**
   * Do something with the value before displaying it.
   *
   * @param mixed $value
   *   The (computed) value of the item.
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The item to format for output
   * @param int $delta
   *   The delta value (index) of the item in case of multiple items
   * @param string $langcode
   *   The language code
   * @return mixed
   */
  public function formatItem($value, FieldItemInterface $item, $delta = 0, $langcode = NULL) {

    $value = unserialize($value);
    
    $first = '<span class="first-name">' . $value['first'] . '</span> ';
    $last = '<span class="last-name">' . $value['last'] . '</span>';
    $name_render = $first . $last;

    return '<span class="cf-profile-name">' . $name_render . '</span>';
  }
}