<?php

namespace LogServer\Protocol;
use JetBrains\PhpStorm\{Immutable, ExpectedValues, Pure};

#[Immutable(Immutable::CONSTRUCTOR_WRITE_SCOPE)]
final class Message {
  use PropertyAccessTrait;

  public function __construct(
    public readonly String    $rawMessage,
    public readonly String    $isoDate,
    public readonly String    $hostname,
    public readonly String    $process,
    public readonly String    $body,
    public readonly ?Priority $priority  = null,
    public readonly ?int      $pid       = null,
    public readonly ?String   $timestamp = null
  ) {}

  public function __toString(): String {

    $unixTimestamp = strtotime($this->isoDate);   // Convert ISO date to Unix timestamp.

    return sprintf("%s\r\n%s\r\n%s\r\n%s\r\n%s\r\n%s\r\n%s\r\n",
      sprintf("Timestamp: %s [%s]",
        $this->isoDate,
        ($unixTimestamp !== false ? (String)$unixTimestamp : "unknown")
      ),
      sprintf("Host Name: %s", $this->hostname),
      sprintf("Priority: %s (%s)",
        $this->priority?->priority ?? "unknown",
        $this->priority === null ? "unknown" : sprintf("%s[%d] %s[%d]",
          $this->priority->facility->name,
          $this->priority->facility->value,
          $this->priority->securityLevel->name,
          $this->priority->securityLevel->value
        )
      ),
      sprintf("Process Info: %s [%s]", $this->process, $this->pid ?? "unknown"),
      sprintf("Message: %s", $this->body),
      sprintf("Message Timestamp: %s", $this->timestamp ?? "unknown"),
      sprintf("Raw: %s", $this->rawMessage)
    );

  }

};
