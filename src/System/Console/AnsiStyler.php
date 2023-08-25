<?php

namespace LogServer\System\Console;

class AnsiStyler {

  protected ?int $backgroundColor;
  protected ?int $foregroundColor;
  protected bool $underline;
  protected bool $bold;
  protected bool $italic;
  protected bool $trim;

  // Resets all formatting properties.
  protected function reset(): void {
    $this->backgroundColor = null;
    $this->foregroundColor = null;
    $this->underline       = false;
    $this->bold            = false;
    $this->italic          = false;
    $this->trim            = false;
  }

  public function __construct() {
    $this->reset();
  }

  // Set the background color using an ANSI color code.
  public function withBackgroundColor(AnsiColorCode|int $color): self {
    if ($color instanceof AnsiColorCode) {
      $color = $color->value;
    }
    $this->backgroundColor = $color;
    return $this;
  }

  // Set the foreground color using an ANSI color code.
  public function withForegroundColor(AnsiColorCode|int $color): self {
    if ($color instanceof AnsiColorCode) {
      $color = $color->value;
    }
    $this->foregroundColor = $color;
    return $this;
  }

  // Set both foreground and background colors.
  public function withColor(AnsiColorCode|int $foregroundColor = 39, AnsiColorCode|int $backgroundColor = 49): self {
    return $this->withForegroundColor($foregroundColor)->withBackgroundColor($backgroundColor);
  }

  // Enable or disable underline style.
  public function withUnderline(bool $underline = true): self {
    $this->underline = $underline;
    return $this;
  }

  // Enable or disable bold style.
  public function withBold(bool $bold = true): self {
    $this->bold = $bold;
    return $this;
  }

  // Enable or disable italic style.
  public function withItalic(bool $italic = true): self {
    $this->italic = $italic;
    return $this;
  }

  // Enable or disable trimming of the printed output.
  public function withTrim(bool $trim = true): self {
    $this->trim = $trim;
    return $this;
  }

  // Print the given expression with the applied formatting.
  public function print(String $expression = ""): void {
    printf("%s", $this->generate($expression));
  }

  // Print the given expression with the applied formatting and a newline.
  public function println(String $expression = ""): void {
    printf("%s%s", $this->generate($expression), PHP_EOL);
  }

  // Generate the formatted string for the given expression.
  public function generate(String $expression = ""): String {

    $escapeSequence = "";
    if ($this->bold) {
      $escapeSequence = sprintf("%s\033[1m", $escapeSequence);
    }
    if ($this->italic) {
      $escapeSequence = sprintf("%s\033[3m", $escapeSequence);
    }
    if ($this->underline) {
      $escapeSequence = sprintf("%s\033[4m", $escapeSequence);
    }
    if ($this->foregroundColor !== null) {
      $escapeSequence = sprintf("%s\033[%dm", $escapeSequence, $this->foregroundColor);
    }
    if ($this->backgroundColor !== null) {
      $escapeSequence = sprintf("%s\033[%dm", $escapeSequence, $this->backgroundColor);
    }
    if ($this->trim) {
      $expression = trim($expression);
    }

    $expression = sprintf("%s%s\033[0m", $escapeSequence, $expression);
    $this->reset();

    return $expression;

  }

};
