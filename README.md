Drupal Commerce Testing
=======================

This is still *work in progress*.

Installation
------------

    wget http://getcomposer.org/composer.phar
    php composer.phar install

Usage
-----

``` php
<?php

require_once 'vendor/autoload.php';

// Site link and credentials:
$commerceUrl = 'http://commerce.example.com';
$commerceUser = 'admin';
$commercePassword = 'password';

try {
  // Initialize Drupal Commerce website:
  $commerceSite = new \Drupal\Commerce\Testing\CommerceTesting(
    $commerceUrl,
    $commerceUser,
    $commercePassword
  );
  // Login to the Drupal Commerce website:
  $commerceSite->login();
} catch (Exception $e) {
  echo 'Caught exception: ', $e->getMessage(), "\n";
  exit(1);
}

```

License
-------

Licensed under the MIT license.
