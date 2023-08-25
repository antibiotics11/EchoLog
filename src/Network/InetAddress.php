<?php

namespace LogServer\Network;
use JetBrains\PhpStorm\{Deprecated, Immutable, ExpectedValues, Pure};

#[Immutable(Immutable::CONSTRUCTOR_WRITE_SCOPE)]
final class InetAddress {

  public readonly String $hostname;
  public readonly String $ipAddress;
  public readonly int    $ipAddressFamily;

  private function __construct(
    String $ipAddress,
    #[ExpectedValues(values: [AF_INET, AF_INET6])]
    int $ipAddressFamily,
    String $hostname = ""
  ) {
    $this->hostname        = $hostname;
    $this->ipAddress       = $ipAddress;
    $this->ipAddressFamily = $ipAddressFamily;
  }

  #[Deprecated(reason: "hostname is now marked as readonly." )]
  #[Pure]
  public function getHostname(): String {
    return $this->hostname;
  }

  #[Deprecated(reason: "ipAddress is now marked as readonly." )]
  #[Pure]
  public function getIpAddress(): String {
    return $this->ipAddress;
  }

  #[Deprecated(reason: "ipAddressFamily is now marked as readonly." )]
  #[Pure]
  public function getIpAddressFamily(): int {
    return $this->ipAddressFamily;
  }

  /**
   * Check if a given IP address is of IPv4 type.
   *
   * @param String $ipAddress The IP address to check.
   * @return bool True if the IP address is IPv4, False otherwise.
   */
  #[Pure]
  public static function isIPv4(String $ipAddress): bool {
    return filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
  }

  /**
   * Check if a given IP address is of IPv6 type.
   *
   * @param String $ipAddress The IP address to check.
   * @return bool True if the IP address if IPv6, False otherwise.
   */
  #[Pure]
  public static function isIPv6(String $ipAddress): bool {
    return filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
  }

  /**
   * Get an InetAddress instance by IP address.
   *
   * @param String $ipAddress The IP address.
   * @return InetAddress|null The InetAddress instance if successful, null otherwise.
   */
  public static function getByIpAddress(String $ipAddress): ?self {

    $ipAddressFamily = 0;
    if (self::isIPv4($ipAddress)) {
      $ipAddressFamily = AF_INET;
    } else if (self::isIPv6($ipAddress)) {
      $ipAddressFamily = AF_INET6;
    } else {
      return null;
    }

    return new self($ipAddress, $ipAddressFamily);

  }

  /**
   * Get an InetAddress instance by hostname.
   *
   * @param String $hostname The hostname.
   * @return InetAddress|null The InetAddress instance if successful, null otherwise.
   */
  public static function getByHostname(String $hostname): ?self {
    return self::getAllByHostname($hostname)[0] ?? null;
  }

  /**
    * Get all InetAddress instances associated with a hostname.
    *
    * @param String $hostname The hostname.
    * @return InetAddress[] An array of InetAddress instances.
    */
  public static function getAllByHostname(String $hostname): Array {

    $dnsRecordA = @dns_get_record($hostname, DNS_A);
    $dnsRecordAAAA = @dns_get_record($hostname, DNS_AAAA);
    if ($dnsRecordA === false) {
      $dnsRecordA = [];
    }
    if ($dnsRecordAAAA === false) {
      $dnsRecordAAAA = [];
    }
    $dnsRecords = array_merge($dnsRecordA, $dnsRecordAAAA);

    $inetAddresses = [];
    foreach ($dnsRecords as $record) {

      $ipAddress = $record["ip"] ?? $record["ipv6"] ?? null;
      if ($ipAddress === null) {
        continue;
      }

      $ipAddressFamily = 0;
      if (self::isIPv4($ipAddress)) {
        $ipAddressFamily = AF_INET;
      } else if (self::isIPv6($ipAddress)) {
        $ipAddressFamily = AF_INET6;
      } else {
        continue;
      }

      $inetAddress = new self($ipAddress, $ipAddressFamily, $hostname);
      $inetAddresses[] = $inetAddress;
    }

    return $inetAddresses;

  }

  /**
   * Get an InetAddress instance by input (hostname or IP address).
   *
   * @param String $hostnameOrIpAddress The input value.
   * @return InetAddress|null The InetAddress instance if successful, null otherwise.
   */
  public static function getByInput(String $hostnameOrIpAddress): ?self {

    $target = strtolower(trim($hostnameOrIpAddress));
    $inetAddress = null;

    if (self::isIPv4($target) || self::isIPv6($target)) {
      $inetAddress = self::getByIpAddress($target);
    } else {
      $inetAddress = self::getByHostname($target);
    }

    return $inetAddress;

  }

};