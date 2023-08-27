<?php

namespace LogServer\Protocol;
use InvalidArgumentException;
use JetBrains\PhpStorm\Immutable;

#[Immutable(Immutable::CONSTRUCTOR_WRITE_SCOPE)]
final class Priority {
  use PropertyAccessTrait;

  private function __construct(
    public readonly int           $priority,
    public readonly Facility      $facility,
    public readonly SecurityLevel $securityLevel
  ) {}

  public static function getByFacilitySeverity(Facility $facility, SecurityLevel $securityLevel): self {
    $priority = $facility->value * 8 + $securityLevel->value;
    return new self($priority, $facility, $securityLevel);
  }

  public static function getByPriority(int $priority): self {

    $facilityValue = floor($priority / 8);
    $securityLevelValue = $priority % 8;

    $facility = Facility::tryFrom($facilityValue);
    $securityLevel = SecurityLevel::tryFrom($securityLevelValue);
    if ($facility === null || $securityLevel === null) {
      throw new InvalidArgumentException("Invalid priority");
    }

    return new self($priority, $facility, $securityLevel);

  }

};