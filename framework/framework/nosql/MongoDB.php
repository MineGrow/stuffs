<?php

namespace Framework\Nosql;

use Framework\App;
use MongoDB\Client;

class MongoDB
{
  public static function init()
  {
    $config = App::$container->getSingle('config');
    $config = $config->config['mongoDB'];
    $client = new Client(
      "{$config['host']}:{$config['port']}",
      [
        'database'  => $config['database'],
        'username'  => $config['username'],
        'password'  => $config['password']
      ]
    );

    $database = $client->selectDatabase($config['database']);
    return $database;
  }
}