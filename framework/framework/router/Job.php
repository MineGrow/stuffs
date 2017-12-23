<?php

namespace Framework\Router;

use Framework\App;
use Framework\Router\FrameRouter;
use Framework\Router\RouterInterface;
use Framework\Exceptions\CoreHttpException;
use ReflectionClass;
use Closure;

class Job implements RouterInterface
{
  private $app;

  private $config;

  /**
   * 路由方法
   *
   * @param FrameRouter $entrance
   * @return void
   */
  public function route(FrameRouter $entrance)
  {
    $entrance->app->notOutput = true;

    $app        = $entrance->app;
    $request    = $app::$container->getSingle('request');
    $moduleName = $request->request('module');
    $jobName    = $request->request('job');
    $actionName = $request->request('action');

    $entrance->moduleName = $moduleName;
    $entrance->jobName    = $jobName;
    $entrance->actionName = $actionName;

    // 获取 job 类
    $jobName = ucfirst($jobName);
    $appName = ucfirst($entrance->config->config['jobs_folder_name']);
    $this->classPath = "{$appName}\\{$moduleName}\\{$jobName}";
  }
}