<?php
/**
 * 自加载模块
 */
namespace Framework;

use Framework\App;
use Framework\Exceptions\CoreHttpException;

/**
 * 注册加载 handle
 */
class Load
{
  /**
   * 类名映射
   *
   * @var array
   */
  public static $map = [];

  /**
   * 类命名空间映射
   *
   * @var array
   */
  public static $namespaceMap = [];

  /**
   * 应用启动注册
   *
   * @param App $app  框架实例
   * @return void
   */
  public static function register(App $app)
  {
    self::$namespaceMap = [
      'Framework' => $app->rootPath
    ];

    // 注册框架加载函数 不使用 composer 加载机制加载框架 自己实现
    spl_autoload_register(['Framework\Load', 'autoload']);
    
    // 引入 composer 自加载文件
    require($app->rootPath . '/vendor/autoload.php');
  }

  /**
   * 自加载函数
   *
   * @param string $class   类名
   * @return void
   */
  private static function autoload($class)
  {
    $classOrigin  = $class;
    $classInfo    = explode('\\', $class);
    $className    = array_pop($classInfo);
    foreach ($classInfo as &$v) {
      $v = strtolower($v);
    }
    
    // TODO unset($v) 为什么?
    unset($v);
    array_push($classInfo, $className);

    $class      = implode('\\', $classInfo);
    $path       = self::$namespaceMap['Framework'];
    $classPath  = $path . '/' . str_replace('\\', '/', $class) . '.php';
    if (!file_exists($classPath)) {
      return ;
      throw new CoreHttpException(404, "$classPath Not Found");
    }
    self::$map[$classOrigin] = $classPath;
    require $classPath;
  }
}