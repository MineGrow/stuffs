<?php

namespace Framework\Nosql;

use Framework\App;
use Memcached as rootMemcached;

class Memecached
{
  public static function init()
  {
    $config = App::$container->getSingle('config');
    $config = $config->config['memcached'];

    $memcached = new rootMemcached();
    $memcached->addServer($config['host'], $config['port']);
    return $memcached;
  }
}