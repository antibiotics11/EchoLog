<?php

namespace LogServer\Protocol\Exception;
use Exception;
use JetBrains\PhpStorm\Immutable;

#[Immutable]
final class UndefinedPropertyException extends Exception {

  public function __construct(String $message, int $exceptionCode = 0, ?Exception $previous = null) {
    parent::__construct($message, $exceptionCode, $previous);
  }

};
