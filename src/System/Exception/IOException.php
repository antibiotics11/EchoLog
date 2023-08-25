<?php

namespace LogServer\System\Exception;
use Exception;
use JetBrains\PhpStorm\Immutable;

#[Immutable]
final class IOException extends Exception {

  public readonly String $path;

  public function __construct(String $message = "", String $path = "", int $exceptionCode = 0, ?Exception $previous = null) {
    parent::__construct($message, $exceptionCode, $previous);
    $this->path = $path;
  }

};