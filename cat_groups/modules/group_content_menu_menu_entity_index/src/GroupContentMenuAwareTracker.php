<?php

namespace Drupal\group_content_menu_menu_entity_index;

use Drupal\Core\Entity\EntityInterface;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Drupal\menu_entity_index\Tracker;

/**
 * Service decorating menu_entity_index.tracker.
 *
 * Makes Group Content Menus available to Menu Entity Index functionality.
 */
class GroupContentMenuAwareTracker extends Tracker {

  /**
   * {@inheritdoc}
   */
  public function getAvailableMenus() {
    $options = parent::getAvailableMenus();

    $group_content_menus = $this->entityTypeManager->getStorage('group_content_menu')->loadByProperties();
    foreach ($group_content_menus as $id => $menu) {
      $options['group_menu_link_content-' . $id] = $menu->label();
    }
    asort($options);

    return $options;
  }


  /**
   * {@inheritdoc}
   */
  public function getHostData(EntityInterface $entity) {
    $data = [];

    $type = $entity->getEntityTypeId();
    if (in_array($entity->getEntityTypeId(), $this->getTrackedEntityTypes())) {
      $id = $entity->id();
      $result = $this->database->select('menu_entity_index')
        ->fields('menu_entity_index', [
          'entity_type',
          'entity_id',
          'menu_name',
          'level',
          'langcode',
        ])
        ->condition('target_type', $type)
        ->condition('target_id', $id)
        ->orderBy('menu_name', 'ASC')
        ->orderBy('level', 'ASC')
        ->execute();
      $menus = [];
      foreach ($result as $row) {
        if (!isset($menus[$row->menu_name])) {
          if (strpos($row->menu_name, GroupContentMenuInterface::MENU_PREFIX) === 0) {
            $group_content_menu_id = explode('-', $row->menu_name)[1];
            $entity = $this->entityTypeManager->getStorage('group_content_menu')->load($group_content_menu_id);
          }
          else {
            $entity = $this->entityTypeManager->getStorage('menu')
              ->load($row->menu_name);
          }
          $menus[$row->menu_name] = $entity->label();
        }
        $entity = $this->entityTypeManager->getStorage($row->entity_type)
          ->load($row->entity_id);
        if ($entity) {
          $data[] = [
            'menu_name' => $menus[$row->menu_name],
            'level' => $row->level,
            'label' => $entity->getTitle(),
            'link' => $entity->access('view') ? $entity->toUrl() : '',
            'language' => $entity->language()->getName(),
          ];
        }
      }
    }

    return $data;
  }

}
