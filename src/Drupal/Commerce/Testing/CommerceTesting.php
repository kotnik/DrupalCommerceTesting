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
   * Reloads the page.
   */
  public function reload() {
    $this->session->reload();
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
    return $this->getPage()->getContent();
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
   * @param $verbose
   *   If TRUE, echo out installation info.
   */
  public function install($db_name, $db_user, $db_pass, $site_mail, $verbose = FALSE) {
    $this->visit();

    // Get installation tasks.
    $tasks = $this->getInstallTasks();
    $current_task = $this->getCurrentTask($tasks);

    switch($current_task) {

    case 'choose_language':
      // Language selection.
      !$verbose || $this->installLogStep('choose_language');
      $button = $this->getPage()->find('css', 'input#edit-submit');
      $button->press();

    case 'set_up_database':
      // Database setup.
      !$verbose || $this->installLogStep('set_up_database');
      $input_name = $this->getPage()->find('css', 'input#edit-mysql-database');
      $input_user = $this->getPage()->find('css', 'input#edit-mysql-username');
      $input_pass = $this->getPage()->find('css', 'input#edit-mysql-password');
      // We assume MySQL being used.
      if (!$input_name || !$input_user || !$input_pass) {
        throw new \Drupal\Commerce\Exception\CommerceException('Could not find MySQL parameters inputs.');
      }
      $input_name->setValue($db_name);
      $input_user->setValue($db_user);
      $input_pass->setValue($db_pass);
      $button = $this->getPage()->find('css', 'input#edit-save');
      $button->press();

    case 'install_profile':
      // Profile installation.
      !$verbose || $this->installLogStep('install_profile');
      while ($this->getPage()->find('css', 'div.progress') && $this->getPage()->find('css', 'div.percentage')->getText() != '100%') {
        $this->reload();
        // Pause spares poor CPU.
        sleep(1);
        !$verbose || print(str_replace('.', '. ', $this->getPage()->find('css', 'div.message')->getText()) . "\n");
      }
      $this->visit('install.php?locale=en');

    case 'configure_site':
      // Site configuration.
      !$verbose || $this->installLogStep('configure_site');
      $input_mail = $this->getPage()->find('css', 'input#edit-site-mail');
      $input_mail->setValue($site_mail);
      $button = $this->getPage()->find('css', 'input#edit-submit');
      $button->press();

    case 'configure_store':
      // Store configuration.
      !$verbose || $this->installLogStep('configure_store');
      $button = $this->getPage()->find('css', 'input#edit-submit');
      $button->press();
      sleep(2);
      while ($this->getPage()->find('css', 'div.progress') && $this->getPage()->find('css', 'div.percentage')->getText() != '100%') {
        $this->reload();
        sleep(1);
        !$verbose || print(str_replace('.', '. ', $this->getPage()->find('css', 'div.message')->getText()) . "\n");
      }

    case 'finished':
      // Installation finished.
      $this->visit();
      break;

    case 'choose_profile':
      throw new \Drupal\Commerce\Exception\CommerceException('Installation profile should be already selected for us.');
      break;

    case 'verify_requirements':
      throw new \Drupal\Commerce\Exception\CommerceException('Unmet requirements for Drupal installation.');
      break;

    default:
      throw new \Drupal\Commerce\Exception\CommerceException('Uknown installation task $current_task.');
    }

    // Assert we got to the front page.
    if ($this->getPage()->find('css', 'h2.site-name')->getText() != 'Commerce Kickstart') {
      throw new \Drupal\Commerce\Exception\CommerceException('Installation failed, no front page can be found.');

    }
    !$verbose || print("Installation finished.\n");
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
        return $name;
      }
    }
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
}
