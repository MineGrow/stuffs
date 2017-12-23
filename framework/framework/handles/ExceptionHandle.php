<?php

namespace Framework\Handles;

use Framework\App;
use Framework\Handles\Handle;
use Framework\Exceptions\CoreHttpException;

class ExceptionHandle implements Handle
{
  public function __contruct()
  {
    # code...
  }

  /**
   * 注册未捕获异常函数
   *
   * @param App $app  框架实例
   * @return void
   */
  public function register(App $app)
  {
    set_exception_handler([$this, 'exceptionHandler']);
  }

  /**
   * 未捕获异常函数
   *
   * @param object $exception 异常
   * @return void
   */
  public function exceptionHandler($exception)
  {
    $exceptionInfo = [
      'code'      => $exception->getCode(),
      'message'   => $exception->getMessage(),
      'file'      => $exception->getFile(),
      'line'      => $exception->getLine(),
      'trace'     => $exception->getTrace(),
      'previous'  => $exception->getPrevious()
    ];

    CoreHttpException::responseErr($exceptionInfo);
  }
}