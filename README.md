**Teak Prices Import** keeps product prices synchronized across different Drupal Commerce sites based on product SKUs.

This module is currently not directly useful as is but it can be a
good starting point if you want to build something similar for a
family of Drupal Commerce sites.

It's syncing from variable `teak_import_prices_url`, you probably want
to add the following to your website's settings.php:

```
$conf['teak_import_prices_url'] = 'http://your_url/some.csv';
```

You can run the import from drush:

```
drush teak_import_prices
```


It's used on [teakmoebel.com](http://teakmoebel.com/) and
[teaktisch.com](http://teaktisch.com/).

Available under the GNU General Public License version 2 or later.
