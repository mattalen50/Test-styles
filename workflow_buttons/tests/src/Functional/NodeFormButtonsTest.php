<?php

namespace Drupal\Tests\workflow_buttons\Functional;

use Drupal\Tests\node\Functional\AssertButtonsTrait;
use Drupal\Tests\node\Functional\NodeTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Tests all the different buttons on the node form.
 *
 * @group workflow_buttons
 */
class NodeFormButtonsTest extends NodeTestBase {

  use StringTranslationTrait;
  use AssertButtonsTrait;

  /**
   * {@inheritDoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'workflow_buttons',
  ];

  /**
   * A normal logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a user that has no access to change the state of the node.
    $this->webUser = $this->drupalCreateUser(['create article content', 'edit own article content']);
    // Create a user that has access to change the state of the node.
    $this->adminUser = $this->drupalCreateUser(['administer nodes', 'bypass node access']);
  }

  /**
   * Tests that the right buttons are displayed for saving nodes.
   */
  public function testNodeFormButtons() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    // Log in as administrative user.
    $this->drupalLogin($this->adminUser);

    // Verify the buttons on a node add form.
    $this->drupalGet('node/add/article');
    $this->assertButtons([$this->t('Save and publish'), $this->t('Save as unpublished')]);

    // Save the node and assert it's published after clicking
    // 'Save and publish'.
    $edit = ['title[0][value]' => $this->randomString()];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, $this->t('Save and publish'));

    // Get the node.
    $node_1 = $node_storage->load(1);
    $this->assertTrue($node_1->isPublished(), 'Node is published');

    // Verify the buttons on a node edit form.
    $this->drupalGet('node/' . $node_1->id() . '/edit');
    $this->assertButtons([$this->t('Save and keep published'), $this->t('Save and unpublish')]);

    // Save the node and verify it's still published after clicking
    // 'Save and keep published'.
    $this->submitForm($edit, $this->t('Save and keep published'));
    $node_storage->resetCache([1]);
    $node_1 = $node_storage->load(1);
    $this->assertTrue($node_1->isPublished(), 'Node is published');
    $this->drupalGet('node/' . $node_1->id() . '/edit');

    // Save the node and verify it's unpublished after clicking
    // 'Save and unpublish'.
    $this->submitForm($edit, $this->t('Save and unpublish'));
    $node_storage->resetCache([1]);
    $node_1 = $node_storage->load(1);
    $this->assertFalse($node_1->isPublished(), 'Node is unpublished');

    // Verify the buttons on an unpublished node edit screen.
    $this->drupalGet('node/' . $node_1->id() . '/edit');
    $this->assertButtons([$this->t('Save and keep unpublished'), $this->t('Save and publish')]);

    // Create a node as a normal user.
    $this->drupalLogout();
    $this->drupalLogin($this->webUser);

    // Verify the buttons for a normal user.
    $this->drupalGet('node/add/article');
    $this->assertButtons([$this->t('Save')], FALSE);

    // Create the node.
    $edit = ['title[0][value]' => $this->randomString()];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, $this->t('Save'));
    $node_2 = $node_storage->load(2);
    $this->assertTrue($node_2->isPublished(), 'Node is published');

    // Log in as an administrator and unpublish the node that just
    // was created by the normal user.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $node_2->id() . '/edit');
    $this->submitForm([], $this->t('Save and unpublish'));
    $node_storage->resetCache([2]);
    $node_2 = $node_storage->load(2);
    $this->assertFalse($node_2->isPublished(), 'Node is unpublished');

    // Log in again as the normal user, save the node and verify
    // it's still unpublished.
    $this->drupalLogout();
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/' . $node_2->id() . '/edit');
    $this->submitForm([], $this->t('Save'));
    $node_storage->resetCache([2]);
    $node_2 = $node_storage->load(2);
    $this->assertFalse($node_2->isPublished(), 'Node is still unpublished');
    $this->drupalLogout();

    // Set article content type default to unpublished. This will change the
    // the initial order of buttons and/or status of the node when creating
    // a node.
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'article');
    $fields['status']->getConfig('article')
      ->setDefaultValue(FALSE)
      ->save();

    // Verify the buttons on a node add form for an administrator.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/add/article');
    $this->assertButtons([$this->t('Save as unpublished'), $this->t('Save and publish')]);

    // Verify the node is unpublished by default for a normal user.
    $this->drupalLogout();
    $this->drupalLogin($this->webUser);
    $edit = ['title[0][value]' => $this->randomString()];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, $this->t('Save'));
    $node_3 = $node_storage->load(3);
    $this->assertFalse($node_3->isPublished(), 'Node is unpublished');
  }

}
