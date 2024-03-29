<?php

namespace Drupal\law_editing\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Error;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Applies default Layout Paragraphs structure to an existing node as long as
 * (a) a node with title TEMPLATE exists for that content type, and (b) the
 * node being modified has not yet had its L.P. 'components' field set.
 *
 * @Action(
 *   id = "clone_layout_paragraphs_template",
 *   label = @Translation("Clone Layout Paragraphs default layout to selected node(s)"),
 *   type = "node"
 * )
 */
class CloneLayoutParagraphsTemplate extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {
    try {
      /** @var \Drupal\law_editing\TemplateManager $manager */
      $manager = \Drupal::service('law_editing.template_manager');
      $template = $manager->getTemplateForContentType($node->bundle());
      if ($template) {
        // Service method will clone layout IF components field is not already
        // set. Otherwise user notice will be displayed.
        $manager->cloneLayoutParagraphTemplateParagraphs($node, $template);
        $node->save();
      }
      else {
        // No template found for this content type. Skip and alert user.
        /** @var \Drupal\node\NodeInterface $node */
        $bundle = $node->bundle();
        $nid = $node->id();
        \Drupal::messenger()->addWarning($this->t('No template found for node #%nid of type %bundle. Layout template not applied.',['%nid' => $nid, '%bundle' => $bundle]));

      }
    }
    catch (\Exception $e) {
      // Example preserved for D11 deprecation prep
      // See https://www.drupal.org/node/2932520
      // watchdog_exception('law_editing', $e);
      $logger = \Drupal::logger('law_editing');
      Error::logException($logger, $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $return_as_object ? AccessResult::allowed() : TRUE;
  }

}
