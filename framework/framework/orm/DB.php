<?php

namespace Framework\Orm;

use Framework\App;
use Framework\Exceptions\CoreHttpException;

class DB
{
  // SQL 解释器
  use Interpreter;

  // 数据库类型  只支持 mysql
  protected $dbtype = '';

  protected $tableName = '';

  // 数据库策略映射
  protected $dbStrategyMap = [
    'mysqldb' => 'Framework\Orm\Db\Mysql'
  ];

  protected $dbInstance;

  // 自增 id
  protected $id = '';

  // 走主库的查询语句
  private $master = ['insert', 'update', 'delete'];

  // 当前查询主从
  private $masterSlave = '';

  // 数据库配置
  private $dbConfig = [
    'dbhost'    => '',
    'dbname'    => '',
    'username'  => '',
    'password'  => ''
  ];

  // 构造函数
  public function __construct()
  {
    # code...
  }

  /**
   * 设置表名
   *
   * @param string $tableName
   * @return void
   */
  public static function table($tableName = '')
  {
    $db = new self;
    $db->tableName = $tableName;
    $prefix = App::$container->getSingle('config')->config['database']['dbprefix'];

    if (! empty($prefix)) {
      $db->tableName = $prefix . '_' . $db->tableName;
    }

    return $db;
  }

  /**
   * 初始化策略
   *
   * @param string $masterOrSlave 初始化主库还是从库
   * @return void
   */
  public function init($masterOrSlave = '')
  {
    $config = App::$container->getSingle('config');
    $this->dbtype = $config->config['database']['dbtype'];
    if (! empty($masterOrSlave)) {
      $this->masterSlave = $masterOrSlave;
    }

    $this->isMasterOrSlave();
    $this->decide();
  }

  /**
   * 策略决策
   *
   * @return void
   */
  public function decide()
  {
    $dbStrategyName   = $this->dbStrategyMap[$this->dbtype];
    $dbConfig         = $this->dbConfig;

    $this->dbInstance = App::$container->getSingle("
      {$this->dbtype}-{$this->masterSlave}", 
      function() use ($dbStrategyName, $dbConfig) {
        return new $dbStrategyName(
          $dbConfig['dbhost'],
          $dbConfig['dbname'],
          $dbConfig['username'],
          $dbConfig['password']
        );
      }
    );
  }
  
  /**
   * 判断走主库还是从库
   *
   * @return boolean
   */
  public function isMasterOrSlave()
  {
    if (! empty($this->masterSlave)) {
      $this->initMaster();
      return ;
    }

    foreach ($this->master as $v) {
      $res = stripos($this->sql, $v);
      if ($res === 0 || $res) {
        $this->initMaster();
        return ;
      }
    }

    $this->initSlave();
  }
  
  /**
   * 初始化主库
   *
   * @return void
   */
  public function initMaster()
  {
    $config = App::$container->getSingle('config');
    $dbConfig = $config->config['database'];
    
    $this->dbConfig['dbhost']   = $dbConfig['dbhost'];
    $this->dbConfig['dbname']   = $dbConfig['dbname'];
    $this->dbConfig['username'] = $dbConfig['username'];
    $this->dbConfig['password'] = $dbConfig['password'];

    $this->masterSlave = 'master';
  }

  /**
   * 初始化从库
   *
   * @return void
   */
  public function initSlave()
  {
    $config = App::$container->getSingle('config');
    if (! isset($config->config['database']['slave'])) {
      $this->initMaster();
      return ;
    }

    $slave      = $config->config['database']['slave'];
    $randSlave  = $slave[array_rand($slave)];
    $dbConfig   = $config->config["database-slave-{$rangSlave}"];
    $this->dbConfig['dbhost']   = $dbConfig['dbhost'];
    $this->dbConfig['dbname']   = $dbConfig['dbname'];
    $this->dbConfig['username'] = $dbConfig['username'];
    $this->dbConfig['password'] = $dbConfig['password'];

    $this->masterSlave = "slave-{$randSlave}";

  }

  /**
   * 查找一条数据
   *
   * @param array $data 查询的字段
   * @return void
   */
  public function findOne($data = [])
  {
    $this->select($data);
    $this->buildSql();
    $functionName = __FUNCTION__;
    return $this->dbInstance->$functionName($this);
  }

  /**
   * 查找所有的数据
   *
   * @param array $data 查询的字段
   * @return void
   */
  public function findAll($data = [])
  {
    $this->select($data);
    $this->buildSql();
    $functionName = __FUNCTION__;
    return $this->dbInstance->$functionName($this);
  }

  /**
   * 保存数据
   *
   * @param array $data
   * @return void
   */
  public function save($data = [])
  {
    $this->insert($data);
    $this->init();
    $functionName = __FUNCTION__;
    return $this->dbInstance->$functionName($this);
  }

  /**
   * 删除数据
   *
   * @return void
   */
  public function delete()
  {
    $this->del();
    $this->buildSql();
    $functionName = __FUNCTION__;
    return $this->dbInstance->$functionName($this);
  }

  /**
   * 更新数据
   *
   * @param array $data
   * @return void
   */
  public function update($data = [])
  {
    $this->updateData($data);
    $this->buildSql();
    $functionName = __FUNCTION__;
    return $this->dbInstance->$functionName($this);
  }

  /**
   * count 数据
   *
   * @param string $data
   * @return void
   */
  public function count($data = '')
  {
    $this->countColumn($data);
    $this->buildSql();
    return $this->dbInstance->findAll($this);
  }

  /**
   * sum 数据
   *
   * @param string $data
   * @return void
   */
  public function sum($data = '')
  {
    $this->sumColumn($data);
    $this->buildSql();
    return $this->dbInstance->findAll($this);
  }

  /**
   * query 数据
   *
   * @param string $data
   * @return void
   */
  public function query($data = '')
  {
    $this->queryColumn($data);
    $this->init();
    return $this->dbInstance->query($this);
  }

  /**
   * 构建 SQL 语句
   *
   * @return void
   */
  public function buildSql()
  {
    if (! empty($this->where)) {
      $this->sql .= $this->where;
    }

    if (! empty($this->orderBy)) {
      $this->sql .= $this->orderBy;
    }

    if (! empty($this->limit)) {
      $this->sql .= $this->limit;
    }

    $this->init();
  }

  /**
   * 开始事务
   *
   * @return void
   */
  public static function beginTransaction()
  {
    $instance = App::$container->getSingle('DB', function() {
      return new DB();
    });

    $instance->init('master');
    $instance->dbInstance->beginTransaction();
  }

  /**
   * 提交事务
   *
   * @return void
   */
  public static function commit()
  {
    $instance = App::$container->getSingle('DB', function() {
      return new DB();
    });

    $instance->init('master');
    $instance->dbInstance->commit();
  }

  /**
   * 回滚事务
   *
   * @return void
   */
  public static function rollBack()
  {
    $instance = App::$container->getSingle('DB', function() {
      return new DB();
    });
    $instance->init('master');
    $instance->dbIntance->rollBack();
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