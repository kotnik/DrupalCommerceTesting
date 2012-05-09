<?php

namespace Drupal\Commerce\Testing;

/**
 * Commerce Testing class.
 */
class CommerceTesting
{
  /**
   * Mink session.
   */
  private $session;

  /**
   * Drupal base Url.
   */
  private $baseUrl;

  /**
   * Drupal username.
   */
  private $username;

  /**
   * Drupal password.
   */
  private $password;

  /**
   * Initializes CommerceTesting class.
   *
   * @param $baseUrl
   *   Url to the Drupal Commerce installation without trailing slash.
   * @param $username
   *   Drupal Commerce username.
   * @param $password
   *   Drupal Commerce password.
   */
  public function __construct($baseUrl, $username = '', $password = '') {
    $this->session = new \Behat\Mink\Session(
      new \Behat\Mink\Driver\GoutteDriver(),
      new \Behat\Mink\Selector\SelectorsHandler()
    );
    $this->baseUrl = $baseUrl . '/';
    $this->username = $username;
    $this->password = $password;
  }

  /**
   * Visits page.
   *
   * @param $url
   *   Optional Url suffix in drupal l() form.
   */
  public function visit($url = '') {
    $this->session->visit($this->baseUrl . $url);
    if (floor($this->session->getStatusCode() / 100) != 2) {
      throw new \Drupal\Commerce\Exception\CommerceException('Unsuccessful, server returned ' . $this->session->getStatusCode() . ' code.');
    }
  }

  /**
   * Gets the page.
   *
   * @return
   *   An instance of Mink DocumentElement class.
   */
  public function getPage() {
    return $this->session->getPage();
  }

  /**
   * Logs user in.
   */
  public function login() {
    if (empty($this->username) && empty($this->password)) {
      throw new \Drupal\Commerce\Exception\CommerceException("Username and password must be provided for login.");
    }

    $this->visit('user');
    $username = $this->getPage()->find('css', 'input#edit-name');
    $password = $this->getPage()->find('css', 'input#edit-pass');
    $loginSubmit = $this->getPage()->find('css', 'input#edit-submit');

    $username->setValue($this->username);
    $password->setValue($this->password);
    $loginSubmit->press();

    if ($this->getPage()->find('css', 'div.error')) {
      throw new \Drupal\Commerce\Exception\CommerceException("Unable to login, please check username and password or if the account is blocked.");
    }
  }
}
