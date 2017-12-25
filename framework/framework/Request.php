<?php

namespace Framework;

use Framework\Exceptions\CoreHttpException;

class Request
{
  /**
   * 请求模块
   *
   * @var string
   */
  private $module = '';

  /**
   * 请求控制器
   *
   * @var string
   */
  private $controller = '';

  /**
   * 请求操作
   *
   * @var string
   */
  private $action = '';

  /**
   * 请求 server 参数
   *
   * @var array
   */
  private $serverParams = [];

  /**
   * 请求 env 参数
   *
   * @var array
   */
  private $envParams = [];

  /**
   * 请求所有参数
   *
   * @var array
   */
  private $requestParams = [];

  /**
   * 请求 GET 参数
   *
   * @var array
   */
  private $getParams = [];

  /**
   * 请求 POST 参数
   *
   * @var array
   */
  private $postParams = [];

  /**
   * http 方法名称
   *
   * @var string
   */
  private $method = '';

  /**
   * 服务 ip
   *
   * @var string
   */
  private $serverIp = '';

  /**
   * 客户端 ip
   *
   * @var string
   */
  private $clientIp = '';

  /**
   * 请求开始时间
   *
   * @var integer
   */
  private $beginTime = 0;

  /**
   * 请求结束时间
   *
   * @var integer
   */
  private $endTime = 0;

  /**
   * 请求消耗时间 ms 毫秒
   *
   * @var integer
   */
  private $consumeTime = 0;

  /**
   * 请求身份 id
   * 
   * 每个请求都赋予唯一的身份识别 id，便于追踪问题
   *
   * @var string
   */
  private $requestId = '';

  public function __construct(App $app)
  {
    $this->serverParams = $_SERVER;
    $this->method       = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'get';
    $this->serverIp     = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $this->clientIp     = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
    $this->beginTime    = isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : time(true);

    if ($app->isCli === 'yes') { // cli 模式
      $this->requestParams  = isset($_REQUEST['argv']) ? $_REQUEST['argv'] : [];
      $this->getParams      = isset($_REQUEST['argv']) ? $_REQUEST['argv'] : [];
      $this->postParams     = isset($_REQUEST['argv']) ? $_REQUEST['argv'] : [];
    } else {
      $this->requestParams  = $_REQUEST;
      $this->getParams      = $_GET;
      $this->postParams     = $_POST;
    }

    // 加载环境参数
    $this->loadEnv($app);
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
    $this->$name = $value;
  }

  /**
   * 获取 GET 参数
   *
   * @param string $value       参数名
   * @param string $default     默认值
   * @param boolean $checkEmpty 值为空时是否返回默认值，默认 true
   * @return mixed
   */
  public function get($value = '', $default = '', $checkEmpty = true)
  {
    if (! isset($this->getParams[$value])) {
      return '';
    }

    if (empty($this->getParams[$value]) && $checkEmpty) {
      return $default;
    }

    return htmlspecialchars($this->getParams[$value]);
  }

  /**
   * 获取 post 参数
   *
   * @param string $value       参数名
   * @param string $default     默认值
   * @param boolean $checkEmpty 值为空时是否返回默认值，默认 true
   * @return mixed
   */
  public function post($value = '', $default = '', $checkEmpty = true)
  {
    if (! isset($this->postParams[$value])) {
      return '';
    }

    if (empty($this->postParams[$value]) && $checkEmpty) {
      return $default;
    }

    return htmlspecialchars($this->postParams[$value]);
  }

  /**
   * 获取 request 参数
   *
   * @param string $value       参数名
   * @param string $default     默认值
   * @param boolean $checkEmpty 值为空时是否返回默认值，默认 true
   * @return mixed
   */
  public function request($value = '', $default = '', $checkEmpty = true)
  {
    if (! isset($this->requestParams[$value])) {
      return '';
    }

    if (empty($this->requestParams[$value]) && $checkEmpty) {
      return $default;
    }

    return htmlspecialchars($this->requestParams[$value]);
  }

  /**
   * 获取所有参数
   *
   * @return array
   */
  public function all()
  {
    $res = array_merge($this->postParams, $this->getParams);
    foreach ($res as &$v) {
      $v = htmlspecialchars($v);
    }
    return $res;
  }

  /**
   * 获取 SERVER 参数
   *
   * @param string $value 参数名
   * @return mixed
   */
  public function server($value = '')
  {
    if (isset($this->serverParams[$value])) {
      return $this->serverParams[$value];
    }
    return '';
  }

  /**
   * 获取 env 参数
   *
   * @param string $value 参数名
   * @return mixed
   */
  public function env($value = '')
  {
    if (isset($this->envParams[$value])) {
      return $this->envParams[$value];
    }
    return '';
  }

  /**
   * 机制环境参数
   *
   * @param App $app  框架实例
   * @return void
   */
  public function loadEnv(App $app)
  {
    $env = parse_ini_file($app->rootPath . '/.env', true);
    if ($env === false) {
      throw CoreHttpException('load env fail', 500);
    }
    $this->envParams = array_merge($_ENV, $env);
  }

  /**
   * 参数验证
   *
   * @param string $paramName 参数名
   * @param string $rule      规则
   * @param integer $length   rule 为 length 时，需要传值 默认为 0 
   * @return mixed
   */
  public function check($paramName = '', $rule = '', $length = 0)
  {
    if (! is_int($length)) {
      throw new CoreHttpException(400, "length type is not int");
    }

    if ($rule === 'require') {
      if (! empty($this->request($paramName))) {
        return ;
      }
      throw new CoreHttpException(404, "param {$paramName}");
    }

    if ($rule === 'length') {
      if (strlen($this->request($paramName)) === $length) {
        return ;
      }
      throw new CoreHttpException(400, "params {$paramName} length is not {$length}");
    }

    if ($rule === 'number') {
      if (is_numeric($this->request($paramName))) {
        return ;
      }

      throw new CoreHttpException(400, "{$paramName} type is not number");
    }
  }
}