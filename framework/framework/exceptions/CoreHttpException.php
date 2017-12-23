<?php

namespace Framework\Exceptions;

use Exception;
use Framework\App;

class CoreHttpException extends Exception
{
  /**
   * Http异常响应代码code
   *
   * @var array
   */
  private $httpCode = [
    400   => 'Bad Request',           // 缺少参数或者必传参数为空
    403   => 'Forbidden',             // 没有访问权限
    404   => 'Not Found',             // 访问的资源不存在
    500   => 'Internet Server Error', // 代码错误
    503   => 'Service Unavailable',   // 远程服务器错误
  ];

  /**
   * 构造函数
   *
   * @param integer $code exception code
   * @param string $extra 错误信息补充
   */
  public function __construct($code = 200, $extra = '')
  {
    $this->code = $code;
    if (empty($extra)) {
      $this->message = $this->httpCode[$code];
      return ;
    }
    $this->message = $extra . ' ' . $this->httpCode[$code];
  }

  /**
   * Http 响应
   *
   * http://php.net/manual/zh/language.exceptions.extending.php
   * 
   * @return json
   */
  public function response()
  {
    // 响应数据
    $data = [
      '__coreError' => [
        'code'    => $this->getCode(),    // 返回异常代码
        'message' => $this->getMessage(), // 返回异常信息
        'infomations' => [
          'file'  => $this->getFile(),    // 返回发生异常的文件名
          'line'  => $this->getLine(),    // 返回发生异常的代码行号
          'trace' => $this->getTrace(),   // backtrace() 数组
        ]
      ]
    ];

    // 记录日志
    App::$container->getSingle('logger')->write($data);

    // 执行响应
    header('Content-Type:Application/json; Charset=utf-8');
    die(json_encode($data, JSON_UNESCAPED_UNICODE));
  }

  /**
   * Http 异常响应 [代码错误]
   *
   * @param array $e 异常
   * @return json
   */
  public static function responseErr($e)
  {
    // 响应数据
    $data = [
      '__coreError' => [
        'code'    => 500,
        'message' => $e,
        'infomations' => [
          'file'  => $e['file'],
          'line'  => $e['line']
        ]
      ]
    ];

    // 记录日志
    App::$container->getSingle('logger')->write($data);

    // 执行响应
    header('Content-Type:Application/json; Charset=utf-8');
    die(json_encode($data));
  }
}