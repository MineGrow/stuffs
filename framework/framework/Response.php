<?php

namespace Framework;

class Response
{
  /**
   * 构造函数
   */
  public function __construct()
  {
    # code...
  }

  /**
   * 响应
   *
   * @param mixed $response 响应内容
   * @return json
   */
  public function response($response)
  {
    header('Content-Type:Application/json; Charset=utf-8');
    die(json_encode($response, JSON_UNESCAPED_UNICODE));
  }

  /**
   * REST 成功响应
   *
   * @param mixed $response 响应内容
   * @return json
   */
  public function restSuccess($response)
  {
    header('Content-Type:Application/json; Charset=utf-8');
    die(json_encode([
      'code'    => 200,
      'message' => 'OK',
      'result'  => $response
    ], JSON_UNESCAPED_UNICODE));
  }

  /**
   * Cli 模式成功响应
   *
   * @param mixed $response 响应内容
   * @return array
   */
  public function cliModeSuccess($response)
  {
    var_dump([
      'code'    => 200,
      'message' => 'OK',
      'result'  => $response
    ]);
  }

  /**
   * REST 失败响应
   *
   * @param integer $code   响应代码
   * @param string $message 描述
   * @param mixed $response 响应内容
   * @return json
   */
  public function restFail($code = 500, $message = 'Internet Server Error', $response)
  {
    header('Content-Type:Application/json; Charset=utf-8');
    die(json_encode([
      'code'    => $code,
      'message' => $message,
      'result'  => $response
    ], JSON_UNESCAPED_UNICODE));
  }

  public function __get($name = '')
  {
    return $this->$name;
  }

  public function __set($name = '', $value = '')
  {
    $this->$name = $value;
  }
}