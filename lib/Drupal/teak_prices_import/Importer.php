<?php

namespace Drupal\teak_prices_import;
use Drupal\krumong as k;

use Drupal\teak_prices_import\Exception\FileManagerException;

class Importer {

  protected $log = array(
    'skip' => array(),
    'insert' => array(),
    'update' => array(),
  );

  protected $keys = array(
    'price' => TRUE,
    'title' => TRUE,
    'images' => TRUE,
  );

  /**
   * Find out which products have been updated.
   */
  function getLog() {
    return $this->log;
  }

  /**
   * Import only one product.
   *
   * @param array $row
   *   Imported data for a specific product.
   */
  function importProduct($row) {
    $this->rowAddId($row);
    $this->productSave($row);
  }

  /**
   * @param array $rows
   *   Array of imported products data by SKU.
   */
  function importProducts($rows) {
    $this->dataAddIds($rows);
    foreach ($rows as $sku => $row) {
      $this->productSave($row);
    }
  }

  /**
   * Save imported data for a product.
   * We need to do this with commerce_product_load() and commerce_product_save().
   * Doing it with plain database write would leave old values in the cache.
   *
   * @param array $row
   *   Row of imported data.
   * @param stdClass $product
   *   Existing product, or NULL if it doesn't exist yet.
   */
  protected function productSave($row) {

    // Record which fields have changed.
    $updated = array();
    $action = 'update';

    $product = $this->prepareProduct($row, $action);
    $wrapper = new ProductWrapper($product);

    // Set values.
    foreach ($this->keys as $key => $enabled) {
      if ($enabled && !empty($row[$key])) {
        $method = 'set_' . $key;
        try {
          $wrapper->$method($row[$key]);
        }
        catch (FileManagerException $e) {
          drupal_set_message($e->getMessage(), 'error');
          drupal_set_message('No further images will be imported in this request, to prevent a flood of errors.', 'error');
          // Stop with file imports for this request, to avoid tons of errors.
          $this->keys['images'] = FALSE;
        }
        catch (\Exception $e) {
          drupal_set_message($e->getMessage(), 'error');
          drupal_set_message('No further images will be imported in this request, to prevent a flood of errors.', 'error');
          // Stop with file imports for this request, to avoid tons of errors.
          $this->keys['images'] = FALSE;
        }
      }
    }

    $updated = $wrapper->getUpdated();

    // Save, if there are any changes.
    if (!empty($updated)) {
      commerce_product_save($product);
    }
    else {
      $action = 'skip';
    }

    $combined_key = $product->product_id . ' : ' . $row['sku'];
    $this->log[$action][$combined_key] = $updated;
  }

  protected function prepareProduct($row, &$action) {

    // Attempt to load existing product
    if (isset($row['product_id'])) {
      $product = commerce_product_load($row['product_id']);
    }

    // Create new product, if not exists
    if (empty($product)) {
      $product = commerce_product_new('product');
      $product->sku = $row['sku'];
      $product->title = 'import';
      $product->commerce_price[LANGUAGE_NONE][0] = array(
        'amount' => 9,
        'currency_code' => commerce_default_currency(),
      );
      $action = 'insert';
    }

    return $product;
  }

  /**
   * @param array $rows
   *   Array of imported product data by SKU.
   *
   * @return array
   *   Nested array of product data by SKU, with added id and revision id.
   */
  protected function dataAddIds(&$rows) {
    $q = db_select('commerce_product', 'cp');
    $q->fields('cp', array('sku', 'product_id', 'revision_id'));
    $q->condition('sku', array_keys($rows));
    foreach ($q->execute() as $row) {
      if (isset($rows[$row->sku])) {
        $rows[$row->sku]['product_id'] = $row->product_id;
      }
    }
  }

  /**
   * Enhance a row of imported product data,
   * by setting a product_id, if a product with that sku does exist.
   */
  protected function rowAddId(&$row) {
    $q = db_select('commerce_product', 'cp');
    $q->fields('cp', array('product_id', 'revision_id'));
    $q->condition('sku', $row['sku']);
    foreach ($q->execute() as $dbrow) {
      $row['product_id'] = $dbrow->product_id;
      break;
    }
  }
}
