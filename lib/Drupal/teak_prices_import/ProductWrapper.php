<?php

namespace Drupal\teak_prices_import;
use Drupal\krumong as k;

use Drupal\teak_prices_import\Exception\FileManagerException;
use Drupal\teak_prices_import\Exception\InvalidDataException;
use Drupal\teak_prices_import\Exception\FileUnlinkException;
use Drupal\teak_prices_import\Exception\FileCreateException;


class ProductWrapper {

  protected $fileManager;
  protected $product;
  protected $updated = array();
  protected $allowFileImport = TRUE;

  function __construct($product) {
    $this->product = $product;
    $this->fileManager = new FileManager();
  }

  function getUpdated() {
    return $this->updated;
  }

  /**
   * Set the price
   */
  function set_price($price) {
    $currency_code = commerce_default_currency();
    if (0
      || empty($this->product->commerce_price[LANGUAGE_NONE][0]['amount'])
      || empty($this->product->commerce_price[LANGUAGE_NONE][0]['currency_code'])
      || $this->product->commerce_price[LANGUAGE_NONE][0]['amount'] !== $price
      || $this->product->commerce_price[LANGUAGE_NONE][0]['currency_code'] !== $currency_code
    ) {
      $this->product->commerce_price[LANGUAGE_NONE][0] = array(
        'amount' => $price,
        'currency_code' => $currency_code,
      );
      $this->updated['price'] = ($price * 0.01) . ' ' . $currency_code;
    }
  }

  /**
   * Set the title
   */
  function set_title($title) {
    if (0
      || empty($this->product->title)
      || 'import' === $this->product->title
    ) {
      $this->product->title = $title;
      $this->updated['title'] = $title;
    }
  }

  /**
   * Set the image from url
   */
  function set_images($json_items) {

    // Json decode.
    $filesToImport = json_decode($json_items);
    if (!is_array($filesToImport)) {
      throw new InvalidDataException("Invalid json detected.");
    }

    // Sync file entities.
    $newFileEntities = array();
    foreach ($filesToImport as $info) {
      try {
        $file = $this->fileManager->syncFileEntity($info);
        $newFileEntities[$file->fid] = $file;
      }
      catch (FileUnlinkException $e) {
        // Blocking permissions on a single file.
        // Other files might still work ok.
        drupal_set_message($e->getMessage(), 'warning');
        continue;
      }
    }

    // Collect information about files already existing on this product.
    $fieldItems = array();
    if (!empty($this->product->field_image[LANGUAGE_NONE])) {
      $fieldItems = $this->product->field_image[LANGUAGE_NONE];
      foreach ($fieldItems as $delta => $item) {
        unset($newFileEntities[$item['fid']]);
      }
    }

    if (!empty($newFileEntities)) {
      $newItems = array();
      foreach ($newFileEntities as $fid => $file) {
        $item = array(
          'fid' => $file->fid,
          'uri' => $file->uri,
          'filemime' => $file->filemime,
          'status' => $file->status,
        );
        $newItems[] = $item;
        $fieldItems[] = $item;
      }
      $this->product->field_image[LANGUAGE_NONE] = $fieldItems;
      $this->updated['images'] = $newItems;
    }
  }
}
