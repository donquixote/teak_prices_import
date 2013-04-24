<?php

namespace Drupal\teak_prices_import;
use Drupal\krumong as k;
use Exception;


class CsvParser {

  function parseCsvData($csv) {

    // Build the header
    $lines = explode("\n", $csv);
    $header = str_getcsv(array_shift($lines));
    $header = $this->normalizeHeader($header);

    // Collect product data
    $products = array();
    foreach ($lines as $line) {

      // Build keyed array for this product.
      $line = str_getcsv($line);
      $product = array();
      foreach ($header as $key => $index) {
        if (empty($line[$index])) {
          continue 2;
        }
        $product[$key] = @$line[$index];
      }
      $sku = $product['sku'];

      // Check for duplicates
      if (isset($products[$sku])) {
        throw new Exception("More than one row of data given for sku '$sku'.");
      }
      $products[$sku] = $product;
    }

    // return products.
    return $products;
  }

  protected function normalizeHeader($header) {

    // Transform the header fields.
    $map = array(
      'artikelnummer' => 'sku',
      'sku' => 'sku',
      'price' => 'price',
      'prijs' => 'price',
      'title' => 'title',
      'titel' => 'title',
      'images' => 'images',
    );
    $normalized = array();
    foreach ($header as $index => $name) {
      $name = strtolower($name);
      if (isset($map[$name])) {
        $key = $map[$name];
        $normalized[$key] = $index;
      }
    }

    // Check if all required fields are present.
    foreach (array('sku', 'price') as $required_key) {
      if (!isset($normalized[$required_key])) {
        throw new Exception("Key '$required_key' not present in CSV.");
      }
    }

    // Return the normalized header.
    return $normalized;
  }
}
