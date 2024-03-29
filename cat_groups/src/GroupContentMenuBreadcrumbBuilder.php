<?php

namespace Drupal\law_groups;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

//// TODO: resume "use" of dependency injection once Group 2.x everywhere
// use Drupal\group\Entity\GroupRelationship;
//// END TODO

use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Breadcrumb builder for nodes in groups, based on their Group Content Menu
 * active trail.
 *
 * @TODO: Rename to GroupRelationshipMenuBreadcrumbBuilder to match naming
 * convention of 2.x branch (GroupContent --> GroupRelationship)
 */
class GroupContentMenuBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use \Drupal\Core\StringTranslation\StringTranslationTrait;

  /**
   * The menu active trail interface.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The menu link manager interface.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The caching backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheMenu;

  /**
   * The locking backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The menu where the current page or taxonomy match has taken place.
   *
   * @var string
   */
  private $menuName;

  /**
   * The menu trail leading to this match.
   *
   * @var string
   */
  private $menuTrail;

  /**
   * Content language code (used in both applies() and build()).
   *
   * @var string
   */
  private $contentLanguage;

  /**
   * The Group Content Menu entity.
   *
   * @TODO uncomment to resume declaration of interface for property
   * \@\var \Drupal\group_content_menu\ GroupRelationshipMenuInterface
   *
   * @TODO Rename to $groupRelationshipMenu
   */
  private $groupContentMenu;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    MenuActiveTrailInterface   $menu_active_trail,
    MenuLinkManagerInterface   $menu_link_manager,
    RequestStack               $request_stack,
    LanguageManagerInterface   $language_manager,
    EntityTypeManagerInterface $entity_type_manager,
    CacheBackendInterface      $cache_menu,
    LockBackendInterface       $lock
  ) {
    $this->menuActiveTrail = $menu_active_trail;
    $this->menuLinkManager = $menu_link_manager;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheMenu = $cache_menu;
    $this->lock = $lock;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // No route name means no active trail:
    $route_name = $route_match->getRouteName();
    if (!$route_name) {
      return FALSE;
    }

    // Make sure menus are selected, and breadcrumb text strings, are displayed
    // in the content rather than the (default) interface language:
    $this->contentLanguage = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();

    $group_content_menus = $this->entityTypeManager->getStorage('group_content_menu')
      ->loadByProperties();
    foreach ($group_content_menus as $id => $menu) {
      $menu_name = 'group_menu_link_content-' . $id;
      // Look for current path on any group content menu.
      $trail_ids = $this->menuActiveTrail->getActiveTrailIds($menu_name);
      $trail_ids = array_filter($trail_ids);
      if ($trail_ids) {
        $this->menuName = $menu_name;
        $this->menuTrail = $trail_ids;
        $this->groupContentMenu = $menu;
        return TRUE;
      }
    }
    // No more menus to check...
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    // Breadcrumbs accumulate in this array, with lowest index being the root
    // (i.e., the reverse of the assigned breadcrumb trail):
    $links = [];
    if ($this->languageManager->isMultilingual()) {
      $breadcrumb->addCacheContexts(['languages:language_content']);
    }

    // Changing the active trail or URL will invalidate this breadcrumb:
    $breadcrumb->addCacheContexts(['route.menu_active_trails:' . $this->menuName]);
    $breadcrumb->addCacheContexts(['url.path']);

    // Generate basic breadcrumb trail from active trail.
    // Keep same link ordering as Menu Breadcrumb (so also reverses menu trail)
    foreach (array_reverse($this->menuTrail) as $id) {
      $plugin = $this->menuLinkManager->createInstance($id);
      $links[] = Link::fromTextAndUrl($plugin->getTitle(), $plugin->getUrlObject());
      $breadcrumb->addCacheableDependency($plugin);
      // In the last line, MenuLinkContent plugin is not providing cache tags.
      // Until this is fixed in core add the tags here:
      if ($plugin instanceof MenuLinkContent) {
        $uuid = $plugin->getDerivativeId();
        $entities = $this->entityTypeManager->getStorage('menu_link_content')
          ->loadByProperties(['uuid' => $uuid]);
        if ($entity = reset($entities)) {
          $breadcrumb->addCacheableDependency($entity);
        }
      }
    }

    // Create a breadcrumb for the group home page.

    //// TODO: remove conditions & return to "use" dependency injection once
    //// Group 2.x is in place everywhere
    if (class_exists('\Drupal\group\Entity\GroupRelationship')) {
      $group_contents = \Drupal\group\Entity\GroupRelationship::loadByEntity($this->groupContentMenu);
    }
    // NO: if (class_exists('\Drupal\group\Entity\GroupContent')){
    // Just presume if GroupRelationship doesn't exist, GroupContent does
    else {
      $group_contents = \Drupal\group\Entity\GroupContent::loadByEntity($this->groupContentMenu);
    }
    //// TODO: resume dependency injection once 2.x is everywhere
    // $group_contents = GroupRelationship::loadByEntity($this->groupContentMenu);
    //// End TODO

    foreach ($group_contents as $group_content) {
      $group = $group_content->getGroup();
      if ($group->hasField('field_home_page') && !empty($group->field_home_page->target_id)) {
        $group_link = Link::createFromRoute($group->label(), 'entity.node.canonical', ['node' => $group->field_home_page->target_id]);
        // Check if the current page is already the group home page. Replace it
        // with our constructed breadcrumb link if so.
        $first_url = $links[0]->getUrl();
        if ($first_url->getRouteName() == 'entity.node.canonical' && $first_url->getRouteParameters()['node'] == $group->field_home_page->target_id) {
          $links[0] = $group_link;
        }
        else {
          array_unshift($links, $group_link);
        }
        break;
      }
    }

    // Create a breadcrumb for the front page.
    $langcode = $this->contentLanguage;
    $label = $this->t('Home', [], ['langcode' => $langcode]);
    $home_link = Link::createFromRoute($label, '<front>');
    array_unshift($links, $home_link);


    // Don't link the last crumb, since it represents the current page.
    /** @var \Drupal\Core\Link $current */
    $current = array_pop($links);
    $current->setUrl(new Url('<none>'));
    array_push($links, $current);

    return $breadcrumb->setLinks($links);
  }

}
