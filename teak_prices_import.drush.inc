<?php

/**
 * Implements hook_drush_command()
 */
function teak_prices_import_drush_command() {
  $items['teak-prices-import'] = array(
    'description' => 'Import/sync all product prices from the reference website.',
  );
  return $items;
}

/**
 * Drush callback for teak-prices-import
 */
function drush_teak_prices_import() {
  $result = teak_prices_import()->updateAll();
  $n = count($result);
  drush_print("$n product prices updated. Probably nothing has changed.", 'notice');
}
