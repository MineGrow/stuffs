<?php

namespace Framework\Handles;

use Framework\App;
use Framework\Handles\Handle;
use Framework\Exceptions\CoreHttpException;

class UserDefinedHandle implements Handle
{
  use \Framework\Traits\GlobalConstant;

  public function __construct()
  {
    $this->registerGlobalConst();
  }

  /**
   * 注册用户自定义操作
   *
   * @param App $app
   * @return void
   */
  public function register(App $app)
  {
    $config = $app::$container->getSingle('config');
    foreach ($config->config['module'] as $v) {
      $v = ucwords($v);
      $className = "\App\\{$v}\\Logics\UserDefinedCase";
      new $className($app);
    }
  }
}