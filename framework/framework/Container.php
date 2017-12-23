<?php

namespace Framework;

use Framework\Exceptions\CoreHttpException;

/**
 * 依赖注入容器
 */
class Container
{
  /**
   * 类映射
   *
   * @var array
   */
  private $classMap = [];

  /**
   * 映射
   *
   * @var array
   */
  public $instanceMap = [];

  /**
   * 注入一个类
   *
   * @param string $alias       类别名
   * @param string $objectName  类名
   * @return void
   */
  public function set($alias = '', $objectName = '')
  {
    $this->classMap[$alias] = $objectName;
    if (is_callable($objectName)) {
      return $objectName;
    }
    return new $objectName;
  }

  /**
   * 获取一个类的实例
   *
   * @param string $alias 类名或别名
   * @return void
   */
  public function get($alias = '')
  {
    if (array_key_exists($alias, $this->classMap)) {
      if (is_callable($this->classMap[$alias])) {
        return $this->classMap[$alias];
      }
      return $this->classMap[$alias];
    }

    throw new CoreHttpException(404, 'Class:' . $alias);
  }

  /**
   * 注入一个单例
   *
   * @param string $alias                   类名或别名
   * @param object||closure||string $object 实例或闭包或类名
   * @return object
   * 
   * is_callable 检测参数是否为合法的可调用结构
   * is_object   检测变量是否是一个对象
   * get_class   返回对象的类名
   */
  public function setSingle($alias = '', $object = '')
  {
    // 先检测参数是否为合法可调用结构 {{{
    if (is_callable($alias)) { // 类名，如果是可调用结构
      $instance  = $alias();
      $className = get_class($instance);
      $this->instanceMap[$className] =$instance; // 类名 => 实例
      return $instance;
    }

    if (is_callable($object)) { // 类名，可调用
      if (empty($alias)) {
        throw new CoreHttpException(400, "{$alias} is empty");
      }
      if(array_key_exists($alias, $this->instanceMap)) {
        return $this->instanceMap[$alias];
      }
      $this->instanceMap[$alias] = $object; // 别名 => 实例
    }
    // }}}

    // 再检测参数是否为一个对象{{{
    if (is_object($alias)) {
      $className = get_class($alias);
      if (array_key_exists($className, $this->instanceMap)) {
        return $this->instanceMap[$className];
      }
      $this->instanceMap[$className] = $alias;
      return $this->instanceMap[$className];
    }

    if (is_object($object)) {
      if (empty($alias)) {
        throw new CoreHttpException(400, "{$alias} is empty");
      }
      $this->instanceMap[$alias] = $object;
      return $this->instanceMap[$alias];
    }
    // }}}

    if (empty($alias) && empty($object)) {
      throw new CoreHttpException(400, "{$alias} and {$object} is empty");
    }
    
    $this->instanceMap[$alias] = new $alias();
    return $this->instanceMap[$alias];
  }

  /**
   * 获取一个单例
   *
   * @param string $alias     类名或别名
   * @param Closure $closure  闭包
   * @return object
   */
  public function getSingle($alias = '', $closure = '')
  {
    if (array_key_exists($alias, $this->instanceMap)) {
      $instance = $this->instanceMap[$alias];
      if (is_callable($instance)) {
        return $this->instanceMap[$alias] = $instance();
      }
      return $instance;
    }

    if (is_callable($closure)) {
      return $this->instanceMap[$alias] = $closure();
    }

    throw new CoreHttpException(404, 'class:' . $alias);
  }

}