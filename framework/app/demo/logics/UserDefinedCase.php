<?php

namespace App\Demo\Logics;

use Framework\App;

class UserDefinedCase
{
  // 注册用户自定义执行类
  private $map = [];

  public function __construct(App $app)
  {
    foreach ($this->map as $v) {
      new $v($app);
    }
  }
}