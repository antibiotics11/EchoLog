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

  #[Immutable(Immutable::PRIVATE_WRITE_SCOPE)]
  #[ArrayShape([
    "address" => "LogServer\\Network\\InetAddress",
    "logger"  => "LogServer\\System\\Logger",
    "parse"   => "bool"
  ])]
  private Array      $remoteLogConfig;

  public function __construct(
      String $serverAddress, int $serverPort, String $serverLogDir, String $serverTimezone,
      #[ArrayShape(["address" => "String", "path" => "String", "parse" => "bool"])]
      Array $remoteLogConfig
  ) {

    $this->consoleLogger = new AnsiStyler();

    $this->remoteLogConfig = [];
    foreach ($remoteLogConfig as $config) {
      if (!isset($config["address"]) || !isset($config["path"])) {
        $this->terminate("\"address\" and \"path\" must be specified.");
      }

      $remoteAddress = InetAddress::getByInput($config["address"]);
      if ($remoteAddress === null) {
        $this->terminate("Invalid remote address.");
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
        "parse"   => $config["parse"] ?? true
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

    PosixSignalHandler::register(PosixSignal::SIGINT,  [$this, "close"]);
    PosixSignalHandler::register(PosixSignal::SIGTERM, [$this, "close"]);

  }

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

  public function handle(String $rawMessage, String $remoteIp, int $remotePort): void {

    $isConfiguredHost = array_key_exists($remoteIp, $this->remoteLogConfig);
    $logColor = $isConfiguredHost ? AnsiColorCode::FOREGROUND_COLOR_CYAN : AnsiColorCode::FOREGROUND_COLOR_YELLOW;
    $log = $this->consoleLogger
                ->withForegroundColor($logColor)
                ->generate(sprintf("[%s] Received %d bytes from %s:%d.",
                  Time::dateRFC2822(),
                  strlen($rawMessage),
                  $isConfiguredHost ? $remoteIp : sprintf("unknown host %s", $remoteIp),
                  $remotePort
                ));
    $this->consoleLogger->println($log);
    $this->serverLogger->write($log);

    try {
      if ($isConfiguredHost) {
        $message = $this->remoteLogConfig[$remoteIp]["parse"] ? Parser::parse($rawMessage) : $rawMessage;
        $this->remoteLogConfig[$remoteIp]["logger"]->write($message);
      }
    } catch (InvalidArgumentException) {
      $this->consoleLogger
           ->withForegroundColor(AnsiColorCode::FOREGROUND_COLOR_YELLOW)
           ->println(sprintf("[%s] Failed to parse received message.", Time::dateRFC2822()));
    }

  }

  // Terminate the server with an error message.
  #[NoReturn(NoReturn::ANY_ARGUMENT)]
  public function terminate(String $errorMessage = ""): void {

    $errorMessage = !strlen($errorMessage) ? "Unknown error occurred." : $errorMessage;
    $this->consoleLogger
         ->withForegroundColor(AnsiColorCode::FOREGROUND_COLOR_RED)
         ->generate($errorMessage);
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
