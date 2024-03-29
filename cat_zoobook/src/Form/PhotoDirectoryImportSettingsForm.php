<?php

namespace Drupal\law_zoobook\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Law School Zoobook photo import settings for this site.
 */
class PhotoDirectoryImportSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(ConfigFactoryInterface $config_factory, FileSystemInterface $file_system, EntityTypeManagerInterface $entity_type_manager) {
    $this->setConfigFactory($config_factory);
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('file_system'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'law_zoobook_photo_directory_import_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['law_zoobook.photo_directory_import_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // The top portion of the form allows the user to set the directory where
    // the Zoobook photos were updated.
    $dir = $this->config('law_zoobook.photo_directory_import_settings')->get('import_directory');
    $form['import_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Import directory'),
      '#default_value' => $dir,
    ];
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = $this->t('Update import directory location');

    // The bottom portion of the form scans the import directory to preview the
    // files found. A separate Submit buttom runs the import.
    try {
      $files = $this->getFiles($dir);
    } catch (\Exception $e) {
      $files = [];
    }
    if ($files) {
      $form['photos'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Photos in %dir', ['%dir' => $dir]),
        '#weight' => 100,
      ];
      $form['photos']['files'] = $this->renderFileList($files);
      $form['photos']['files']['#weight'] = 100;
      $form['photos']['import'] = [
        '#type' => 'submit',
        '#submit' => [[$this, 'importPhotos']],
        '#value' => $this->t('Import directory photos'),
        '#weight' => 101,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Don't allow setting a nonexistent directory, or one without images.
    $dir = $form_state->getValue('import_directory');
    try {
      if (!$files = $this->getFiles($dir)) {
        $form_state->setErrorByName('import_directory', $this->t('There are no zoobook photos in the specified directory.'));
      }
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('import_directory', $this->t('Could not scan the specified directory.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('law_zoobook.photo_directory_import_settings')
      ->set('import_directory', $form_state->getValue('import_directory'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Get a list of image files in the specified directory.
   *
   * @param $dir
   *   A directory URI, probably starting with "private://".
   *
   * @return array
   *   An associative array of stdClass objects of file data, keyed by URI.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFiles($dir) {
    $files = $this->fileSystem->scanDirectory($dir, '/.*\.(jpg|jpeg|gif| png)/i');
    // Check for a matching zoobook profile. If there is one, add the NID to the
    // filedata.
    foreach ($files as $filedata) {
      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $query->condition('field_x500_reference.value', $filedata->name);
      $nids = $query->accessCheck(FALSE)->execute();
      if ($nids) {
        $filedata->nid = reset($nids);
      }
    }
    return $files;
  }

  /**
   * Render a list of images, highlighting ones that don't match an existing
   * Zoobook profile node.
   *
   * @param array $files
   *   An array of files from $this->>getFiles().
   *
   * @return array
   *   A renderable item list of filenames.
   */
  protected function renderFileList(array $files) {
    $build = [
      '#theme' => 'item_list',
      '#list_type' => 'ol',
      '#items' => [],
    ];
    foreach ($files as $filedata) {
      $item = $filedata->filename;
      if (empty($filedata->nid)) {
        $item = Markup::create('<strong>' . $filedata->filename . '</strong> - no Zoobook profile match');
      }
      else {
        $node = $this->entityTypeManager->getStorage('node')->load($filedata->nid);
        if ($node->field_suppress_person->value) {
          $item = Markup::create('<em>' . $filedata->filename . '</em> - Profile is suppressed from directory');
        }
      }
      $build['#items'][] = $item;
    }
    return $build;
  }

  /**
   * Alternate submit callback.
   *
   * Create managed files out of matching raw files, moves them to the Zoobook
   * directory configured for the field, and updates the Zoobook node with the
   * new file.
   */
  public function importPhotos(array &$form, FormStateInterface $form_state) {
    $count = 0;
    $dir = $this->config('law_zoobook.photo_directory_import_settings')->get('import_directory');
    $files = $this->getFiles($dir);
    foreach ($files as $filedata) {
      if (empty($filedata->nid)) {
        continue;
      }
      $node = $this->entityTypeManager->getStorage('node')->load($filedata->nid);
      // Skip suppressed profiles.
      if ($node->field_suppress_person->value) {
        continue;
      }
      // Create a managed file entity from the raw file.
      $file = File::create([
        'uid' => 1,
        'filename' => $filedata->filename,
        'uri' => $filedata->uri,
        'status' => 1,
      ]);
      $file->save();
      // Move the managed file from the import directory to the configured
      // Zoobook portrait directory.
      /** @var \Drupal\field\Entity\FieldConfig $field */
      $field = $this->entityTypeManager->getStorage('field_config')->load('node.zoobook.field_student_portrait');
      $settings = $field->getSettings();
      $destination = $settings['uri_scheme'] . '://' . $settings['file_directory'] . '/' . $filedata->filename;
      // Formerly file_move(), removed in D10. Parameters are same before & after.
      \Drupal::service('file.repository')->move($file, $destination);
      // Update the Zoobook node with the new file entity.
      $node->field_student_portrait->target_id = $file->id();
      $node->field_student_portrait->alt = $node->field_first_name->value . ' ' . $node->field_last_name->value . ' portrait';
      $node->save();
      $count++;
    }
    $this->messenger()->addStatus($this->t('%count Zoobook profiles were updated with new photos.', ['%count' => $count]));
  }

}
