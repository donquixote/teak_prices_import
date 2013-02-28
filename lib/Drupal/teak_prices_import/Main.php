<?php

namespace Drupal\teak_prices_import;


class Main {

  protected $services;

  function __construct($services) {
    $this->services = $services;
  }

  function updateProduct($product) {
    $prices = $this->pricesBySKU();
    return $this->services->importer->importProductPrice($product, $prices);
  }

  function updateAll() {
    $prices = $this->pricesBySKU();
    return $this->services->importer->importPrices($prices);
  }

  function pricesBySKU() {
    $csv = $this->services->fetcher->fetchCsv();
    return $this->services->csvParser->parseCsvPrices($csv);
  }
}
