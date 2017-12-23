<?php

namespace Framework\Router;

use Framework\App;
use Framework\Exceptions\CoreHttpException;
use Closure;

class FrameRouter
{
  private $app;

  private $config;

  private $request;

  private $moduleName = '';

  private $controllerName = '';

  private $actionName = '';

  private $classPath = '';

  private $executeType = '';

  private $requestUri = '';

  /**
   * 路由策略
   *
   * @var string
   */
  private $routeStrategy = '';

  /**
   * 路由策略映射
   *
   * @var array
   */
  private $routeStrategyMap = [
    'general'     => 'Framework\Router\General',
    'pathinfo'    => 'Framework\Router\Pathinfo',
    'user-defined'=> 'Framework\Router\Userdefined',
    'micromonomer'=> 'Framework\Router\Micromonomer',
    'job'         => 'Framework\Router\Job'
  ];

  public function __get($name = '')
  {
    return $this->$name;
  }

  public function __set($name = '', $value = '')
  {
    $this->$name = $value;
  }

  /**
   * 注册路由处理机制
   *
   * @param App $app
   * @return void
   */
  public function init(App $app)
  {
    // 注入当前对象到容器
    $app::$container->setSingle('router', $this);
    // request uri
    $this->request      = $app::$container->getSingle('request');
    $this->requestUri   = $this->request->server('REQUEST_URI');

    $this->app          = $app;
    $this->config       = $app::$container->getSingle('config');
    // 获取默认配置
    $this->moduleName     = $this->config->config['route']['default_module'];
    $this->controllerName = $this->config->config['route']['default_controller'];
    $this->actionName     = $this->config->config['route']['default_action'];

    // 路由策略
    $this->strategyJudge();
    (new $this->routeStrategyMap[$this->routeStrategy])->route($this);

    // 判断是 app 还是 job
    $this->isAppOrJob($this);

    // 自定义路由判断
    if ((new $this->routeStrategyMap['user-defined'])->route($this)) {
      return;
    }

    // 启动路由
    $this->start();
  }

  /**
   * 判断 app 还是 job
   *
   * @return boolean
   */
  public function isAppOrJob()
  {
    // 任务类
    if ($this->routeStrategy === 'job') {
      $className        = $this->request->request('job');
      $actionName       = $this->request->request('action');
      $folderName       = ucfirst($this->config->config['jobs_folder_name']);
      $this->classPath  = "{$folderName}\\{$this->moduleName}\\{$className}";
      $this->executeType= 'job';
      return ;
    }

    // 获取控制器类
    $controllerName     = ucfirst($this->controllerName);
    $folderName         = ucfirst($this->config->config['applications_folder_name']);
    $this->classPath    = "{$folderName}\\{$this->moduleName}\\Controllers\\{$controllerName}";
    $this->executeType  = 'controller';
  }

  /**
   * 路由策略决策
   *
   * @return void
   */
  public function strategyJudge()
  {
    // 路由策略
    if (! empty($this->routeStrategy)) {
      return ;
    }

    // 任务路由
    if ($this->app->isCli === 'yes' && $this->request->get('router_mode') === 'job') {
      $this->routeStrategy = 'job';
      return ;
    }

    // 普通路由
    if (strpos($this->requestUri, 'index.php') || $this->app->isCli === 'yes')
    {
      $this->routeStrategy = 'general';
      return ;
    } else {
      $this->routeStrategy = 'pathinfo';
      return ;
    }
  }
  
  /**
   * 路由机制
   *
   * @return void
   */
  public function start()
  {
    // 判断模块存不存在
    if (! in_array(strtolower($this->moduleName), $this->config->config['module'])) {
      throw new CoreHttpException(404, 'Module:' . $this->moduleName);
    }

    // 判断控制器是否存在
    if (!class_exists($this->classPath)) {
      throw new CoreHttpException(404, "{$this->executeType}:{$this->classPath}");
    }

    // 实例化当前控制器
    $controller = new $this->classPath();
    if (! method_exists($controller, $this->actionName)) {
      throw new CoreHttpException(404, 'Action:' . $this->actionName);
    }

    // 调用操作
    $actionName = $this->actionName;
    // 获取返回值
    $this->app->responseData = $controller->$actionName();
  }
}