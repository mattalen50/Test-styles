<?php

namespace Drupal\template_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Url;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class TemplateBuilderController extends ControllerBase
{

    /**
     * The file system service.
     *
     * @var \Drupal\Core\File\FileSystemInterface
     */
    protected $fileSystem;

    /**
     * The file url generator service.
     *
     * @var \Drupal\Core\File\FileUrlGeneratorInterface
     */
    protected $fileUrlGenerator;

    /**
     * TemplateBuilderController constructor.
     */
    public function __construct(FileSystemInterface $file_system, $file_url_generator)
    {
        $this->fileSystem = $file_system;
        $this->fileUrlGenerator = $file_url_generator;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('file_system'),
            $container->get('file_url_generator')
        );
    }

    /**
     * Generates a download page for a specific webform submission.
     */
    public function content($sid)
    {
        $webform_submission = WebformSubmission::load($sid);
        if (!$webform_submission || $webform_submission->getWebform()->id() != 'template_builder') {
            throw new NotFoundHttpException();
        }

        // Ensure the directory exists.
        $directory = 'public://template_builder_files';
        if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
            \Drupal::messenger()->addError(t('Unable to create or write to the directory @dir.', ['@dir' => $directory]));
            return [];
        }

        // Load the HTML file.
        $html_filename = $webform_submission->id() . '.html';
        $html_filepath = $this->fileSystem->realpath($directory . '/' . $html_filename);

        // Initialize the ZIP archive.
        $zip = new \ZipArchive();
        $zip_name = $this->fileSystem->realpath("public://template_builder_files/u-of-m-customized-template-{$sid}.zip");

        if ($zip->open($zip_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
          \Drupal::messenger()->addError('Cannot open zip file.');
          return [];
        }

        // Add the HTML file to the ZIP archive.
        $zip->addFile($html_filepath, "{$sid}.html");

        // Declare the module and asset paths.
        $module_path = \Drupal::moduleHandler()->getModule('template_builder')->getPath();
        $asset_path = "{$module_path}/assets";

        // Modify the CSS content based on the webform submission data.
        $css_filepath = "{$asset_path}/css/2015-tc.css";
        $css_content = file_get_contents($css_filepath);
        // Get webform data
        $data = $webform_submission->getData();
        // Set custom width in css. Min is set on field to 820px.
        if (isset($data['maximum_width']) && ($data['maximum_width'] !== '1200px' && $data['maximum_width'] !== '100%')) {
          // Match any instance of width: 1200px and replace with custom number. This currently only replaces two needed lines in the css. Any additions would be replaced.
          $css_content = preg_replace('/width: 1200px;/m', 'width: ' . $data['maximum_width'] . 'px;', $css_content);
        }

        // Generate relative path for CSS file by removing the module's path from its absolute path
        $local_css_path = substr($css_filepath, strlen($module_path) + 1);

        // Add files and folders from the assets directory to the ZIP archive except css file.
        $dir_iterator = new RecursiveDirectoryIterator($asset_path);
        $iterator = new RecursiveIteratorIterator($dir_iterator);

        foreach ($iterator as $file) {
          if ($file->isFile()) {
            $local_path = substr($file->getPathname(), strlen($module_path) + 1);
            // Skip the original CSS file
            if ($local_path !== $local_css_path) {
              $zip->addFile($file->getPathname(), $local_path);
            }
          }
        }

        // Add the modified CSS content to the ZIP archive.
        $zip->addFromString($local_css_path, $css_content);

        // Close the ZIP archive.
        $zip->close();

        // Create a download link for the zip file.
        $zip_uri = $directory . "/u-of-m-customized-template-{$sid}.zip";
        $wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri($zip_uri);
        $download_link = $wrapper->getExternalUrl();

        // Prepare the icon URL for the Twig template.
        $icon_filename = 'zip-16.svg';
        $icon_source_path = $module_path . '/assets/img/icons/' . $icon_filename;
        $public_icon_uri = 'public://template_builder_assets/' . $icon_filename;

        // Copy the icon from the module to the public file system if it doesn't already exist.
        // Use the real path for checking file existence.
        $public_icon_realpath = $this->fileSystem->realpath($public_icon_uri);
        if (!file_exists($public_icon_realpath)) {
          // Use the file_system service to ensure the directory exists and is writable.
          $destination_directory = 'public://template_builder_assets';
          $this->fileSystem->prepareDirectory($destination_directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

          // Now copy the file.
          $this->fileSystem->copy($icon_source_path, $public_icon_uri, FileSystemInterface::EXISTS_REPLACE);
        }

        // Generate the public URL to the icon.
        $icon_url = $this->fileUrlGenerator->generateAbsoluteString($public_icon_uri);

        // Return a render array.
        return [
        '#theme' => 'template_builder_download',
        '#data' => $webform_submission->getData(),
        '#icon_url' => $icon_url,
        '#download_link' => $download_link,
        ];
    }
}
