<?php

namespace Drupal\law_news;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;

/**
 * ViewsHelper service.
 */
class ViewsHelper {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a ViewsHelper object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection, CacheBackendInterface $cache) {
    $this->connection = $connection;
    $this->cache = $cache;
  }

  /**
   * Returns an array of how many times each term in a vocabulary is used by a
   * published node of a given type.
   *
   * IMPORTANT: if a term is not used at all, it does not appear in the results.
   *
   * @param string $vid
   * @param string $type
   *
   * @return array
   *   Count totals keyed by term tid.
   */
  public function getTermCountsByType(string $vid, string $type) {
    $cid = "term_counts:$vid:$type";
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }
    $query = $this->connection->select('taxonomy_term_field_data', 'tax');
    $query->leftJoin('taxonomy_index', 'ti', 'tax.tid = ti.tid');
    $query->leftJoin('node_field_data', 'n', 'ti.nid = n.nid');
    $query->addField('tax', 'tid', 'tid');
    $query->addExpression('count(n.nid)', 'count');
    $query->groupBy('tax.tid');
    $query->condition('tax.status', 1);
    $query->condition('tax.vid', $vid);
    $query->condition('n.status', 1);
    $query->condition('n.type', $type);
    $counts = $query->execute()->fetchAllKeyed();
    $this->cache->set($cid, $counts, CacheBackendInterface::CACHE_PERMANENT, ['node_list']);
    return $counts;
  }

}
