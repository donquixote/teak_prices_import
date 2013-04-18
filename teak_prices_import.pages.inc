<?php

use Drupal\krumong as k;

/**
 * Page callback for /teak-prices-import/% and /teak-prices-import/all
 *
 * @param object $product
 *   Result of commerce_product_load(),
 *   or null in case that all products are to be updated.
 */
function teak_prices_import_page($sku = NULL) {

  $main = teak_prices_import();
  if (!empty($product)) {
    $main->updateProduct($sku);
  }
  else {
    $log = $main->updateAll();
    $out = '';
    foreach (array(
      'insert' => "New products that were created",
      'update' => "Existing products that were updated",
      'skip' => "Existing products that have not changed",
    ) as $key => $msg) {
      $n = count($log[$key]);
      $out .= "$msg: $n";
      $out .= krumong('main')->dump($log[$key]);
    }
    return $out;
  }
}
