<?php

namespace Drupal\law_text_filters\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "filter_law_text_italics",
 *   title = @Translation("Law Italics Filter"),
 *   description = @Translation("Convert all italics tags to em tags."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class FilterLawTextItalics extends FilterBase {
  public function process($text, $langcode) {
    $tags = [
      '<i>' => '<em>',
      '</i>' => '</em>'
    ];

    return new FilterProcessResult(str_ireplace(array_keys($tags), array_values($tags), $text));
  }
}

