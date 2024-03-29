<?php

namespace Drupal\law_courses\EventSubscriber;

use Drupal\search_api\Event\IndexingItemsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Law School Courses event subscriber for Search API events.
 */
class SearchApiSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'search_api.indexing_items' => ['replaceTitleNumberNewlineChars'],
    ];
  }

  /**
   * Respond when Search API is indexing.
   *
   * Replace 'title_number' newline characters with a simple space character.
   * Search API is able to concatenate properties into a single field using the
   * Aggregated Field option. The concatenation delimiter is a hard-coded "\n\n",
   * which is a problem for Facet Forms; the form input there is represented as
   * "\r\n\r\n".
   *
   * @param \Drupal\search_api\Event\IndexingItemsEvent $event
   *   Response event.
   */
  public function replaceTitleNumberNewlineChars(IndexingItemsEvent $event) {
    $items = $event->getItems();
    foreach ($items as $item) {
      /** @var \Drupal\search_api\Item\Field[] $fields */
      $fields = $item->getFields();
      if (isset($fields['title_number'])) {
        $values = $fields['title_number']->getValues();
        foreach ($values as $delta => $value) {
          $values[$delta] = str_replace("\n\n", ' ', $value);
        }
        $fields['title_number']->setValues($values);
      }
    }
  }
}
