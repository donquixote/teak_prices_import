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
    $url = variable_get('teak_prices_import_url');
    if (empty($url)) {
      throw new Exception("The variable 'teak_prices_import_url' needs to be set, e.g. with 'drush vset'");
    }
    return new m\CurlFetcher($url);
  }
}
