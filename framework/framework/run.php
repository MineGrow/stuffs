<?php
/**
 * 框架运行文件
 */

 use Framework\Handles\ErrorHandle;
 use Framework\Handles\ExceptionHandle;
 use Framework\Handles\RouterHandle;
 use Framework\Handles\ConfigHandle;
 use Framework\Handles\LogHandle;
 use Framework\Handles\NosqlHandle;
 use Framework\Handles\UserDefinedHandle;
 use Framework\Exceptions\CoreHttpException;
 use Framework\Request;
 use Framework\Response;

 require(__DIR__ . '/App.php');

 try {

  // 初始化应用 {{{
  $app = new Framework\App(realpath(__DIR__ . '/..'), function() {
    return require(__DIR__ . '/Load.php');
  });
  // }}}

  // 挂载 handles {{{
  $app->load(function() {
    return new ConfigHandle();      // 加载预定义配置机制
  });
  $app->load(function() {
    return new LogHandle();         // 加载日志处理机制
  });
  $app->load(function() {
    return new ErrorHandle();       // 加载错误处理机制
  });
  $app->load(function() {
    return new ExceptionHandle();   // 加载异常处理机制
  });
  $app->load(function() {
    return new NosqlHandle();       // 加载 nosql 机制
  });
  $app->load(function() {
    return new UserDefinedHandle(); // 加载用户自定义机制
  });
  $app->load(function() {
    return new RouterHandle();      // 加载路由机制
  });
  // }}}

  // 启动应用{{{
    $app->run(function() use ($app) {
      return new Request($app);
    });
  //}}}

  // 响应结果 {{{ 应用生命周期结束
    $app->response(function() {
      return new Response();
    });
  // }}}
  
 } catch (CoreHttpException $e) {
  $e->response();
 }