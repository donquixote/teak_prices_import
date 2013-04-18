<?php

namespace Drupal\teak_prices_import;


class Main {

  protected $services;

  function __construct($services) {
    $this->services = $services;
  }

  function updateProduct($sku) {
    $data = $this->productDataBySKU();
    if (isset($data[$sku])) {
      $importer = new Importer();
      $importer->importProduct($data[$sku]);
      return $importer->getLog();
    }
  }

  function updateAll() {
    $data = $this->productDataBySKU();
    $importer = new Importer();
    $importer->importProducts($data);
    return $importer->getLog();
  }

  function productDataBySKU() {
    $csv = $this->services->fetcher->fetchCsv();
    return $this->services->csvParser->parseCsvData($csv);
  }
}
