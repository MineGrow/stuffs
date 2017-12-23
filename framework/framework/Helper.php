<?php

use Framework\App;

/**
 * 获取环境参数
 *
 * @param string $paramName 参数名
 * @return mixed
 */
function env($paramName = '')
{
  return App::$container->getSingle('request')->env($paramName);
}

/**
 * 浏览器友善的打印数据
 *
 * @param array $data 数据
 * @return mixed
 */
function dump($data = [])
{
  ob_start();
  var_dump($data);
  $output = ob_get_clean();
  if (!extension_loaded('xdebug')) {
    $output = preg_replace('/\]\=\n(\s+)/m', '] => ', $output);
    $output = '<pre>' . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
  }
  echo $output;
  return null;
}

/**
 * log
 *
 * @param array $data       log 数据
 * @param string $fileName  log 文件名  绝对路径
 * @return void
 */
function easy_log($data = [], $fileName = 'debug')
{
  $time = date('Y-m-d H:i:s', time());
  error_log(
    "[{$time}]: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n",
    3,
    $fileName . '.log'
  );
}

