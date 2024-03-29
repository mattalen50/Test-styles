<?php

namespace Drupal\law_editing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\layout_paragraphs\LayoutParagraphsComponent;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Drupal\node\NodeInterface;

class TemplateManager {

  use \Drupal\Core\StringTranslation\StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  function getTemplateForContentType(string $content_type) {
    $template = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'title' => 'TEMPLATE',
      'type' => $content_type,
    ]);
    if (!empty($template)) {
      // Note: currently presumes only 1 template and uses first
      // @TODO Add check for 2+ templates & throw error
      return reset($template);
    }
    return NULL;
  }

  /**
   * Gets all nodes with a title of 'TEMPLATE' and returns an array of
   * identifiers for the associated Layout Paragraphs field (for which the
   * default layout setting is disused and should be disabled).
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  function getFieldsWithTemplateOverrides() {
    $template_overridden_paragraph_fields = [];
    // By convention, all content type templates are given the title "TEMPLATE"
    $templated_nodes = $this->entityTypeManager->getStorage('node')->loadByProperties([
      'title' => 'TEMPLATE',
    ]);

    if (!empty($templated_nodes)) {
      $bundles = [];
      $entityFieldManager = \Drupal::service('entity_field.manager');

      // Get bundle ID/name for each node, then eliminate duplicates
      foreach ($templated_nodes as $node) {
        $bundles[] = $node->bundle();
      }
      $bundles = array_unique($bundles);

      // We need to identify which field is used for Layout Paragraphs.
      // By convention we currently use 'field_components', but this is not
      // enforced. So, we iterate through each bundle's fields and see if
      // there is one of type 'entity_reference_revisions' with the handler
      // 'default:paragraph' (and presume there is only one per bundle).
      //
      // We pre-filter on fields prefixed with 'field_' (custom defined fields)
      // to avoid certain system fields where get() throws trim() errors.
      foreach ($bundles as $bundle) {
        $bundle_field_definitions = $entityFieldManager->getFieldDefinitions('node', $bundle);

        /** @var \Drupal\Core\Entity\EntityFieldManager $field */
        foreach ($bundle_field_definitions as $field) {
          if (
            str_starts_with($field->getName(),'field_') &&
            $field->get('field_type') == 'entity_reference_revisions' &&
            $field->getSetting('handler') == 'default:paragraph' &&
            !empty($field->get('id'))
          ) {
            $template_overridden_paragraph_fields[] = $field->get('id');
          }
        }
      }
    }
    return $template_overridden_paragraph_fields;
  }

  /**
   * Duplicates Layout Paragraph structure from TEMPLATE node of same content
   * type to new node or existing node (if field_components is not already set).
   *
   * @param \Drupal\node\NodeInterface $node
   * @param \Drupal\node\NodeInterface $template
   *
   * @return void
   */
  function cloneLayoutParagraphTemplateParagraphs(NodeInterface $node, NodeInterface $template) {

    $field = $node->get('field_components');
    $layout = new LayoutParagraphsLayout($field);
    if ($layout->isEmpty()) {
      // Clone the layout paragraphs from the template entity, keeping track of
      // UUID changes.
      $uuid_map = [];
      $components = [];
      foreach ($template->get('field_components') as $delta => $item) {
        $duplicate = $item->entity->createDuplicate();
        $uuid_map[$item->entity->uuid()] = $duplicate->uuid();
        $components[] = new LayoutParagraphsComponent($duplicate);
      }
      // Remap Parent UUIDs on cloned paragraphs to their cloned parents.
      foreach ($components as $component) {
        /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
        $paragraph = $component->getEntity();
        if ($parent_uuid = $paragraph->getBehaviorSetting('layout_paragraphs', 'parent_uuid')) {
          $behavior = $paragraph->getAllBehaviorSettings()['layout_paragraphs'];
          $behavior['parent_uuid'] = $uuid_map[$parent_uuid];
          $paragraph->setBehaviorSettings('layout_paragraphs', $behavior);
        }
        $layout->appendComponent($paragraph);
      }
    }
    else {
      // This node already has Layout Paragraphs set. Skip and alert user.
      $title = $node->getTitle() ?? "[not set]";
      $nid = $node->id();
      \Drupal::messenger()->addWarning($this->t('Layout Paragraphs have already been set for node #%nid with title "%title". Layout template not applied.',['%nid' => $nid, '%title' => $title]));
    }

  }

}
