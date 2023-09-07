<?php

namespace LogServer;
use LogServer\Network\{InetAddress, UDPServer};
use LogServer\Network\Exception\ServerException;
use LogServer\Protocol\Parser;
use LogServer\System\{Time, Logger};
use LogServer\System\Posix\{PosixSignal, PosixSignalHandler};
use LogServer\System\Console\{AnsiColorCode, AnsiStyler};
use InvalidArgumentException;
use JetBrains\PhpStorm\{Immutable, ArrayShape, NoReturn};

final class LogServer {

  private UDPServer  $udpServer;

  private Logger     $serverLogger;

  private AnsiStyler $consoleLogger;

  // Configuration array for remote log hosts.
  #[Immutable(Immutable::PRIVATE_WRITE_SCOPE)]
  #[ArrayShape([
    "address" => "LogServer\\Network\\InetAddress",
    "logger"  => "LogServer\\System\\Logger",
    "parse"   => "bool"
  ])]
  private Array      $remoteLogConfig;

  // Initializes the server with provided settings.
  public function __construct(
      String $serverAddress, int $serverPort, String $serverLogDir, String $serverTimezone,
      #[ArrayShape(["address" => "String", "path" => "String", "parse" => "bool"])]
      Array $remoteLogConfig
  ) {

    $this->consoleLogger = new AnsiStyler();

    $serverAddress = InetAddress::getByInput($serverAddress);
    if ($serverAddress === null) {
      $this->terminate("Invalid server address.");
    }

    $this->remoteLogConfig = [];
    foreach ($remoteLogConfig as $config) {
      if (!isset($config["address"]) || !isset($config["path"])) {
        $this->terminate("\"address\" and \"path\" not specified.");
      }

      $remoteAddress = InetAddress::getByInput($config["address"]);
      if ($remoteAddress === null) {
        $this->terminate(sprintf("Invalid address \"%s\".", $config["address"]));
      }

      if ($remoteAddress->ipAddressFamily != $serverAddress->ipAddressFamily) {
        $this->consoleLogger
             ->withForegroundColor(AnsiColorCode::FOREGROUND_COLOR_YELLOW)
             ->println(sprintf("Address family %s(%s) does not match server.",
               ($remoteAddress->ipAddressFamily == AF_INET ? "AF_INET" : "AF_INET6"),
               $remoteAddress
             ));
      }

      $remoteLogger = null;
      try {
        $remoteLogger = new Logger($config["path"], 1000);
      } catch (InvalidArgumentException $e) {
        $this->terminate($e->getMessage());
      }

      $this->remoteLogConfig[$remoteAddress->getIpAddress()] = [
        "address" => $remoteAddress,
        "logger"  => $remoteLogger,
        "parse"    => $config["parse"] ?? false
      ];
    }

    try {
      $this->serverLogger = new Logger(trim($serverLogDir), 10000);
    } catch (InvalidArgumentException $e) {
      $this->terminate($e->getMessage());
    }

    try {
      $this->udpServer = new UDPServer();
      $this->udpServer->create($serverAddress, $serverPort);
      $this->udpServer->setSocketBufferSize(1024);
      $this->udpServer->setSocketReceiveTimeout(2, 0);
      $this->udpServer->setSocketBlocking(true);
    } catch (ServerException $e) {
      $this->terminate($e->getMessage());
    }

    try {
      Time::setTimezone($serverTimezone);
    } catch (InvalidArgumentException $e) {
      $this->terminate($e->getMessage());
    }

    $signals = [PosixSignal::SIGHUP, PosixSignal::SIGINT, PosixSignal::SIGQUIT, PosixSignal::SIGTERM];
    for ($s = 0; $s < count($signals); $s++) {
      PosixSignalHandler::register($signals[$s],  [$this, "close"]);
    }

  }

  // Start the server.
  public function run(): void {

    $log = $this->consoleLogger
                ->withForegroundColor(AnsiColorCode::FOREGROUND_COLOR_CYAN)
                ->generate(sprintf("[%s] Starting server at %s:%d...",
                    Time::dateRFC2822(),
                    $this->udpServer->getServerAddress()->getIpAddress(),
                    $this->udpServer->getServerPort()
                ));
    $this->consoleLogger->println($log);
    $this->serverLogger->write($log);

    $this->udpServer->receive([$this, "handle"]);

  }

  // Handle an incoming message.
  public function handle(String $message, String $remoteIp, int $remotePort): void {

    $isConfiguredHost = array_key_exists($remoteIp, $this->remoteLogConfig);
    $logColor = $isConfiguredHost ? AnsiColorCode::FOREGROUND_COLOR_CYAN : AnsiColorCode::FOREGROUND_COLOR_YELLOW;
    $log = $this->consoleLogger
                ->withForegroundColor($logColor)
                ->generate(sprintf("[%s] Received %d bytes from %s:%d.",
                  Time::dateRFC2822(),
                  strlen($message),
                  $isConfiguredHost ? $remoteIp : sprintf("unknown host %s", $remoteIp),
                  $remotePort
                ));
    $this->consoleLogger->println($log);
    $this->serverLogger->write($log);

    if (!$isConfiguredHost) {
      return;
    }

    $message = mb_convert_encoding(trim($message), "UTF-8");
    try {
      $message = $this->remoteLogConfig[$remoteIp]["parse"] ? Parser::parse($message) : $message;
    } catch (InvalidArgumentException) {
      $this->consoleLogger
           ->withForegroundColor(AnsiColorCode::FOREGROUND_COLOR_YELLOW)
           ->println(sprintf("[%s] Failed to parse received message.", Time::dateRFC2822()));
    }
    $this->remoteLogConfig[$remoteIp]["logger"]->write($message);

  }

  // Terminate the server with an error message.
  #[NoReturn(NoReturn::ANY_ARGUMENT)]
  public function terminate(String $errorMessage = ""): void {

    $errorMessage = !strlen($errorMessage) ? "Unknown error occurred." : $errorMessage;
    $this->consoleLogger
         ->withForegroundColor(AnsiColorCode::FOREGROUND_COLOR_RED)
         ->println(sprintf("Error: %s", $errorMessage));
    exit(1);

  }

  // Close the server gracefully.
  #[NoReturn(NoReturn::ANY_ARGUMENT)]
  public function close(): void {

    $this->serverLogger->writeLogBufferToFile();
    foreach ($this->remoteLogConfig as $config) {
      if ($config["logger"]->getLogBufferCurrentSize() > 0) {
        $config["logger"]->writeLogBufferToFile();
      }
    }

    $this->consoleLogger
         ->withForegroundColor(AnsiColorCode::FOREGROUND_COLOR_CYAN)
         ->println(sprintf("[%s] Shutting down the server...", Time::dateRFC2822()));
    $this->udpServer->close();
    exit(0);

  }

};
