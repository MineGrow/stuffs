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
  |-- App.php                       [框架类]
  |-- Load.php                      [自加载类]
  |-- run.php                       [框架启动脚本]