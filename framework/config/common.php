<?php

use Framework\Helper;

return [
  // 应用目录名称
  'application_folder_name' => 'app',
  // 脚本目录名称
  'jobs_folder_name'        => 'jobs',
  // 默认模块
  'module'  => [
    'demo'
  ],
  // 默认路由配置
  'route'   => [
    'default_module'    => 'demo',    // 默认模块
    'default_controller'=> 'index',   // 默认控制器
    'default_action'    => 'hello',   // 默认操作
  ],

  // 响应结果是否使用框架定义的 rest 风格
  'rest_response'       => true,

  // 默认时区
  'default_timezone'    => 'Asia/Shanghai',
];