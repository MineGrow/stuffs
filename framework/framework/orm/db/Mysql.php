<?php

namespace Framework\Orm\Db;

use Framework\Orm\DB;
use Framework\Exceptions\CoreHttpException;
use PDO;

class Mysql
{
  private $dbhost = '';

  private $dbname = '';

  private $dns    = '';

  private $username = '';

  private $password = '';

  private $pdo      = '';

  /**
   * 预处理实例
   * 
   * 代表一条预处理语句，并在该语句被执行后代表一个相关的结果集
   *
   * @var string
   */
  private $pdoStatement = '';

  public function __construct($dbhost = '', $dbname = '', $username = '', $password = '')
  {
    $this->dbhost   = $dbhost;
    $this->dbname   = $dbname;
    $this->dsn      = "mysql:dbname={$this->dbname};host={$this->dbhost};";
    $this->username = $username;
    $this->password = $password;

    $this->connect();
  }

  private function connect()
  {
    $this->pdo = new PDO(
      $this->dsn,
      $this->username,
      $this->password
    );
  }

  public function __get($name = '')
  {
    return $this->$name;
  }

  public function __set($name = '', $value = '')
  {
    $this->$name = $value;
  }

  public function findOne(DB $db)
  {
    $this->pdoStatement = $this->pdo->prepare($db->sql);
    $this->bindValue($db);
    $this->pdoStatement->execute();
    return $this->pdoStatement->fetch(PDO::FETCH_ASSOC);
  }

  public function findAll(DB $db)
  {
    $this->pdoStatement = $this->pdo->prepare($db->sql);
    $this->bindValue($db);
    $this->pdoStatement->execute();
    return $this->pdoStatement->fetchAll(PDO::FETCH_ASSOC);
  }

  public function save(DB $db)
  {
    $this->pdoStatement = $this->pdo->prepare($db->sql);
    $this->bindValue($db);
    $res = $this->pdoStatement->execute();
    if(! $res) {
      return false;
    }

    return $db->id = $this->pdo->lastInsertId();
  }

  public function delete(DB $db)
  {
    $this->pdoStatement = $this->pdo->prepare($db->sql);
    $this->bindValue($db);
    $this->pdoStatement->execute();
    return $this->pdoStatement->rowCount();
  }

  public function update(DB $db)
  {
    $this->pdoStatement = $this->pdo->prepare($db->sql);
    $this->bindValue($db);
    return $this->pdoStatement->execute();
  }

  public function query(DB $db)
  {
    $res = [];
    foreach ($this->pdo->query($db->sql, PDO::FETCH_ASSOC) as $v) {
      $res[] = $v;
    }
    return $res;
  }
  
  public function bindValue(DB $db)
  {
    if (empty($db->params)) {
      return;
    }

    foreach ($db->params as $k => $v) {
      $this->pdoStatement->bindValue(":{$k}", $v);
    }
  }

  public function beginTransaction()
  {
    $this->pdo->beginTransaction();
  }

  public function commit()
  {
    $this->pdo->commit();
  }

  public function rollBack()
  {
    $this->pdo->rollBack();
  }
}