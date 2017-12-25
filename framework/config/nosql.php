<?php
/**
 * nosql 相关配置
 */

return [
  // 需要提供支持的 nosql 种类
  // 参数示例：redis/memcached/mongoDB
  'nosql' => env('nosql')['support'],
  
  'redis' => [
    'host'    => env('redis')['host'],
    'port'    => env('redis')['port'],
    'password'=> env('redis')['password']
  ],

  'memcached' => [
    'host'    => env('memcached')['host'],
    'port'    => env('memcached')['port'],
    'password'=> env('memcached')['password'],
  ],

  'mongoDB'   => [
    'host'    => env('mongoDB')['host'],
    'port'    => env('mongoDB')['port'],
    'database'=> env('mongoDB')['database'],
    'username'=> env('mongoDB')['username'],
    'password'=> env('mongoDB')['password'],
  ]
];