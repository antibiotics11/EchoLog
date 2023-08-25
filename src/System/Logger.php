<?php

namespace LogServer\System;
use InvalidArgumentException;
use LogServer\System\Exception\IOException;
use JetBrains\PhpStorm\Pure;

class Logger {

  protected String $logDirectory;
  protected Array  $logBuffer;
  protected int    $logBufferMaxSize;
  protected int    $logBufferLastFlushed;

  protected function getLogFilePath(): String {
    $filename = sprintf("%s.log", date("Y.m.d", time()));
    return sprintf("%s%s%s", $this->logDirectory, DIRECTORY_SEPARATOR, $filename);
  }

  protected function setLogDirectory(String $logDirectory): void {

    if (!is_dir($logDirectory) || !is_writable($logDirectory)) {
      if (!mkdir($logDirectory, 0755, true)) {
        throw new InvalidArgumentException("The provided log directory is not writable.");
      }
    }

    $logDirectoryAbsolutePath = realpath($logDirectory);
    if ($logDirectoryAbsolutePath === false) {
      throw new InvalidArgumentException("The actual log directory path does not exist.");
    }

    $this->logDirectory = $logDirectoryAbsolutePath;

  }

  /**
   * Constructs a Logger instance.
   *
   * @param String $logDirectory The directory to store log files.
   * @param int $logBufferMaxSize The maximum size of the log buffer.
   */
  public function __construct(String $logDirectory, int $logBufferMaxSize = 100) {
    $this->setLogDirectory($logDirectory);
    $this->flushLogBuffer();
    $this->logBufferMaxSize = $logBufferMaxSize;
  }

  /**
   * Clear the log buffer.
   */
  public function flushLogBuffer(): void {
    $this->logBuffer = [];
    $this->logBufferLastFlushed = time();
  }

  #[Pure]
  public function getLogDirectory(): String {
    return $this->logDirectory;
  }

  /**
   * Get the current size of the log buffer.
   */
  #[Pure]
  public function getLogBufferCurrentSize(): int {
    return count($this->logBuffer);
  }

  /**
   * Get the maximum size of the log buffer
   */
  #[Pure]
  public function getLogBufferMaxSize(): int {
    return $this->logBufferMaxSize;
  }

  /**
   * Write a log expression to the buffer.
   *
   * @param String $expression The log expression to write.
   * @param bool $flushLogBuffer Whether to flush the buffer after writing.
   */
  public function write(String $expression, bool $flushLogBuffer = true): void {

    $this->logBuffer[] = $expression;
    $logBufferCurrentSize = $this->getLogBufferCurrentSize();
    if (date("d", $this->logBufferLastFlushed) != date("d", time())
      || $logBufferCurrentSize >= $this->logBufferMaxSize) {
      $this->writeLogBufferToFile($flushLogBuffer);
    }

  }

  /**
   * Write the contents of the log buffer to a log file.
   *
   * @param bool $flushLogBuffer Whether to flush the buffer after writing.
   * @return int The number of bytes written to the log file.
   * @throws IOException If writing to the log file fails.
   */
  public function writeLogBufferToFile(bool $flushLogBuffer = true): int {

    $logFilePath = $this->getLogFilePath();
    $logFileContents = sprintf("%s\r\n", implode("\r\n", $this->logBuffer));

    $bytesWritten = file_put_contents($logFilePath, $logFileContents, FILE_APPEND | LOCK_EX);
    if ($bytesWritten === false) {
      throw new IOException("Failed to write log data to the file.", $logFilePath);
    }
    if ($flushLogBuffer) {
      $this->flushLogBuffer();
    }

    return $bytesWritten;

  }

};