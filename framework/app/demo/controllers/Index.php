<?php

namespace App\Demo\Controllers;

use Framework\App;
use Framework\Logger;

class Index
{
  public function __construct()
  {
    # code...
  }
  
  public function hello()
  {
    return 'Hello World - ' . NOW_TIME;
  }

  /**
   * 演示
   *
   * @return void
   * 
   * domain/Demo/Index/test?username=tigerb&password=123456789987&code=123456
   */
  public function test()
  {
    $request = App::$container->getSingle('request');
    $request->check('username', 'request');
    $request->check('password', 'length', 12);
    $request->check('code', 'number');
    return [
      'username'  => $request->get('username','default value')
    ];
  }

  /**
   * 框架内部调用演示
   * 
   * 极大简化了内部模块依赖的问题
   * 
   * 可构建微单体建构
   * 
   * @example domain/Demo/Index/micro
   * @return json
   */
  public function micro()
  {
    return App::$app->get('demo/index/hello', [
      'user'  => 'test'
    ]);
  }

  /**
   * 容器内获取实例
   *
   * @return void
   */
  public function getInstanceFromContainerDemo()
  {
    App::$container->getSingle('request');
    App::$container->getSingle('config');
    $logger = App::$container->getSingle('logger');
    $logger->write(['This is php logger']);
    return [];
  }

  /**
   * 容器内获取 nosql 实例演示
   *
   * @return void
   */
  public function nosqlDemo()
  {
    App::$container->getSingle('redis');
    App::$container->getSingle('memcached');
    App::$container->getSingle('mongoDB');

    return [];
  }
}