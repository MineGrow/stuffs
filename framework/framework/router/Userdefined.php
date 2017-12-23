<?php

namespace Framework\Router;

use Framework\Router\FrameRouter;
use Framework\Router\RouterInterface;
use Framework\Exceptions\CoreHttpException;

class Userdefined implements RouterInterface
{
  private $getMap = [];

  private $postMap = [];

  private $putMap = [];

  private $deleteMap = [];

  /**
   * 自定义 get 请求路由
   *
   * @param string $uri       请求 uri
   * @param string $function  匿名函数或者控制器方法标示
   * @return void
   */
  public function get($uri = '', $function = '')
  {
    $this->getMap[$uri] = $function;
  }

  /**
   * 自定义 post 请求路由
   *
   * @param string $uri       请求 uri
   * @param string $function  匿名函数或者控制器方法标示
   * @return void
   */
  public function post($uri = '', $function = '')
  {
    $this->postMap[$uri] = $function;
  }

  /**
   * 自定义 put 请求路由
   *
   * @param string $uri       请求 uri
   * @param string $function  匿名函数或者控制器方法标示
   * @return void
   */
  public function put($uri = '', $function = '')
  {
    $this->putMap[$uri] = $function;
  }

  /**
   * 自定义 delete 请求路由
   *
   * @param string $uri       请求 uri
   * @param string $function  匿名函数或者控制器方法标示
   * @return void
   */
  public function delete($uri = '', $function = '')
  {
    $this->deleteMap[$uri] = $function;
  }

  public function route(FrameRouter $entrance)
  {
    if ($entrance->routeStrategy === 'job') {
      return ;
    }

    $module = $entrance->config->config['module'];
    foreach ($module as $v) {
      // 加载自定义路由配置文件
      $routeFile = "{$entrance->app->rootPath}/config/{$v}/route.php";
      if (file_exists) {
        require($routeFile);
      }
    }

    $uri      = "{$entrance->moduleName}/{$entrance->controllerName}/{$entrance->actionName}";
    $app      = $entrance->app;
    $request  = $app::$container->getSingle('request');
    $method   = $request->method . 'Map';

    if (! isset($this->method)) {
      throw new CoreHttpException(404, 'Http Method:' . $request->method);
    }

    if (! array_key_exists($uri, $this->method)) {
      return false;
    }

    // 执行自定义路由匿名函数
    $map = $this->$method;
    $entrance->app->responseData = $map[$uri]($app);
    if ($entrance->app->isCli === 'yes') {
      $entrance->app->notOutput = false;
    }
    return true;
  }
}