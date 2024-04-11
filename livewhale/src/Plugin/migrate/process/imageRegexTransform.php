<?php

namespace Drupal\umn_livewhale_event_importer\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Take the event image JSON and extract the largest asset URL for import.
 *
 * @MigrateProcessPlugin(
 *   id = "imageRegexTransform"
 * )
 */
class imageRegexTransform extends ProcessPluginBase {

  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Check if the input is empty
    if (empty($value)) {
      return '';
    }

    // Regex pattern to extract the 3x image URL
    $pattern = '/(?<=2x, )(.*)(?= 3x)/';

    // Perform regex match
    if (preg_match($pattern, $value, $matches)) {
      // Extract the URL (first captured group)
      $url = explode(' ', $matches[1])[0]; // Split by space and get the URL part

      // Remove the 'scale/3x/', 'height/\d+/', and 'src_region/\d+,\d+,\d+,\d+/' parts
      $url = preg_replace('/scale\/3x\//', '', $url);
      $url = preg_replace('/height\/\d+\//', '', $url);
      $url = preg_replace('/src_region\/\d+,\d+,\d+,\d+\//', '', $url);

      // Change any width value to 320
      $url = preg_replace('/width\/\d+/', 'width/320', $url);

      return $url;
    }

    // Return null or a default value if no match is found
    return '';
  }

}
