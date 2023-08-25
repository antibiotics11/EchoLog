<?php

namespace LogServer;

class Autoloader {

  public static function register(): bool {
    return spl_autoload_register(function(String $class) {

      $namespacePrefix = "LogServer\\";
      $classRelativePath = str_replace("\\", "/", substr($class, strlen($namespacePrefix)));
      $classFilePath = sprintf("%s/%s.php", __DIR__, $classRelativePath);

      if (is_readable($classFilePath)) {
        require_once $classFilePath;
      }

    });
  }

};

Autoloader::register();
