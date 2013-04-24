<?php

namespace Drupal\teak_prices_import;
use Drupal\krumong as k;

use Drupal\teak_prices_import\Exception\FileManagerException;
use Drupal\teak_prices_import\Exception\FileUnlinkException;
use Drupal\teak_prices_import\Exception\FileCreateException;
use Drupal\teak_prices_import\Exception\InvalidDataException;


class FileManager {

  /**
   * Attempt to load a file entity with the given $info->uri, and
   * if it exists: Update, if needed.
   * if it does not exist: Create it.
   *
   * @param array $info
   *   Imported info about the file. Contains sha1, uri and url.
   *
   * @return stdClass
   *   Object representing the file entity.
   */
  function syncFileEntity($info) {

    // This will throw an exception if the info is corrupted.
    $this->validateImportedInfo($info);

    // First, sync the physical file.
    $this->downloadIfMissingOrMismatch($info);

    // Now sync the file entity.
    if ($file = $this->loadFileEntity($info)) {
      // File entity exists. Check if it needs update.
      $this->updateFileEntityIfNeeded($file, $info);
    }
    else {
      // File entity not existing. Need to create a new one.
      $file = $this->createFileEntity($info);
    }
    return $file;
  }

  protected function validateImportedInfo($info) {
    if (!is_object($info)) {
      throw new InvalidDataException("Imported file info must be an object.");
    }
    if (empty($info->uri)) {
      throw new InvalidDataException("Imported file info must have a key 'uri'.");
    }
    if (empty($info->url)) {
      throw new InvalidDataException("Imported file info must have a key 'url'.");
    }
    if (empty($info->sha1)) {
      throw new InvalidDataException("Imported file info must have a key 'sha1'.");
    }
  }

  protected function createFileEntity($info) {
    $file = (object)array(
      'uri' => $info->uri,
      'filename' => trim(basename($info->uri)),
      'filemime' => file_get_mimetype($info->uri),
      'status' => 1,
    );
    $file = file_save($file);
    if (empty($file) || empty($file->fid)) {
      throw new TeakImportFileException("Failed to create file entity at <code>$info->uri</code>.");
    }
    return $file;
  }

  protected function loadFileEntity($info) {
    foreach (db_select('file_managed', 'f')
      ->condition('uri', $info->uri)
      ->fields('f', array('fid'))
      ->execute() as $row
    ) {
      // Already exists. Great!
      $file = file_load($row->fid);

      // Check if loading went ok.
      if (empty($file)) {
        throw new TeakImportFileException("Failed to load file with fid=$row->fid.");
      }
      if ($file->uri !== $info->uri) {
        throw new TeakImportFileException("File with fid=$row->fid has wrong uri.");
      }
      return $file;
    }
  }

  protected function updateFileEntityIfNeeded($file, $info) {
    $fileNeedsUpdate = FALSE;

    if (1 != $file->status) {
      // Need to make this file public.
      $file->status = 1;
      $fileNeedsUpdate = TRUE;
    }

    if (empty($file->filename)) {
      $file->filename = trim(basename($file->uri));
      $fileNeedsUpdate = TRUE;
    }

    if ($fileNeedsUpdate) {
      file_save($file);
    }
  }

  protected function downloadIfMissingOrMismatch($info) {

    if (!is_file($info->uri)) {
      // No file exists in this place.
      $this->downloadCreateFile($info);
    }
    else {
      // File exists. But is it still up to date?
      $sha1 = sha1_file($info->uri);
      if ($sha1 === $info->sha1) {
        // Match! Leave the file here.
      }
      else {
        // Mismatch.
        $this->downloadReplaceFile($info);
      }
    }

    if (!is_file($info->uri)) {
      throw new FileManagerException("File does not exist at <code>$info->uri</code>.");
    }
  }

  /**
   * Download a file that does not exist yet.
   */
  protected function downloadCreateFile($info) {
    $realpath = drupal_realpath($info->uri);
    $dir = dirname($realpath);
    $this->mkdirIfNotExists($dir);
    $this->downloadFile($info);
  }

  protected function mkdirIfNotExists($dir) {
    if (is_dir($dir)) {
      return TRUE;
    }
    if (is_file($dir)) {
      throw new FileCreateException("Supposed directory <code>$dir</code> is a file, not a directory.");
    }
    $parent = dirname($dir);
    $this->mkdirIfNotExists($parent);
    $success = mkdir($dir);
    if (!$success) {
      throw new FileCreateException("Failed to <code>mkdir('$dir')</code>.\nCheck directory permissions at <code>$parent</code>.");
    }
  }

  /**
   * Download a file that does already exist, replacing the original.
   */
  protected function downloadReplaceFile($info) {
    $unlinkSuccess = @unlink($file);
    if (!$unlinkSuccess) {
      $realpath = drupal_realpath($info->uri);
      throw new FileUnlinkException("Failed to <code>unlink('$info->uri')</code> for replacing.\nCheck file permissions at <code>$realpath</code>.");
    }
    $this->downloadFile($file);
  }

  /**
   * At this point we can be sure the the directory exists, but the file does not.
   */
  protected function downloadFile($info) {
    // Download with cURL.
    // See http://stackoverflow.com/a/3938564/246724
    $fhandle = @fopen($info->uri, 'w');
    if (!$fhandle) {
      $dir = dirname(drupal_realpath($info->uri));
      throw new FileCreateException("Failed to <code>fopen('$info->uri')</code> for creating.<br/>Check directory permissions at <code>$dir</code>.");
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FILE, $fhandle);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_URL, $info->url);
    curl_exec($ch);
    curl_close($ch);
    // The file handle needs to be closed, or else the file will be corrupted.
    fclose($fhandle);
    if (!is_file($info->uri)) {
      $link = l($url, $url, array('absolute' => TRUE));
      throw new TeakImportFileException("Failed to download file to <code>$file->uri</code> from $link.");
    }
    if ($info->sha1 !== sha1_file($info->uri)) {
      throw new TeakImportFileException("Downloaded file at <code>$file->uri</code> has wrong sha1.");
    }
  }
}
