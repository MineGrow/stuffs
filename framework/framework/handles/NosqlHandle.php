<?php

namespace Framework\Handles;

use Framework\App;
use Framework\Handles\Handle;
use Framework\Exceptions\CoreHttpException;

class NosqlHandle implements Handle
{
  public function __construct()
  {
    # code...
  }

  /**
   * 注册 nosql 处理机制
   *
   * @param App $app
   * @return void
   */
  public function register(App $app)
  {
    $config = $app::$container->getSingle('config');
    if (empty($config->config['nosql'])) {
      return;
    }
    
    $config = explode(',', $config->config['nosql']);
    foreach ($config as $v) {
      $className = 'Framework\Nosql\\' . ucfirst($v);
      App::$container->setSingle($v, function() use ($className) {
        // 懒加载 lazy load
        return $className::init();
      });
    }
  }
}