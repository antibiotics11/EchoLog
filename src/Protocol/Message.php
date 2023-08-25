<?php

namespace LogServer\Protocol;
use LogServer\Protocol\Exception\UndefinedPropertyException;
use JetBrains\PhpStorm\{Immutable, ExpectedValues};

#[Immutable(Immutable::CONSTRUCTOR_WRITE_SCOPE)]
final class Message {

  public function __construct(
    public readonly String  $rawMessage,
    public readonly String  $isoDate,
    public readonly String  $hostname,
    public readonly String  $process,
    public readonly String  $body,
    #[ExpectedValues([0, 1, 2, 3, 4, 5, 6, 7])]
    public readonly ?int    $priority  = null,
    public readonly ?int    $pid       = null,
    public readonly ?String $timestamp = null
  ) {}

  public function __get(String $name) {
    throw new UndefinedPropertyException("Undefined property access.");
  }

  public function __set(String $name, Mixed $value) {
    throw new UndefinedPropertyException("Undefined property assignment.");
  }

  public function __toString(): String {
    $unixTimestamp = strtotime($this->isoDate);   // Convert ISO date to Unix timestamp.
    return sprintf("%s [%s]\r\nhostname: %s\r\npriority: %s (%s)\r\nprocess: %s [%s]\r\nmessage: %s\r\nmessage timestamp: %s\r\nraw: %s\r\n",
      $this->isoDate, ($unixTimestamp !== false ? $unixTimestamp : "unknown"),
      $this->hostname,
      $this->priority ?? "unknown", "unknown",
      $this->process, $this->pid ?? "unknown",
      $this->body,
      $this->timestamp ?? "unknown",
      $this->rawMessage
    );
  }

};
