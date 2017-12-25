# 构建自己的PHP框架

## 大致流程 
  [参考链接](https://github.com/TIGERB/easy-php/edit/master/README-CN.md)
  
  ``` 
  入口文件   
    ----> 注册自加载函数
    ----> 注册错误和异常处理函数
    ----> 加载配置文件
    ----> 请求
    ----> 路由
    ----> 控制器 <---> 数据模型
    ----> 响应
    ----> json
    ----> 视图渲染数据
  ```

  - 框架目录结构
  ```
  framework
  |-- exceptions                    [异常处理目录]
  |     |-- CoreHttpException.php   [核心http异常处理类]
  |-- handles                       [框架运行时挂载处理机制类目录]
  |     |-- Handle.php              [处理机制接口]
  |     |-- ConfigHandle.php        [配置文件处理机制类]
  |     |-- ErrorHandle.php         [错误处理机制类]
  |     |-- ExceptionHandle.php     [未捕获异常处理机制类]
  |     |-- LogHandle.php           [处理日志机制类]
  |     |-- NosqlHandle.php         [nosql处理机制类]
  |     |-- RouterHandle.php        [路由处理机制类]
  |     |-- UserDefinedHandle.php   [用户自定义处理机制]
  |-- traits                        [代码复用目录]
  |     |-- GlobalConstant.php      [全局常量控制]
  |-- router                        [路由策略]
  |     |-- RouterInterface.php     [路由策略接口]
  |     |-- FrameRouter.php         [路由策略入口类]
  |     |-- General.php             [普通路由策略]
  |     |-- Job.php                 [脚本任务路由]
  |     |-- Micromonomer.php        [微单体路由]
  |     |-- Pathinfo.php            [pathinfo 路由]
  |     |-- Userdefined.php         [用户自定义路由]
  |-- nosql                         [nosql 类目录]
  |     |-- Memcached.php           [Memcached 类文件]
  |     |-- MongoDB.php             [MongoDB 类文件]
  |     |-- Redis.php               [Redis 类文件]
  |-- orm                           [对象关系模型]
  |     |-- DB.php                  [数据库操作类]
  |     |-- Interpreter.php         [sql 解析器]
  |     |-- Model.php               [数据模型基类]
  |     |-- db                      [数据库类目录]
  |           |-- Mysql.php         [mysql 实体类]
  |-- App.php                       [框架类]
  |-- Container.php                 [服务容器]
  |-- Helper.php                    [框架助手类]
  |-- Load.php                      [自加载类]
  |-- Request.php                   [请求类]
  |-- Response.php                  [响应类]
  |-- run.php                       [框架启动脚本]

  app                               [PHP 应用目录]
  |-- demo                          [模块目录]
  |     |-- controllers             [控制器目录]
  |     |   |-- Index.php           [默认控制器文件]
  |     |-- Logics                  [逻辑层，主要写业务逻辑的地方]
  |     |   |-- exceptions          [异常目录]
  |     |   |-- gateway             [一个逻辑层实现的 gateway 演示]
  |     |   |-- tools               [工具类目录]
  |     |   |-- UserDefinedCase.php [注册框架加载到路由前的处理用例]
  |     |-- models                  [数据模型目录]
  |         |-- TestTable           [演示模型文件，定义一一对应的数据模型]

  jobs                              [脚本目录，写业务脚本的地方]
  |-- demo                          [模块目录]
  |     |-- Demo.php                [脚本演示文件]

  config                            [配置目录]
  |-- demo                          [模块配置目录]
  |     |-- config.php              [模块自定义配置]
  |     |-- route.php               [模块自定义路由]
  |-- common.php                    [公共配置]
  |-- database.php                  [数据库配置]
  |-- nosql.php                     [nosql 配置]

  public                            [公共资源目录]
  |-- dist                          [前端 build 之后的资源目录]
  |     |-- ...
  |-- index.html                    [前端入口文件，build 生成的文件]
  |-- index.php                     [后端入口文件]
  
  runtimes                          [临时目录]
  |-- logs                          [日志目录]
  |-- build                         [PHP 打包生成 phar 文件目录]
  
  vendor                            [composer 目录]
  .env.example                      [env 环境变量示例文件]
  .gitignore                        [git 忽略配置文件]
  composer.json                     [composer 配置文件]
  run                               [快速开始脚本]
  build                             [PHP 打包脚本]
  cli                               [框架 cli 模式运行脚本]
```