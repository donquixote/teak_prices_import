<?php

namespace Drupal\teak_prices_import;


class CurlFetcher {

  protected $url;

  function __construct($url) {
    $this->url = $url;
  }

  function fetchCsv() {
    $ch = curl_init($this->url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $csv = curl_exec($ch);
    curl_close($ch);
    return $csv;
  }
}
