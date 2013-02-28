<?php

use Drupal\krumong as k;

/**
 * Page callback for /teak-prices-import/% and /teak-prices-import/all
 *
 * @param object $product
 *   Result of commerce_product_load(),
 *   or null in case that all products are to be updated.
 */
function teak_prices_import_page($product = NULL) {

  $main = teak_prices_import();
  if (!empty($product)) {
    $main->updateProduct($product);
  }
  else {
    $ids = $main->updateAll();
    k\dpm($ids);
    return print_r($ids, TRUE);
  }
}
