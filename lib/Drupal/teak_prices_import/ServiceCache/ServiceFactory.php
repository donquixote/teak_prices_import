<?php

namespace Drupal\teak_prices_import\ServiceCache;
use Drupal\teak_prices_import as m;


class ServiceFactory {

  function createService($key, $cache) {
    $method = 'get_' . $key;
    return $this->$method($cache);
  }

  protected function get_main($cache) {
    // Hardcoded url, who cares.
    return new m\Main($cache);
  }

  protected function get_importer($cache) {
    return new m\Importer();
  }

  protected function get_csvParser($cache) {
    return new m\CsvParser();
  }

  protected function get_fetcher($cache) {
    return new m\CurlFetcher('http://teakmeubelen.nl/prices.csv');
  }
}
