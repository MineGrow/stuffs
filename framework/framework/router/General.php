<?php

namespace Framework\Router;

use Framework\Router\FrameRouter;
use Framework\Router\RouterInterface;

class General implements RouterInterface
{
  
  // private $app;

  public function route(FrameRouter $entrance)
  {
    $app            = $entrance->app;
    $request        = $app::$container->getSingle('request');
    $moduleName     = $request->request('module');
    $controllerName = $request->request('controller');
    $actionName     = $request->request('action');

    if (! empty($moduleName)) {
      $entrance->moduleName = $moduleName;
    }

    if (! empty($controllerName)) {
      $entrance->controllerName = $controllerName;
    }

    if (! empty($actionName)) {
      $entrance->actionName = $actionName;
    }

    // CLI 模式不输出
    if (empty($actionName) && $entrance->app->isCli === 'yes') {
      $entrance->app->notOutput = true;
    }
  }
}