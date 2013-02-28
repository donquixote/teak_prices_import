<?php

namespace Drupal\teak_prices_import;
use Drupal\krumong as k;


class Importer {

  function importProductPrice($product, $prices) {
    if (!isset($product->sku)) {
      throw new Exception("Product has no sku");
    }
    if (isset($prices[$product->sku])) {
      $this->productSavePrice($prices[$product->sku]);
    }
  }

  /**
   * @param array $prices
   *   Array of prices by SKU.
   */
  function importPrices($prices) {
    $prices_by_id = $this->pricesById($prices);
    $log = array();
    foreach ($prices_by_id as $id => $price) {
      $product = commerce_product_load($id);
      $result = $this->productSavePrice($product, $price);
      if (!empty($result)) {
        $log[$id] = $result;
      }
    }
    return $log;
  }

  /**
   * Save the price for a product.
   * We need to do this with commerce_product_load() and commerce_product_save().
   * Doing it with plain database write would leave old values in the cache.
   *
   * @param int $id
   *   The product id.
   * @param int $price
   *   Price (amount in EUR)
   */
  protected function productSavePrice($product, $price) {
    if (empty($product->commerce_price)) {
      return;
    }
    foreach ($product->commerce_price as $lang => &$items) {
      if (!isset($items[0])) {
        continue;
      }
      if ((int)$items[0]['amount'] === (int)$price) {
        continue;
      }
      $result = array(
        'sku' => $product->sku,
        'old' => $items[0]['amount'],
        'new' => $price,
      );
      $items[0]['amount'] = $price;
    }
    if (!empty($result)) {
      commerce_product_save($product);
      return $result;
    }
  }

  /**
   * @param array $prices
   *   Array of prices by SKU.
   *
   * @return array
   *   Nested array of prices by product id and revision id.
   */
  protected function pricesById($prices) {
    // Find SKUs
    $q = db_select('commerce_product', 'cp');
    $q->fields('cp', array('sku', 'product_id', 'revision_id'));
    $q->condition('sku', array_keys($prices));
    $prices_by_id = array();
    foreach ($q->execute() as $row) {
      if (isset($prices[$row->sku])) {
        $prices_by_id[$row->product_id] = $prices[$row->sku];
      }
    }
    return $prices_by_id;
  }
}
