<?php

namespace LogServer\Protocol;
use LogServer\Protocol\Exception\UndefinedPropertyException;

trait PropertyAccessTrait {

  public final function __get(String $name) {
    throw new UndefinedPropertyException(sprintf("Property \"%s\" does not exist.", $name));
  }

  public final function __set(String $name, Mixed $value) {
    throw new UndefinedPropertyException(sprintf("Property \"%s\" does not exist.", $name));
  }

};