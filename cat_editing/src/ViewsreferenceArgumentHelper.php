<?php

namespace Drupal\law_editing;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * ViewsreferenceArgumentHelper service.
 */
class ViewsreferenceArgumentHelper {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a ViewsreferenceArgumentHelper object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(Connection $connection, CacheBackendInterface $cache, EntityFieldManagerInterface $entity_field_manager) {
    $this->connection = $connection;
    $this->cache = $cache;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Gets an option list from an entity field.
   *
   * @param string $entity_type
   * @param string $field_name
   *
   * @return array|false
   *   An array of valid options, keyed by ID, or FALSE if the field is unsupported.
   */
  public function getOptionsFromEntityField(string $entity_type, string $field_name) {
    $cid = "entity_field_options:$entity_type:$field_name";
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }
    $definition = $this->getFieldDefinition($entity_type, $field_name);
    if ($definition->getType() !== 'entity_reference') {
      return FALSE;
    }
    $settings = $definition->getSettings();
    $target_entity_type = explode(':', $settings['handler'])[1];
    if ($target_entity_type !== 'taxonomy_term') {
      return FALSE; // @todo use EntityQuery to support other kinds of references.
    }
    $target_bundles = $settings['handler_settings']['target_bundles'];

    // Get the options list.
    $query = $this->connection->select('taxonomy_term_field_data', 'tax');
    $query->addField('tax', 'tid', 'tid');
    $query->addField('tax', 'name', 'name');
    $query->condition('tax.status', 1);
    $query->condition('tax.vid', $target_bundles, 'IN');
    $query->orderBy('tax.name');
    $options = $query->execute()->fetchAllKeyed();
    $this->cache->set($cid, $options, CacheBackendInterface::CACHE_PERMANENT, ['taxonomy_term_list']);
    return $options;
  }

  /**
   * Get a set of Views Argument FAPI options for aggregator feed arguments.
   */
  public function getOptionsForAggregatorFeeds() {
    $query = $this->connection->select('aggregator_feed', 'a');
    $query->fields('a', ['fid', 'title']);
    $query->orderBy('title');
    return $query->execute()->fetchAllKeyed();
  }

  public function getFieldName(string $entity_type, string $field_name) {
    $definition = $this->getFieldDefinition($entity_type, $field_name);
    return $definition->getLabel();
  }

  /**
   * Get the definition for a given field.
   *
   * @param string $entity_type
   * @param string $field_name
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|false
   */
  protected function getFieldDefinition(string $entity_type, string $field_name) {
    $field_map = $this->entityFieldManager->getFieldMap();
    if (empty($field_map[$entity_type][$field_name]['bundles'])) {
      return FALSE;
    }
    // Pick an arbitary bundle so that we can load the field definition, then
    // get the target entity type and bundles for the entity reference.
    $bundle = reset($field_map[$entity_type][$field_name]['bundles']);
    return $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle)[$field_name];
  }

}
