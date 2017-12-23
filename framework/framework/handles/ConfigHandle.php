<?php

namespace Framework\Handles;

use Framework\App;
use Framework\Handles\Handle;
use Framework\Exceptions\CoreHttpException;

class ConfigHandle implements Handle
{
  /**
   * 框架实例
   *
   * @var object
   */
  private $app;

  /**
   * 配置
   *
   * @var array
   */
  private $config;

  public function __construct()
  {
    # code
  }

  /**
   * 魔法函数 __get
   *
   * @param string $name
   * @return void
   */
  public function __get($name = '')
  {
    return $this->$name;
  }

  public function __set($name = '', $value = '')
  {
    $this->$name = $value;
  }

  /**
   * 注册配置文件的处理机制
   *
   * @param App $app 框架实例
   * @return void
   */
  public function register(App $app)
  {
    require($app->rootPath . '/framework/Helper.php');

    $this->app = $app;
    $app::$container->setSingle('config', $this);
    $this->loadConfig($app);

    // 设置时区
    date_default_timezone_set($this->config['default_timezone']);
  }

  /**
   * 加载配置文件
   *
   * @param App $app 框架实例
   * @return void
   */
  public function loadConfig(App $app)
  {
    // 加载公共自定义配置
    $defaultCommon    = require($app->rootPath . '/config/common.php');
    $defaultNosql     = require($app->rootPath . '/config/nosql.php');
    $defaultDatabase  = require($app->rootPath . '/config/database.php');

    $this->config = array_merge($defaultCommon, $defaultNosql, $defaultDatabase);

    // 加载模块自定义配置
    $module = $app::$container->getSingle('config')->config['module'];
    foreach ($module as &$v) {
      $file = "{$app->rootPath}/config/{$v}/config.php";
      if (file_exists($file)) {
        $this->config = array_merge($this->config, require($file));
      }
    }
  }
}