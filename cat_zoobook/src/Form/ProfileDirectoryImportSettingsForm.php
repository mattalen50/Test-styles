<?php

namespace Drupal\law_zoobook\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\file\Entity\File;
use \Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Law School Zoobook profile import settings for this site.
 */
class ProfileDirectoryImportSettingsForm extends ConfigFormBase {

  /**
   * Expected CSV column headers.
   *
   * @var array
   */
  protected array $csv_headers = [
    'X.500',
    'email',
    'LAST',
    'FIRST',
    'GRAD_YEAR',
    'DEGREE',
  ];

  /**
   * Expanded column headers for existing-student table output.
   *
   * @var array
   */
  protected array $csv_existing_student_headers = [
    'Published',
    'Suppressed',
    'Notes',
    'Edit',
  ];

  /**
   * Store term IDs that have been looked up for re-use, keyed by taxonomy IDs.
   * Also note whether there have been any missing-term errors.
   *
   * @var array
   */
  protected array $terms = [
    'degree_type' => [],
    'grad_year' => [],
    'term_error' => FALSE,
  ];

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->setConfigFactory($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames() {
    return ['law_zoobook.profile_directory_import_settings'];
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'law_zoobook_profile_directory_import_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Top of two-part form where we submit/manage the CSV file that will be the
    // data source for import & persist between page loads.
    // The import_file config expects a single FID integer value.
    $import_file = $this->config('law_zoobook.profile_directory_import_settings')->get('import_file');
    $allowed_ext = 'csv';
    $form['top_spacer'] = [
      '#type' => 'item',
      '#title' => $this->t("Instructions:"),
      '#markup' => $this->t("Import students by uploading a CSV file with columns in this format:
      [X.500 (Internet ID), email, LAST (name), FIRST (name), GRAD_YEAR, DEGREE].<br>
      Both GRAD_YEAR and DEGREE must be strings exactly matching existing taxonomy terms.<br>
      File may contain a header row, which will be skipped if the first cell contains 'X.500'<br>&nbsp;"),
    ];
    $form['import_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('CSV File for Import'),
      '#description' => $this->t('Valid extensions: @allowed_ext', ['@allowed_ext' => $allowed_ext]),
      '#upload_location' => 'temporary://temp-import/',
      '#multiple' => FALSE,
      '#required' => TRUE,
      '#default_value' => ($import_file) ? [$import_file] : NULL,
      '#upload_validators' => [
        'file_validate_extensions' => [
          $allowed_ext,
        ],
      ],
    ];

    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#value'] = $this->t('Update CSV File Preview');

    // Second of two-part form, where we load CSV file if present and parse it
    // to review pending update.
    // NOTE: entityTypeManager WSoD on load() of NULL, so need extra logic even
    // outside of try/catch.
    $file = [];
    if ($import_file) {
      try {
        $file = $this->entityTypeManager->getStorage('file')->load($import_file);
      } catch (\Exception $e) {
        // Nothing to do
      }
    }

    if ($file) {
      // Get file metadata: should be saved in temporary:// location.
      $file_data = File::load($import_file);
      if (!$file_data->isTemporary()) {
        $this->messenger()->addWarning($this->t("File is not in Temporary storage! Are you sure you want to proceed?"));
      }

      // Parse CSV to array for inspection and table rendering.
      $csv_data = [];
      if (($csv = fopen($file->uri->getString(), 'r')) !== FALSE) {
        while (($row = fgetcsv($csv, 0, ",")) !== FALSE) {
          $csv_data[] = $row;
        }
        fclose($csv);
      }

      if ($csv_data) {
        // Successfully parsed CSV; now review each row.
        $processed_data = $this->processCsvData($csv_data);

        // Display processed data from file
        $form['file_header'] = [
          '#type' => 'item',
          '#title' => $this->t('Preview of %filename', ['%filename' => $file_data->getFilename()]),
          '#prefix' => $this->t("<br>"),
        ];

        // If some incoming students are already in system, list them and
        // compare old/new data. These will NOT be imported.
        if ($processed_data['existing_students']) {
          $form['existing_csv_data'] = [
            '#type' => 'fieldset',
            '#title' => 'Pre-Existing Students',
            '#weight' => 100,
          ];
          $form['existing_csv_data']['existing_students'] = array(
            '#type' => 'table',
            '#caption' => 'These students are already in the system and will be SKIPPED. Update manually, as needed.',
            '#header' => array_merge($this->csv_headers, $this->csv_existing_student_headers),
            '#rows' => $processed_data['existing_students'],
          );
        }

        if ($processed_data['new_students']) {
          // Separate form group/fieldset for new students to import
          $form['new_csv_data'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('NEW Students'),
            '#weight' => 100,
          ];
          $form['new_csv_data']['description'] = [
            '#type' => 'item',
            '#description' => $this->t("The following students will be imported, with values as per table headers."),
          ];
          // Show button only if ready to import all taxonomy terms
          if (!$this->terms['term_error']) {
            $form['new_csv_data']['import'] = [
              '#type' => 'submit',
              '#submit' => [[$this, 'importNewStudents']],
              '#value' => $this->t('Import Students'),
              '#weight' => 101,
            ];
          }
          $form['new_csv_data']['new_students'] = array(
            '#type' => 'table',
            '#caption' => $this->t("<br>"),
            '#header' => $this->csv_headers,
            '#rows' => $processed_data['new_students'],
            '#weight' => 102,
          );
        }
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('law_zoobook.profile_directory_import_settings')
      ->set('import_file', reset($form_state->getValue('import_file')))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Iterate through array of parsed raw CSV data of incoming students to
   * determine whether any in import file already exist in the system, based on
   * Internet ID (X.500) or email address. Split the results into two groups,
   * and augment existing-student output for display. See $this->csv_headers for
   * expected CSV column order.
   *
   * @param array $csv_array
   *   An array of data parsed from CSV file input, with columns expected to
   *   follow $this->csv_headers in order.
   *
   * @return array
   *   An array of two arrays, keyed as 'existing_students' and 'new_students',
   *   each formatted for rendered table output. 'new_students' follows the
   *   original CSV order, as in $this->csv_headers.
   */
  protected function processCsvData(array $csv_array) {
    $build['existing_students'] = [];
    $build['new_students'] = [];
    $missing_terms['grad_year'] = [];
    $missing_terms['degree_type'] = [];

    // Iterate through each row & look up any existing Zoobook nodes based
    // on the first column holding the UMN ID (X.500)
    foreach ($csv_array as $row) {
      if ($row[0] == 'X.500') {
        // Skip header row if present
        continue;
      }

      $nids = [];
      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $orGroup = $query->orConditionGroup()
        ->condition('field_x500_reference.value', $row[0])
        ->condition('field_default_email.value', $row[1]);
      $query->condition($orGroup);
      $nids = $query->accessCheck(FALSE)->execute();

      if ($nids) {
        // We have a user with this ID/email already.

        if(sizeof($nids) > 1) {
          // Multiple matches are found: special case to call out.
          $row[6] = "N/A";
          $row[7] = "N/A";
          $row[8] = "Multiple ID/Email Matches";
          $row[9] = "";
        }

        else {
          // We have only 1 match, so compare values & indicate changes.
          $node = $this->entityTypeManager->getStorage('node')->load(reset($nids));
          $edit_url = $node->toUrl('edit-form');
          $edit_link = Link::fromTextAndUrl('edit', $edit_url);
          $note = "";
          if ($node->getCreatedTime() > strtotime('today')) {
            // Edited after midnight today, server time
            $note .= $this->t("CREATED today");
          }
          elseif ($node->getChangedTime() > strtotime('today')) {
            // Edited after midnight today, server time
            $note .= $this->t("EDITED today");
          }

          $row[0] = ($node->field_x500_reference->value == $row[0]) ? $row[0] : (($node->field_x500_reference->value) ?: "NULL") . " --> " . $row[0];
          $row[1] = ($node->field_default_email->value  == $row[1]) ? $row[1] : (($node->field_default_email->value)  ?: "NULL") . " --> " . $row[1];
          $row[2] = ($node->field_last_name->value      == $row[2]) ? $row[2] : (($node->field_last_name->value)      ?: "NULL") . " --> " . $row[2];
          $row[3] = ($node->field_first_name->value     == $row[3]) ? $row[3] : (($node->field_first_name->value)     ?: "NULL") . " --> " . $row[3];
          $row[4] = ($node->field_graduation_year->entity->getName() == $row[4]) ? $row[4] : (($node->field_graduation_year->entity->getName()) ?: "NULL") . " --> " . $row[4];
          $row[5] = ($node->field_degree_type->entity->getName()     == $row[5]) ? $row[5] : (($node->field_degree_type->entity->getName())      ?: "NULL") . " --> " . $row[5];
          $row[6] = ($node->isPublished()) ? "yes" : "NO";
          $row[7] = ($node->field_suppress_person->value) ? "YES" : "no";
          $row[8] = $note;
          $row[9] = $edit_link;
        }

        $build['existing_students'][] = $row;
      }

      else {
        // This is a new user. Preserve & present data to be imported.
        $build['new_students'][] = $row;

        // Test for terms in import that are NOT present to reference.
        if ($this->getTermIdFromName($row[4], 'grad_year') === FALSE) {
          $this->terms['term_error'] = TRUE;
          $missing_terms['grad_year'][] = $row[4];
        }
        if ($this->getTermIdFromName($row[5], 'degree_type') === FALSE) {
          $this->terms['term_error'] = TRUE;
          $missing_terms['degree_type'][] = $row[5];
        }
      }

    }

    // Give error feedback if import file contains terms that don't yet exist.
    if (!empty($missing_terms['grad_year'])) {
      $term_list = implode(', ', $missing_terms['grad_year']);
      $this->messenger()->addError($this->t('Import contains Grad Year term(s) that do not exist. Create them first: %terms', ['%terms' => $term_list] ));
    }
    if (!empty($missing_terms['degree_type'])) {
      $term_list = implode(', ', $missing_terms['degree_type']);
      $this->messenger()->addError($this->t('Import contains Degree Type term(s) that do not exist. Create them first: %terms', ['%terms' => $term_list] ));
    }

    return $build;

  }

  /**
   * Iterate through array of new students to import, creating Zoobook nodes
   * for each. Note that terms (grad year and degree) must match existing
   * taxonomy terms, or new ones will be created!
   */
  public function importNewStudents(array &$form, FormStateInterface $form_state) {
    $count = 0;
    $csv_data = [];
    // By this point, we can safely assume the csv import file has been uploaded
    // and saved properly to be parsed here.
    $import_file = $this->config('law_zoobook.profile_directory_import_settings')->get('import_file');
    $file = $this->entityTypeManager->getStorage('file')->load($import_file);
    $csv = fopen($file->uri->getString(), 'r');
    while (($row = fgetcsv($csv, 0, ",")) !== FALSE) {
      $csv_data[] = $row;
    }
    fclose($csv);
    $processed_data = $this->processCsvData($csv_data);

    foreach ($processed_data['new_students'] as $row) {
      // Convert incoming taxonomy terms to IDs
      $year_tid = $this->getTermIdFromName($row[4], 'grad_year') ?: "";
      $degree_tid = $this->getTermIdFromName($row[5], 'degree_type') ?: "";

      $node = Node::create(['type' => 'zoobook']);
      $node->set('field_x500_reference', $row[0]);
      $node->set('field_default_email', $row[1]);
      $node->set('field_last_name', $row[2]);
      $node->set('field_first_name', $row[3]);
      $node->set('field_graduation_year', $year_tid);
      $node->set('field_degree_type', $degree_tid);
      $node->save();
      $count++;
    }
    $this->messenger()->addStatus($this->t('Success: %count Zoobook profiles were created.', ['%count' => $count]));
  }

  /**
   * Get term ID from term name, checking first for whether the ID has already
   * been queried, and if not, then run Drupal logic to get the ID.
   *
   * Returns FALSE if no match is found.
   */
  protected function getTermIdFromName(string $name, string $vid) {
    if (isset($this->terms[$vid][$name])) {
      return $this->terms[$vid][$name];
    }
    if ($term = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['name' => $name, 'vid' => $vid])) {
      $term = reset($term);
      $this->terms[$vid][$name] = $term->id();
      return $this->terms[$vid][$name];
    }
    return FALSE;
  }

}
