<?php

namespace LogServer\Network;
use Socket;
use LogServer\Network\Exception\ServerException;
use JetBrains\PhpStorm\Pure;

declare(ticks = 1);

final class UDPServer {

  private ?Socket       $serverSocket;
  private ?InetAddress  $serverAddress;
  private int           $serverPort;
  private int           $socketBufferSize;

  public function __construct() {
    $this->serverSocket     = null;
    $this->serverAddress    = null;
    $this->serverPort       = 0;
    $this->socketBufferSize = 512;
  }

  /** Create and configure the server socket.
   *
   * @param InetAddress|String $serverAddress The server's IP address.
   * @param int $serverPort The port number to bind the server socket to.
   * @throws ServerException If an error occurs during socket creation or binding.
   */
  public function create(InetAddress|String $serverAddress, int $serverPort): void {

    if (is_string($serverAddress)) {
      $serverAddress = InetAddress::getByInput($serverAddress);
      if ($serverAddress === null) {
        throw new ServerException("Invalid server address.");
      }
    }

    if (!PortChecker::isValidPortNumber($serverPort)) {
      throw new ServerException("Invalid port number.");
    }
    if (!PortChecker::isPortAvailable($serverPort, $serverAddress, SOL_UDP)) {
      throw new ServerException("Port not available.");
    }

    $this->serverAddress = $serverAddress;
    $this->serverPort = $serverPort;

    $serverSocket = socket_create(
      $this->serverAddress->getIpAddressFamily(),
      SOCK_DGRAM,
      SOL_UDP
    );

    if ($serverSocket === false) {
      $this->close();
      throw new ServerException(socket_strerror(socket_last_error()));
    }
    $this->serverSocket = $serverSocket;

    if (false === socket_bind(
      $this->serverSocket,
      $this->serverAddress->getIpAddress(),
      $this->serverPort
    )) {
      $this->close();
      throw new ServerException(socket_strerror(socket_last_error()));
    }

  }

  /**
   * Set the blocking mode of the server socket.
   *
   * @param bool $enableBlockingMode True to enable blocking mode, false for non-blocking mode.
   * @return bool True if the mode was set successfully, False otherwise.
   */
  public function setSocketBlocking(bool $enableBlockingMode): bool {

    if ($enableBlockingMode) {
      return socket_set_block($this->serverSocket);
    }
    return socket_set_nonblock($this->serverSocket);

  }

  /**
   * Set the send timeout 
   *
   * @param int $sec Seconds for the timeout.
   * @param int $usec Microseconds for the timeout.
   * @return bool True if the timeout was set successfully, False otherwise.
   */
  public function setSocketSendTimeout(int $sec = 1, int $usec = 0): bool {
    $timeout = [ "sec" => $sec, "usec" => $usec ];
    return socket_set_option($this->serverSocket, SOL_SOCKET, SO_SNDTIMEO, $timeout);
  }

  public function setSocketReceiveTimeout(int $sec = 1, int $usec = 0): bool {
    $timeout = [ "sec" => $sec, "usec" => $usec ];
    return socket_set_option($this->serverSocket, SOL_SOCKET, SO_RCVTIMEO, $timeout);
  }

  public function setSocketBufferSize(int $bufferSize = 512): bool {

    if ($bufferSize > 0 && $bufferSize <= PHP_INT_MAX) {
      $this->socketBufferSize = $bufferSize;
      return true;
    }
    return false;

  }

  #[Pure]
  public function getSocketBufferSize(): int {
    return $this->socketBufferSize;
  }

  #[Pure]
  public function getServerAddress(): ?InetAddress {
    return $this->serverAddress;
  }

  #[Pure]
  public function getServerPort(): int {
    return $this->serverPort;
  }

  /**
   * Receive incoming data and handle it using the provided handler function.
   *
   * @param Callable $handler The callback function to process received data.
   * @param Mixed[] $handlerArgs Additional arguments to pass to the handler function.
   * @throws ServerException If
   */
  public function receive(Callable $handler, Array $handlerArgs = []): void {

    while (true) {
      $bytesReceived = @socket_recvfrom(
        $this->serverSocket,
        $socketBuffer, $this->socketBufferSize,
        0,
        $remoteIp, $remotePort
      );
      if ($bytesReceived === false) {
        continue;
      }

      $response = call_user_func($handler, $socketBuffer, $remoteIp, $remotePort, $handlerArgs);

      if (is_null($response)) {
        continue;
      } else if (is_string($response)) {
        $this->send($response, $remoteIp, $remotePort);
      } else if (is_array($response)) {
        foreach ($response as $r) {
          if (!is_string($r)) {
            throw new ServerException(sprintf("Invalid return type '%s'.", gettype($r)));
          }
          $this->send($r, $remoteIp, $remotePort);
        }
      } else {
        throw new ServerException(sprintf("Invalid return type '%s'.", gettype($response)));
      }
    }

  }

  /**
   * Send a message to a specified target IP and port.
   *
   * @param String $message The message to send.
   * @param InetAddress|String $targetIp The target IP address.
   * @param int $targetPort The target port number.
   * @return int|bool The number of bytes sent on success, or False on failure.
   */
  public function send(String $message, InetAddress|String $targetIp, int $targetPort): int|false {

    if ($targetIp instanceof InetAddress) {
      $targetIp = $targetIp->getIpAddress();
    }

    return @socket_sendto(
      $this->serverSocket,
      $message, strlen($message),
      0,
      $targetIp, $targetPort
    );

  }

  /**
   * Close the server socket.
   */
  public function close(): void {
    if ($this->serverSocket instanceof Socket) {
      socket_close($this->serverSocket);
      $this->serverSocket = null;
    }
  }

  public function __destruct() {
    $this->close();
  }

};
