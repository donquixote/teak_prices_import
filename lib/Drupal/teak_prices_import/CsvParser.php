<?php

namespace Drupal\teak_prices_import;
use Drupal\krumong as k;


class CsvParser {

  function parseCsvPrices($csv) {
    $lines = explode("\n", $csv);
    $header = str_getcsv(array_shift($lines));
    $prices = array();
    foreach ($lines as $line) {
      @list($sku, $price) = str_getcsv($line);
      if (empty($sku) || !isset($price)) {
        continue;
      }
      if (isset($prices[$sku])) {
        throw new Exception("More than one price given for sku $sku.");
      }
      $prices[$sku] = $price;
    }
    return $prices;
  }
}
