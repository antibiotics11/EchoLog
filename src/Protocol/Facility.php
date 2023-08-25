<?php

namespace LogServer\Protocol;
use JetBrains\PhpStorm\Pure;

enum Facility: int {

  case KERN         = 0;
  case USER         = 1;
  case EMAIL        = 2;
  case DAEMON       = 3;
  case AUTH         = 4;
  case SYSLOG       = 5;
  case LPR          = 6;
  case NEWS         = 7;
  case UUCP         = 8;
  case CRON         = 9;
  case AUTHPRIV     = 10;
  case FTP          = 11;
  case NTP          = 12;
  case SECURITY     = 13;
  case CONSOLE      = 14;
  case SOLARIS_CRON = 15;
  case LOCAL_0      = 16;
  case LOCAL_1      = 17;
  case LOCAL_2      = 18;
  case LOCAL_3      = 19;
  case LOCAL_4      = 20;
  case LOCAL_5      = 21;
  case LOCAL_6      = 22;
  case LOCAL_7      = 23;

  #[Pure]
  public static function getDescription(self|int $facility): String {

    if ($facility instanceof self) {
      $facility = $facility->value;
    }

    return match ($facility) {
      self::KERN->value          => "Kernel",
      self::USER->value          => "User-level",
      self::EMAIL->value         => "Mail",
      self::DAEMON->value        => "System",
      self::AUTH->value,
      self::AUTHPRIV->value      => "Security/authentication",
      self::SYSLOG->value        => "Syslog",
      self::LPR->value           => "Printer",
      self::NEWS->value          => "News",
      self::UUCP->value          => "UUCP",
      self::CRON->value          => "Cron",
      self::FTP->value           => "FTP",
      self::NTP->value           => "NTP",
      self::SECURITY->value      => "Log audit",
      self::CONSOLE->value       => "Log alert",
      self::SOLARIS_CRON->value  => "Scheduling",
      self::LOCAL_0->value,
      self::LOCAL_1->value,
      self::LOCAL_2->value,
      self::LOCAL_3->value,
      self::LOCAL_4->value,
      self::LOCAL_5->value,
      self::LOCAL_6->value,
      self::LOCAL_7->value       => "Local"
    };

  }

};
