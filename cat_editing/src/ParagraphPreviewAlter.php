<?php

namespace Drupal\law_editing;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Trusted callback container for #post_render operations.
 */
class ParagraphPreviewAlter implements TrustedCallbackInterface {

  /**
   * @inheritdoc
   */
  public static function trustedCallbacks() {
    return ['convertFormTags'];
  }

  /**
   * Convert <form> elements to <div> elements, and disables inputs.'
   *
   * @param $element
   *
   * @return array|string|string[]
   */
  public static function convertFormTags($element) {
    $element = str_replace('<form', '<div', $element);
    $element = str_replace('form>', 'div>', $element);
    $element = str_replace('<input', '<input disabled ', $element);
    $element = str_replace('<select', '<select disabled ', $element);
    $element = str_replace('<textarea', '<textarea disabled ', $element);
    return $element;
  }
}
