<?php

namespace Framework;

use Framework\Container;
use Framework\Exceptions\CoreHttpException;

/**
 * 框架应用类
 */
class App 
{
  /**
   * 框架加载流程一系列处理类集合
   *
   * @var array
   */
  private $handlesList = [];

  /**
   * 请求对象
   *
   * @var object
   */
  private $request;

  /**
   * 框架实例根目录
   *
   * @var string
   */
  private $rootPath;

  /**
   * 响应对象
   *
   * @var object
   */
  private $responseData;

  /**
   * cli 模式
   *
   * @var string
   */
  private $isCli = 'false';

  /**
   * 框架实例
   *
   * @var object
   */
  public static $app;

  /**
   * 是否输出响应结果
   * 
   * 默认输出
   * cli 模式 访问路径为空 不输出
   *
   * @var boolean
   */
  private $notOutput = false;

  /**
   * 服务容器
   *
   * @var object
   */
  public static $container;

  public function __contruct($rootPath, Closure $loader)
  {
    // cli 模式
    $this->isCli = getenv('IS_CLI');
    // 根目录
    $this->rootPath = $rootPath;

    // 注册自加载
    $loader();
    Load::register($this);

    self::$app = $this;
    self::$container = new Container();
  }

  /**
   * 魔法函数 __get
   *
   * @param string $name 属性名称
   * @return mixed
   */
  public function __get($name = '')
  {
    return $this->$name;
  }

  /**
   * 魔法函数 __set
   *
   * @param string $name  属性名称
   * @param string $value 属性值
   */
  public function __set($name = '', $value = '')
  {
    $this->name = $value;
  }

  /**
   * 注册框架运行过程中一系列处理类
   *
   * @param Closure $handle handle 类
   * @return void
   */
  public function load(Closure $handle)
  {
    $this->handlesList[] = $handle;
  }

  /**
   * 内部调用
   *
   * @param string $method  模拟的 http 请求 method
   * @param string $uri     要调用的 path
   * @param array $argus    参数
   * @return json
   */
  public function callSelf($method = '', $uri = '', $argus = [])
  {
    $requestUri = explode('/', $uri);
    if (count($requestUri) !== 3) {
      throw new CoreHttpException(400);
    }

    $request = self::$container->getSingle('request');
    $request->method        = $method;
    $request->requestParams = $argus;
    $request->getParams     = $argus;
    $request->postParams    = $argus;
    
    $router = self::$container->getSingle('router');
    $router->moduleName     = $requestUri[0];
    $router->controllerName = $requestUri[1];
    $router->actionName     = $requestUri[2];
    $router->routeStrategy  = 'micromonomer';
    $router->init($this);
    return $this->responseData;
  }

  /**
   * 内部调用 get
   *
   * @param string $uri   要调用的 path 
   * @param array $argus  参数
   * @return void
   */
  public function get($uri = '', $argus = [])
  {
    return $this->callSelf('get', $uri, $argus);
  }

  /**
   * 内部调用 post
   *
   * @param string $uri   要调用的 path
   * @param array $argus  参数
   * @return void
   */
  public function post($uri = '', $argus = [])
  {
    return $this->callSelf('post', $uri, $argus);
  }

  /**
   * 内部调用 put
   *
   * @param string $uri   要调用的path
   * @param array $argus  参数
   * @return void
   */
  public function put($uri = '', $argus = [])
  {
    return $this->callSelf('put', $uri, $argus);
  }

  /**
   * 内部调用 delete
   *
   * @param string $uri   要调用的path
   * @param array $argus  参数
   * @return void
   */
  public function delete($uri = '', $argus = [])
  {
    return $this->callSelf('delete', $uri, $argus);
  }

  /**
   * 运行运用
   *
   * @param Request $request  请求对象
   * @return void
   */
  public function run(Closure $request)
  {
    self::$container->setSingle('request', $request);
    foreach ($this->handlesList as $handle) {
      $instance = $handle();
      $instance->register($this);
    }
  }

  /**
   * 生命周期结束
   * 
   * 响应请求
   *
   * @param Closure $closure 响应类
   * @return void
   */
  public function response(Closure $closure)
  {
    if ($this->notOutput === true) {
      return ;
    }

    if ($this->isCli === 'yes') {
      $closure()->cliModeSuccess($this->responseData);
      return ;
    }

    // 获取是否使用 rest
    $useRest = self::$container->getSingle('config')->config['rest_response'];

    if ($useRest) {
      $closure()->restSuccess($this->responseData);
    }
    $closure()->response($this->responseData);
  }

}