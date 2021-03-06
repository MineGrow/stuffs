<?php

return [
  // 主库配置
  'database'  => [
    'dbtype'    => env('database')['dbtype'],
    'dbprefix'  => env('database')['dbprefix'],
    'dbname'    => env('database')['dbname'],
    'dbhost'    => env('database')['dbhost'],
    'username'  => env('database')['username'],
    'password'  => env('database')['password'],
    'slave'     => explode(',', env('database')['slave'])
  ],
  // 从库 0 配置
  'database-slave-0' => [
    'dbname'    => env('database-slave-0')['dbname'],
    'dbhost'    => env('database-slave-0')['dbhost'],
    'username'  => env('database-slave-0')['username'],
    'password'  => env('database-slave-0')['password'],
  ],
  // 从库 1 配置
  'database-slave-1' => [
    'dbname'    => env('database-slave-1')['dbname'],
    'dbhost'    => env('database-slave-1')['dbhost'],
    'username'  => env('database-slave-1')['username'],
    'password'  => env('database-slave-1')['password'],
  ]
];
