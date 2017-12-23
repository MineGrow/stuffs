<?php

namespace Framework\Nosql;

use Framework\App;
use Redis as rootRedis;

class Redis 
{
  public static function init()
  {
    $config = App::$container->getSingle('config');
    $config = $config->config['redis'];
    $redis  = new rootRedis();
    $redis->connect($config['host'], $config['port']);
    return $redis;
  }
}