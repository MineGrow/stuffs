<?php

namespace Framework\Handles;

use Framework\App;
use Framework\Handles\Handle;
use Framework\Exceptions\CoreHttpException;

class LogHandle implements Handle
{
  private $logPath = '';

  private $logFileName = 'framework-run';

  /**
   * 延时注册 Logger 实例到容器
   *
   * @param App $app
   * @return void
   */
  public function register(App $app)
  {
    App::$container->setSingle('logger', function() {
      return new LogHandle();
    });
  }

  public function __construct()
  {
    // 日志目录检查
    $this->logPath = env('log_path');
    if (empty($this->logPath) || !isset($this->logPath['path'])) {
      throw new CoreHttpException(400, 'log path is not defined');
    }

    $this->logPath = $this->logPath['path'];
    $this->logPath = App::$app->rootPath . $this->logPath;
    if (! file_exists($this->logPath)) {
      mkdir($this->logPath, 0777, true);
    }
    // 构建日志文件名称
    $this->logFileName .= '.' . date('Ymd', time());
  }

  /**
   * 写入日志
   *
   * @param array $data
   * @return void
   */
  public function write($data = [])
  {
    easy_log(
      $data,
      $this->logPath . $this->logFileName
    );
  }
}