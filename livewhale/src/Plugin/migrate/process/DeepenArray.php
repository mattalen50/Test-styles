<?php

namespace Drupal\umn_livewhale_event_importer\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

/**
 * Transforms each string in an array into an associative array.
 *
 * Example:
 * source:
 *   - tag1
 *   - tag2
 * becomes
 *   - value: tag1
 *   - value: tag2
 *
 * @MigrateProcessPlugin(
 *   id = "deepen_array"
 * )
 */
class DeepenArray extends ProcessPluginBase {

  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Return an empty string or another default value if the input is null or empty
    if (empty($value)) {
      return '';
    }

    // Ensure that the input is an array
    if (!is_array($value)) {
      throw new MigrateException('Input for DeepenArray plugin is not an array.');
    }

    $keyname = !empty($this->configuration['keyname']) ? $this->configuration['keyname'] : 'value';
    $result = [];

    foreach ($value as $sub_value) {
      if (!is_scalar($sub_value)) {
        throw new MigrateException('All elements of the input array must be scalar values.');
      }
      $result[] = [$keyname => $sub_value];
    }

    return $result;
  }
}
