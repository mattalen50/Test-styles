<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\ProfileNameFullPronouns.
 */

namespace Drupal\law_profile_name\Plugin\Field\FieldFormatter;

use Drupal\computed_field\Plugin\Field\FieldFormatter\ComputedPhpFormatterBase;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Plugin implementation of the 'Profile Name Full with Pronouns' formatter for computed fields.
 *
 * @FieldFormatter(
 *   id = "computed_profile_name_full_pronouns",
 *   label = @Translation("Profile Name Full with Pronouns"),
 *   field_types = {
 *     "computed_string",
 *     "computed_string_long",
 *   }
 * )
 */

class ProfileNameFullPronouns extends ComputedPhpFormatterBase {

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
    
    $prefix = isset($value['prefix']) ? '<span class="prefix">' . $value['prefix']  . '</span> ' : '';
    $first = '<span class="first-name">' . $value['first'] . '</span> ';
    $last = '<span class="last-name">' . $value['last'] . '</span>';
    $grad = isset($value['grad']) ? ' <span class="grad-year">' . $value['grad']  . '</span>' : '';
    $pronouns = isset($value['pronouns']) ? ' <span class="pronouns">' . $value['pronouns']  . '</span>' : '';
    $name_render = $prefix . $first . $last . $grad . $pronouns;
    
    return '<span class="cf-profile-name-pronouns">' . $name_render . '</span>';
  }
}