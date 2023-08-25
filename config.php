<?php

const UDP_SERVER_ADDRESS  = "192.168.0.3";
const UDP_SERVER_PORT     = 514;
const UDP_SERVER_LOG_DIR  = "/var/log/messages/server";

const REMOTE_LOG_CONFIG   = [
  [  // My Home modem
    "address" => "192.168.0.1",
    "path"    => "/var/log/messages/modem",
    "parse"   => false
  ],
  [  // My brand-new ASUS Router
    "address" => "192.168.0.2",
    "path"    => "/var/log/messages/router",
    "parse"   => true
  ]
];
