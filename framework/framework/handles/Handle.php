<?php

namespace Framework\Handles;

use Framework\App;

Interface Handle
{
  /**
   * 注册处理机制
   */
  public function register(App $app);
  
}