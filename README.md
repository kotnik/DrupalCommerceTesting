Drupal Commerce Testing
=======================

Behat/Mink powered tests.

Following screnarios are supported:

* Installation
* Visiting a page
* Login
* Adding to the cart
* Checkout

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
$commerceUser = 'user';
$commercePassword = 'password';

try {
  // Initialize Drupal Commerce website:
  $commerceSite = new \Drupal\Commerce\Testing\CommerceTesting(
    $commerceUrl,
    $commerceUser,
    $commercePassword
  );

  // Install Drupal Commerce website:
  $commerceSite->install('database', 'user', 'password', 'user@example.com');

  // Login to the Drupal Commerce website:
  $commerceSite->login();

  // Visit the front page:
  $commerceSite->visit();

  // Add product with product display node ID 10 to the cart:
  $comerceSite->addToCart(10);

  // Checkout:
  $commerceSite->checkout()
} catch (Exception $e) {
  echo 'Caught exception: ', $e->getMessage(), "\n";
  exit(1);
}

```

License
-------

Licensed under the MIT license.
