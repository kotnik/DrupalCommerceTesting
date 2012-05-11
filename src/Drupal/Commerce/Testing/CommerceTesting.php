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
   * Verbosity.
   */
  private $verbose;

  /**
   * Initializes CommerceTesting class.
   *
   * @param $baseUrl
   *   Url to the Drupal Commerce installation without trailing slash.
   * @param $username
   *   Drupal Commerce username.
   * @param $password
   *   Drupal Commerce password.
   * @param $timeout
   *   Request timeout in seconds.
   */
  public function __construct($baseUrl, $username = '', $password = '', $timeout = 60) {
    $this->session = new \Behat\Mink\Session(
      new \Behat\Mink\Driver\GoutteDriver(
        new \Goutte\Client(array('timeout' => $timeout))
      ),
      new \Behat\Mink\Selector\SelectorsHandler()
    );
    $this->baseUrl = $baseUrl . '/';
    $this->username = $username;
    $this->password = $password;
    $this->verbose = FALSE;
  }

  /**
   * Turns on verbosity.
   */
  public function beVerbose() {
    $this->verbose = TRUE;
  }

  /**
   * Visits page.
   *
   * @param $url
   *   Optional Url suffix in drupal l() form.
   */
  public function visit($url = '') {
    $this->session->visit($this->baseUrl . $url);
    !$this->verbose || print("Visited " . $this->baseUrl . $url . ".\n");
    // Fail on all status codes but 2XX.
    if (floor($this->session->getStatusCode() / 100) != 2) {
      $this->throwError('Unsuccessful, server returned ' . $this->session->getStatusCode() . ' code.');
    }
  }

  /**
   * Reloads the page.
   */
  public function reload() {
    $this->session->reload();
    !$this->verbose || print("Page reloaded.\n");
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
   * Gets HTML content of the page.
   *
   * @return
   *   Complete page HTML.
   */
  public function getPageContent() {
    !$this->verbose || print("Page content retrieved.\n");
    return $this->getPage()->getContent();
  }

  /**
   * Logs user in.
   */
  public function login() {
    if (empty($this->username) && empty($this->password)) {
      $this->throwError('Username and password must be provided for login.');
    }

    $this->visit('user');
    $this->assertExistance('input#edit-name')->setValue($this->username);
    $this->assertExistance('input#edit-pass')->setValue($this->password);
    $this->assertExistance('input#edit-submit')->press();

    if ($this->assertExistance('div.error')) {
      $this->throwError('Unable to login, please check username and password or if the account is blocked.');
    }
    !$this->verbose || print("Login performed.\n");
  }

  /**
   * Adds product to the cart.
   *
   * @param $nid
   *   Product display node identifier.
   */
  public function addToCart($nid) {
    if (!is_numeric($nid)) {
      $this->throwError('Only product displays (nodes) can be added to the cart and you must provide node ID.');
    }

    $this->visit('node/' . $nid);
    $this->assertExistance('form.commerce-add-to-cart input#edit-submit')->press();

    if (!$this->assertText('div.status', 'added to')) {
      $this->throwError('Failed to add node/' . $nid . ' to the cart.');
    }
    !$this->verbose || print('Added node/' . $nid . " to the cart.\n");
  }

  /**
   * Performs cart checkout.
   *
   * @param $level
   *   Checkout is made of 4 levels. With this paramter you can set
   *   which level you want to reach. Following levels are available:
   *     1 = Start checkout.
   *     2 = 1 and complete checkout information.
   *     3 = 1, 2 and select shipping method.
   *     4 = 1, 2, 3 and do the review completing the checkout.
   */
  public function checkout($level = 4) {
    $this->visit('cart');

    if ($this->assertText('div.cart-empty-page', 'is empty')) {
      $this->throwError('Can not checkout with the empty cart.');
    }

    $current_level = 0;
    while ($current_level++ < $level) {
      switch ($current_level) {

      case 1:
        // Start checkout from the cart.
        $this->assertExistance('input#edit-checkout')->press();
        !$this->verbose || print("Checkout started.\n");
        break;

      case 2:
        // Checkout form.
        $this->assertExistance('input#edit-account-login-mail')->setValue($this->randomString(10) . '@example.com');
        foreach (
          array(
            'edit-customer-profile-shipping-commerce-customer-address-und-0-name-line',
            'edit-customer-profile-shipping-commerce-customer-address-und-0-thoroughfare',
            'edit-customer-profile-shipping-commerce-customer-address-und-0-locality',
            'edit-customer-profile-shipping-commerce-customer-address-und-0-postal-code',
            'edit-customer-profile-billing-commerce-customer-address-und-0-name-line',
            'edit-customer-profile-billing-commerce-customer-address-und-0-thoroughfare',
            'edit-customer-profile-billing-commerce-customer-address-und-0-locality',
            'edit-customer-profile-billing-commerce-customer-address-und-0-postal-code'
          ) as $field) {
          $this->assertExistance('input#' . $field)->setValue($this->randomString(5));
        }
        foreach (
          array(
            'edit-customer-profile-shipping-commerce-customer-address-und-0-administrative-area',
            'edit-customer-profile-billing-commerce-customer-address-und-0-administrative-area'
          ) as $field) {
          $this->assertExistance('select#' . $field)->selectOption('MN');
        }
        $this->assertExistance('input#edit-continue')->press();

        if ($this->assertText('h2.element-invisible', 'Errors on form')) {
          $this->throwError('Checkout failed with errors on the checkout form.');
        }
        !$this->verbose || print("Checkout information submited.\n");
        break;

      case 3:
        // Select default shipping method.
        $this->assertExistance('input#edit-continue')->press();

        if ($this->assertText('h2.element-invisible', 'Errors on form')) {
          $this->throwError('Checkout failed with errors on the shipping form.');
        }
        !$this->verbose || print("Shipping method submited.\n");
        break;

      case 4:
        // Use default (example) payment method.
        $this->assertExistance('input#edit-commerce-payment-payment-details-name')->setValue($this->randomString(5));

        $this->assertExistance('input#edit-continue')->press();

        !$this->verbose || print("Payment method submited.\n");

        if ($this->assertText('h2.element-invisible', 'Errors on form')) {
          $this->throwError('Checkout failed with errors on the review form.');
        }

        // Assert checkout completition.
        $checkout_link = $this->assertExistance('div.checkout-completion-message a')->getAttribute('href');
        !$this->verbose || print("Checkout complete. View it here: $checkout_link.\n");
        break;
      }
    }
  }

  /**
   * Installs Commerce Kickstart V2.
   *
   * This method is tailored to Drupal Commerce Kickstart distribution.
   *
   * @param $db_name
   *   Database name.
   * @param $db_user
   *   Database username.
   * @param $db_pass
   *   Database password.
   * @param $site_mail
   *   Website mail.
   */
  public function install($db_name, $db_user, $db_pass, $site_mail) {
    $this->visit();

    // Get installation tasks.
    $tasks = $this->getInstallTasks();
    $current_task = $this->getCurrentTask($tasks);

    switch($current_task) {

    case 'choose_language':
      // Language selection.
      !$this->verbose || $this->installLogStep('choose_language');
      $this->assertExistance('input#edit-submit')->press();
      !$this->verbose || print("Language selected.\n");

    case 'set_up_database':
      // Database setup. We assume MySQL being used.
      !$this->verbose || $this->installLogStep('set_up_database');
      $this->assertExistance('input#edit-mysql-database')->setValue($db_name);
      $this->assertExistance('input#edit-mysql-username')->setValue($db_user);
      $this->assertExistance('input#edit-mysql-password')->setValue($db_pass);
      $this->assertExistance('input#edit-save')->press();
      !$this->verbose || print("Database set up.\n");

    case 'install_profile':
      // Profile installation.
      !$this->verbose || $this->installLogStep('install_profile');
      while ($this->assertExistance('div.progress', FALSE) && !$this->assertText('div.percentage', '100%')) {
        $this->reload();
        // Pause spares poor CPU.
        sleep(1);
        !$this->verbose || print(str_replace('.', '. ', $this->assertExistance('div.message', FALSE)->getText()) . "\n");
      }
      $this->visit('install.php?locale=en');
      !$this->verbose || print("Profile installed.\n");

    case 'configure_site':
      // Site configuration.
      !$this->verbose || $this->installLogStep('configure_site');
      $this->assertExistance('input#edit-site-mail')->setValue($site_mail);
      $this->assertExistance('input#edit-submit')->press();
      !$this->verbose || print("Site configured.\n");

    case 'configure_store':
      // Store configuration.
      !$this->verbose || $this->installLogStep('configure_store');
      $this->assertExistance('input#edit-submit')->press();
      sleep(2);
      while ($this->assertExistance('div.progress', FALSE) && !$this->assertText('div.percentage', '100%')) {
        $this->reload();
        sleep(1);
        !$this->verbose || print(str_replace('.', '. ', $this->assertExistance('div.message', FALSE)->getText()) . "\n");
      }
      !$this->verbose || print("Store configured.\n");

    case 'finished':
      // Installation finished.
      $this->visit();
      break;

    case 'choose_profile':
      $this->throwError('Installation profile should be already selected for us.');
      break;

    case 'verify_requirements':
      $this->throwError('Unmet requirements for Drupal installation.');
      break;

    default:
      $this->throwError('Uknown installation task $current_task.');
    }

    // Assert we got to the front page.
    if (!$this->assertText('h2.site-name', 'Commerce Kickstart')) {
      $this->throwError('Installation failed, no front page can be found.');

    }
    !$this->verbose || print("Installation finished.\n");
  }

  /**
   * Gets Drupal Commerce installation tasks.
   *
   * @return
   *   Array with all the tasks and its statuses.
   */
  protected function getInstallTasks() {
    $tasks = array();
    $task_list = $this->getPage()->findAll('css', '.task-list li');

    foreach ($task_list as $task_el) {
      $tasks[strtolower(str_replace(' ', '_', trim(preg_replace('/\s*\([^)]*\)/', '', $task_el->getText()))))] = $task_el->getAttribute('class') ? $task_el->getAttribute('class') : 'todo';
    }

    !$this->verbose || print('Found ' . count($tasks) . " installation tasks.\n");

    return $tasks;
  }

  /**
   * Gets current installation task.
   *
   * @param $tasks
   *   Array with the tasks.
   * @return
   *   Name of the current installation task.
   *
   * @see this::getInstallTasks()
   */
  protected function getCurrentTask($tasks) {
    foreach ($tasks as $name => $status) {
      if ($status == 'active') {
        !$this->verbose || print('Current installation taks is ' . $name . ".\n");
        return $name;
      }
    }
  }

  /**
   * Asserts element exsistance.
   *
   * @param $selector
   *   CSS selector for the element.
   * @param $force_existance
   *   Throw exception if element is not found.
   * @return
   *   Instance of DocumentElement class.
   */
  protected function assertExistance($selector, $force_existance = TRUE) {
    $element = $this->getPage()->find('css', $selector);
    if (!$element && $force_existance) {
      $this->throwError("Element $selector does not exist.");
    }

    !$this->verbose || print(($element ? 'Found' : 'Not found') . ' element ' . $selector . ".\n");

    return $element;
  }

  /**
   * Asserts element text.
   *
   * @param $selector
   *   CSS selector for the element, or DocumentElement object.
   * @param $text
   *   Text to assert in the element.
   * @return
   *   Instance of DocumentElement class or empty variable if not found.
   *
   * In case the text is not found, and exception will be thrown.
   */
  protected function assertText($selector, $text) {
    if (!is_object($selector) || !$selector instanceof Behat\Mink\Element\DocumentElement) {
      $element = $this->assertExistance($selector, FALSE);
    }

    if ($element && strpos($element->getText(), $text) !== FALSE) {
      !$this->verbose || print('Found "' . $text . '" in ' . $selector . ".\n");
      return TRUE;
    }

    !$this->verbose || print('Did not found "' . $text . '" in ' . $selector . ".\n");
    return FALSE;
  }

  /**
   * Throws CommerceTesting exception.
   *
   * @param $message
   *   Message for the exception.
   */
  protected function throwError($message) {
    throw new \Drupal\Commerce\Exception\CommerceException($message);
  }

  /**
   * Logs (outputs) current installation step.
   *
   * @param $step
   *   Current installation step.
   */
  private function installLogStep($step) {
    echo "Step: ";
    echo ucfirst(str_replace('_', ' ', $step));
    echo "\n";
  }

  /**
   * Makes a random string.
   *
   * @param $length
   *   The length of the random string.
   * @param $numeralOnly
   *   If TRUE, return only random numbers.
   * @return
   *   Random string.
   */
  private function randomString($length = 10, $numeralOnly = FALSE) {
    $base = $numeralOnly ? '123456789' : 'abcdefghjkmnpqrstwxyz';
    $max = strlen($base) - 1;
    $string = '';
    mt_srand(microtime(TRUE) * 1000000);
    while (strlen($string) < $length) {
      $string .= $base{mt_rand(0, $max)};
    }
    return $string;
  }
}
