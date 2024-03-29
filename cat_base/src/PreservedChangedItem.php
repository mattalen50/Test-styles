<?php

namespace Drupal\law_base;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\ChangedItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a 'changed' field type class that knows to preserve the timestamp.
 *
 * Adapted from https://www.drupal.org/project/preserve_changed
 */
class PreservedChangedItem extends ChangedItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['preserve'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Determines if the changed timestamp should be preserved.'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if ($this->preserve) {
      return;
    }
    parent::preSave();
  }

}
