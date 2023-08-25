<?php

namespace LogServer\System;
use InvalidArgumentException;
use JetBrains\PhpStorm\Pure;

class Time {

  // Sets the default timezone. (GMT in default)
  public static function setTimezone(String $timezone = "GMT"): void {
    if (!in_array($timezone, timezone_identifiers_list()) && strcmp($timezone, "GMT") != 0) {
      throw new InvalidArgumentException("Invalid timezone identifier.");
    }
    date_default_timezone_set($timezone);
  }

  // Retrieves the currently configured default timezone.
  #[Pure]
  public static function getTimezone(): String {
    return date_default_timezone_get();
  }

  // Formats a given timestamp as a date string in RFC2822 format. (current time in default)
  public static function dateRFC2822(?int $timestamp = null): String {
    return date(DATE_RFC2822, $timestamp ?? time());
  }

};
