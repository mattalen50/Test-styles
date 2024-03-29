<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldFormatter\FacultyProfileNameOnly.
 */

namespace Drupal\law_profile_name\Plugin\Field\FieldFormatter;

use Drupal\computed_field\Plugin\Field\FieldFormatter\ComputedPhpFormatterBase;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Plugin implementation of the 'Faculty Profile Name' formatter for computed fields.
 *
 * @FieldFormatter(
 *   id = "computed_faculty_profile_name_only",
 *   label = @Translation("Linked Profile Name Only"),
 *   field_types = {
 *     "computed_string",
 *     "computed_string_long",
 *   }
 * )
 */

class FacultyProfileNameOnly extends ComputedPhpFormatterBase {

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

    $display_name = $first . $last;
    $linked = '<a href="' . $value['alias'] . '">' . $display_name . '</a>';

    $is_faculty = $value['is_faculty'] ?? false;
    $published = $value['published'] ?? false;
    $suppress = $value['suppress'] ?? false;
    $name_render = $published && $is_faculty && !$suppress ? $linked : $display_name;
    
    return '<span class="cf-linked-profile-name">' . $name_render . '</span>';
  }
}