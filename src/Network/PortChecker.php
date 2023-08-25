<?php

namespace LogServer\Network;
use JetBrains\PhpStorm\{ExpectedValues, Pure};

class PortChecker {

  /**
   * Check If the provided port number is valid (1-65535).
   *
   * @param int $portNumber The Port number to validate.
   * @return bool True If the port number is valid, False otherwise.
   */
  #[Pure]
  public static function isValidPortNumber(int $portNumber): bool {
    return $portNumber > 0 && $portNumber <= 65535;
  }

  /**
   * Check If a specific port is in use on the given IP address.
   *
   * @param int $portNumber The Port number to check.
   * @param InetAddress $inetAddress The InetAddress instance.
   * @param int $protocol Protocol to use (SOL_TCP by default).
   * @return bool True if the port is in use, False otherwise.
   */
  public static function isPortInUse(
    int $portNumber, InetAddress $inetAddress,
    #[ExpectedValues(values: [SOL_TCP, SOL_UDP])]
    int $protocol = SOL_TCP
  ): bool {
    return !self::isPortAvailable($portNumber, $inetAddress, $protocol);
  }

  /**
   * Check if a specific port is available on the given IP address.
   *
   * @param int $portNumber The Port number to check.
   * @param InetAddress $inetAddress The InetAddress instance.
   * @param int $protocol Protocol to use (SOL_TCP by default).
   * @return bool True if the port is available, False otherwise.
   */
  public static function isPortAvailable(
    int $portNumber, InetAddress $inetAddress,
    #[ExpectedValues(values: [SOL_TCP, SOL_UDP])]
    int $protocol = SOL_TCP
  ): bool {

    $address = $inetAddress->getIpAddress();
    $addressFamily = $inetAddress->getIpAddressFamily();

    $testSocketType = match ($protocol) {
      SOL_TCP => SOCK_STREAM,
      SOL_UDP => SOCK_DGRAM,
      default => SOCK_RAW
    };

    $testSocket = @socket_create($addressFamily, $testSocketType, $protocol);
    if ($testSocket === false) {
      return false;
    }

    $testBindingResult = @socket_bind($testSocket, $address, $portNumber);
    socket_close($testSocket);

    return $testBindingResult;

  }

};
