<?php

namespace LogServer\Protocol;
use InvalidArgumentException;

class Parser {

  private const SYSLOG_MESSAGE_PATTERNS = [
    '/<(\d+)>(\w+\s+\d+\s\d+:\d+:\d+)\s(\w+)\s(\w+):\s\[(\s*\d+\.\d+)\]\s(.+)/',
    '/(\w+\s+\d+\s\d+:\d+:\d+)\s(\w+)\s(\w+):\s\[(\s*\d+\.\d+)\]\s(.+)/',
    '/<(\d+)>(\w+\s+\d+\s\d+:\d+:\d+)\s(\w+)\s(\w+):\s(.+)/',
    '/(\w+\s+\d+\s\d+:\d+:\d+)\s(\w+)\s(\w+):\s(.+)/',
    '/(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+\+\d{2}:\d{2})\s(\w+)\s(\w+)\[(\d+)\]:\s(.+)/'
  ];

  /**
   * Parse the given syslog message.
   *
   * @param String $message The syslog message to be parsed.
   * @return Message The parsed syslog message.
   * @throws InvalidArgumentException If the syslog message doesn't match any pattern.
   */
  public static function parse(String $message): Message {

    foreach (self::SYSLOG_MESSAGE_PATTERNS as $pattern) {
      if (preg_match($pattern, $message, $matches)) {

        $rawMessage = trim(array_shift($matches));

        $priority = null;
        if (ctype_digit($matches[0])) {
          try {
            $priority = (int)array_shift($matches);
            $priority = Priority::getByPriority($priority);
          } catch (InvalidArgumentException) {}
        }

        $isoDate  = array_shift($matches);
        $hostname = array_shift($matches);
        $process  = array_shift($matches);

        $pid = null;
        $timestamp = null;
        $tmp = array_shift($matches);
        if (ctype_digit($tmp)) {
          $pid = (int)$tmp;
        } else {
          $timestamp = (float)$tmp;
        }

        $body = implode("", $matches);

        return new Message($rawMessage, $isoDate, $hostname, $process, $body, $priority, $pid, $timestamp);

      }
    }

    throw new InvalidArgumentException("Invalid message format.");

  }

};