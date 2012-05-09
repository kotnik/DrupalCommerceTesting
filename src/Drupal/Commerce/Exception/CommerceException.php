<?php

namespace Drupal\Commerce\Exception;

/**
 * Commerce Exception.
 */
class CommerceException extends \Exception
{
  /**
   * Initializes Commerce exception.
   */
  public function __construct($message, $code = 0, \Exception $previous = null) {
    parent::__construct($message);
  }
}
