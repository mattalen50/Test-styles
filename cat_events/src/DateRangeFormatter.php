<?php

namespace Drupal\law_events;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * DateRangeFormatter service.
 */
class DateRangeFormatter {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a DateRangeFormatter object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DateFormatterInterface $date_formatter) {
    $this->configFactory = $config_factory;
    $this->dateFormatter = $date_formatter;
    $tz = $this->configFactory->get('system.date')->get('timezone')['default'];
    date_default_timezone_set($tz);
  }

  public function formatDate(DrupalDateTime $date) {
    return $this->dateFormatter->format($date->getTimestamp(), 'long');
  }

  /**
   * Compose a date range string, combining like parts.
   */
  public function formatDateRange(DrupalDateTime $start, DrupalDateTime $end) {
    $is_same_day = $this->dateFormatter->format($start->getTimestamp(), 'date_only') == $this->dateFormatter->format($end->getTimestamp(), 'date_only');
    $is_same_ampm = ($is_same_day) && ($this->dateFormatter->format($start->getTimestamp(), 'custom', 'a') == $this->dateFormatter->format($end->getTimestamp(), 'custom', 'a'));

    if (!$is_same_day) {
      $date = $this->dateFormatter->format($start->getTimestamp(), 'long');
      $date .= ' - ';
      $date .= $this->dateFormatter->format($end->getTimestamp(), 'long');
      return $date;
    }
    else {
      $date = $this->dateFormatter->format($start->getTimestamp(), 'date_only');
    }

    if (!$is_same_ampm) {
      $time = $this->dateFormatter->format($start->getTimestamp(), 'time_only');
      $time .= ' to ';
      $time .= $this->dateFormatter->format($end->getTimestamp(), 'time_only');
    }
    else {
      $time = $this->dateFormatter->format($start->getTimestamp(), 'custom', 'g:i');
      $time .= ' to ';
      $time .= $this->dateFormatter->format($end->getTimestamp(), 'time_only');
    }
//    $time = str_replace(':00 - ', '-', $time);
//    $time = str_replace(':00', '', $time);
    return $date . ', ' . $time;
  }

}
