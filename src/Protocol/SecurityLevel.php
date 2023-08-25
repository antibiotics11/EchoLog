<?php

namespace LogServer\Protocol;
use JetBrains\PhpStorm\Pure;

enum SecurityLevel: int {

  case EMERG   = 0;
  case ALERT   = 1;
  case CRIT    = 2;
  case ERR     = 3;
  case WARNING = 4;
  case NOTICE  = 5;
  case INFO    = 6;
  case DEBUG   = 7;

  #[Pure]
  public static function getDescription(self|int $securityLevel): String {

    if ($securityLevel instanceof self) {
      $securityLevel = $securityLevel->value;
    }

    return match ($securityLevel) {
      self::EMERG->value   => "Emergency",
      self::ALERT->value   => "Alert",
      self::CRIT->value    => "Critical",
      self::ERR->value     => "Error",
      self::WARNING->value => "Warning",
      self::NOTICE->value  => "Notice",
      self::INFO->value    => "Informational",
      self::DEBUG->value   => "Debug",
      default              => "Unknown"
    };

  }

};
