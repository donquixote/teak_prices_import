<?php

namespace Drupal\teak_prices_import;
use Drupal\krumong as k;


class Importer {

  protected $log = array(
    'skip' => array(),
    'insert' => array(),
    'update' => array(),
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

    // Attempt to load existing product
    if (isset($row['product_id'])) {
      $product = commerce_product_load($row['product_id']);
    }

    // Record which fields have changed.
    $updated = array();
    $action = 'update';

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

    // Set the price
    if (!empty($row['price'])) {
      $currency_code = commerce_default_currency();
      if (0
        || empty($product->commerce_price[LANGUAGE_NONE][0]['amount'])
        || empty($product->commerce_price[LANGUAGE_NONE][0]['currency_code'])
        || $product->commerce_price[LANGUAGE_NONE][0]['amount'] !== $row['price']
        || $product->commerce_price[LANGUAGE_NONE][0]['currency_code'] !== $currency_code
      ) {
        $product->commerce_price[LANGUAGE_NONE][0] = array(
          'amount' => $row['price'],
          'currency_code' => $currency_code,
        );
        $updated['price'] = ($row['price'] * 0.01) . ' ' . $currency_code;
      }
    }

    // Set the title, if given
    if (!empty($row['title'])) {
      if (0
        || empty($product->title)
        || 'import' === $product->title
      ) {
        $product->title = $row['title'];
        $updated['title'] = $row['title'];
      }
    }

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
