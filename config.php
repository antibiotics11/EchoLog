<?php

/**
 * Address of the log server. (In IPv4 or IPv6 format)
 * Domain name will be automatically converted to IP address.
 */
const UDP_SERVER_ADDRESS = "192.168.0.3";

/**
 * Port number of the log server. (Default value is 514)
 */
const UDP_SERVER_PORT = 514;

/**
 * Directory path where server logs are stored.
 */
const UDP_SERVER_LOG_DIR = "/var/log/messages/server";

const REMOTE_LOG_CONFIG = [

  // Configuration for My Home Modem.
  [
    /**
     * Address of the remote host sending syslog messages. (In IPv4 or IPv6 format)
     */
    "address" => "192.168.0.1",

    /**
     * Directory path where the syslog messages are stored.
     */
    "path" => "/var/log/messages/localhost",

    /**
     * Whether the log server should parse received syslog messages.
     * Enabling this might increase CPU usage.
     */
    "parse" => true

  ],

  // Configuration for My brand-new ASUS Router.
  [
    "address" => "192.168.0.2",
    "path" => "/var/log/messages/router",
  ]

];
