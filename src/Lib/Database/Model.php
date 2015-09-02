<?php
namespace Lib\Database;

use Lib\Database\PDOManager;
use Lib\Database\DataObject;
use Lib\Database\DatabaseException;
use Lib\Log\Debugger;

class Model extends QueryBuilder
{

    CONST PK = "id"; 
    
    private $master_db = "";
    private $slave_db = "";

    private $table = null;
    private $conn = null;

    private $is_collection = true;
    private $result = null;

    CONST DEFAULT_FETCH_MODE = \PDO::FETCH_ASSOC;


    public function __construct()
    {
        parent::__construct();
    }

    public function getPDO($is_write = true)
    {
        return $this->getConnection($is_write);
    }
    /**
     * 转成对象
     * @return [type] [description]
     */
    private function toObject()
    {
        if ($this->is_collection) {
            $object_arr = [];
            foreach ($this->result as $pk_val => $row) {
                $object_arr[$pk_val] = $this->parseDataObject($row);
            }
            return $object_arr;
        } else {
            return $this->parseDataObject($this->result);
        }
    }

    public function parseDataObject($row)
    {
        // 创建结果集对象 
        // $child_class = get_called_class();
        // $data = new $child_class;
        if (empty($row) || !is_array($row)) {
            return null;
        }
        $data = new DataObject();
        foreach ($row as $column => $value) {
            $formated_property = strtolower($column);
            $data->set($formated_property, $value);
        }
        return $data;
    }

    public function toArray()
    {
        return $this->result;
    }
    /**
     * 查询所有数据
     * @return [type] [description]
     */
    public function findAll()
    {
        $this->is_collection = true;
        $this->result = $this->slaveFind($this->is_collection);
        return $this->toObject();
    }
    /**
     * 查询单条数据
     * @return [type] [description]
     */
    public function find()
    {
        $this->is_collection = false;
        $this->result = $this->slaveFind($this->is_collection);
        return $this->toObject();
    }

    private function slaveFind($is_collection)
    {
        $trace_key = "SQL" . rand(0,1000);
        Debugger::instance()->traceBegin($trace_key);
        // 查询时使用 slave 查询
        $conn = $this->getSlaveConnection();
        // 创建 sql 语句
        $sql = $this->buildQuerySql();
        $stmt = $this->getPrepareStatement($conn, $sql);
        $this->executeBindParams($stmt, $sql, $this->getQueryMapping());
        if ($is_collection) {
            // 需要返回结果集的情况
            $result = $stmt->fetchAll(self::DEFAULT_FETCH_MODE);
            $result = $this->changeArrayKeyToPK($result);
        } else {
            // 返回单条结果
            $result = $stmt->fetch(self::DEFAULT_FETCH_MODE);
        }
        Debugger::instance()->traceEnd($trace_key, $sql . "; params:" . json_encode($this->getQueryMapping()));
        return $result;
    }
    /**
     * 将查询结果的主键值作为数组的 key 
     * @param  [type] $arr [description]
     * @return [type]      [description]
     */
    public function changeArrayKeyToPK($arr)
    {
        if (empty($this::PK)) {
            return $arr;
        }
        $result = [];
        foreach ($arr as $value) {
            if (isset($value[$this::PK])) {
                $result[$value[$this::PK]] = $value;
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }
    /**
     * 主键查询
     * @param  [type] $pk_value [description]
     * @return [type]           [description]
     */
    public function pk($pk_value)
    {
        $trace_key = "SQL" . rand(0,1000);
        Debugger::instance()->traceBegin($trace_key);
        $pk_column = $this::PK;
        if (empty($pk_column)) {
            throw new DatabaseException(DatabaseException::TABLE_PK_NOT_EXIST);
        }
        $this->is_collection = false;
        $sql = "select * from " . $this->getTableName();
        $sql .= " where `$pk_column` = :$pk_column";
        $conn = $this->getMasterConnection();

        $bind_value_arr = [":$pk_column" => $pk_value];
        $stmt = $this->getPrepareStatement($conn, $sql);
        $this->executeBindParams($stmt, $sql, $bind_value_arr);
        $this->result = $stmt->fetch(self::DEFAULT_FETCH_MODE);
        Debugger::instance()->traceEnd($trace_key, $sql . "; params:" . json_encode($bind_value_arr));
        return $this->toObject();
    }
    /**
     * 查询数据行数
     * @return [type] [description]
     */
    public function count()
    {
        $this->count_column = 1 ;
        // 查询时使用 slave 查询
        $conn = $this->getSlaveConnection();
        // 创建 sql 语句
        $sql = $this->buildQuerySql();
        $stmt = $this->getPrepareStatement($conn, $sql);
        $this->executeBindParams($stmt, $sql, $this->getQueryMapping());
        $result = $stmt->fetch(\PDO::FETCH_NUM);
        return current($result);
    }

    public function save($mixed)
    {
        if (empty($mixed)) {
            // TODO throw exception
        }
        $pk_value = $this->getPKValue($mixed);
        if ($pk_value && $this->pk($pk_value)) {
            // 执行更新操作
            return $this->updateRow($pk_value, $mixed);
        } else {
            // 执行插入操作
            return $this->insertRow($mixed);
        }
    }
    /**
     * 数组或对象中是否含有主键
     * @param  [type]  $mixed [description]
     * @return boolean        [description]
     */
    private function getPKValue($mixed)
    {
        if (is_array($mixed) && isset($mixed[$this::PK])) {
            return $mixed[$this::PK];
        }
        if (is_object($mixed) && isset($mixed->{$this::PK})) {
            return $mixed->{$this::PK};
        }
        return null;
    }
    /**
     * 更新单条数据
     * @param  [type] $pk_value [description]
     * @param  [type] $mixed    [description]
     * @return [type]           [description]
     */
    private function updateRow($pk_value, $mixed)
    {
        if (isset($mixed['update_time'])) {
            // 使用 mysql 更新时间
            unset($mixed['update_time']);
        }
        if (empty($mixed)) {
            return false;
        }

        $pk_column = $this::PK;
        $sql = "update " . $this->getTableName();
        // 更新的列
        $set_column_str = "";
        // 更新的值
        $bind_value_arr = [];
        foreach ($mixed as $key => $value) {
            $column = trim($key);
            if ($key == $pk_column) {
                continue;
            }
            $formated_column = "`" . $column . "`";
            if ($set_column_str) {
                $set_column_str .= ",";
            } else {
                $set_column_str .= " set ";
            }
            $set_column_str .= "$formated_column=:$column";
            $bind_value_arr[":$column"] = $value;
        }
        $bind_value_arr[":" . $pk_column] = $pk_value;
        $sql .= $set_column_str . " where `" . $pk_column . "`=:" . $pk_column ." limit 1";

        $conn = $this->getMasterConnection();
        $stmt = $this->getPrepareStatement($conn, $sql);
        $this->executeBindParams($stmt, $sql, $bind_value_arr);
        return $pk_value;
    }
    /**
     * 插入单行 返回插入的last id
     * @param  [type] $mixed [description]
     * @return [type]        [description]
     */
    public function insertRow($mixed)
    {
        if (empty($mixed)) {
            return false;
        }
        $sql = "insert into " . $this->getTableName();
        // 更新的列
        $set_column_arr = [];
        // 更新的值
        $bind_value_arr = [];
        foreach ($mixed as $key => $value) {
            $column = trim($key);
            $formated_column = "`" . $column . "`";
            $set_column_arr[] = $formated_column;
            $bind_value_arr[":$column"] = $value;
        }
        $sql .= "(" . implode(",", $set_column_arr) . ") values " . 
            "(" . implode(",", array_keys($bind_value_arr)) . ") ;";
        $conn = $this->getMasterConnection();
        $stmt = $this->getPrepareStatement($conn, $sql);
        $insert_result = $this->executeBindParams($stmt, $sql, $bind_value_arr);

        if (empty($this::PK)) {
            return $insert_result;
        }
        return $conn->lastInsertId($this::PK);
    }
    /**
     * 更新多条数据 
     * @param  [array] $update_arr [更新的数据]
     * @return [int]               [更新行数]
     */
    public function update($update_arr)
    {
        if (empty($update_arr)) {
            return false;
        }
        $sql = "update " . $this->getTableName();
        // 更新的列
        $set_column_str = "";
        // 更新的值
        $bind_value_arr = [];
        $pk_column = $this::PK;
        foreach ($update_arr as $key => $value) {
            $column = trim($key);
            if ($key == $pk_column) {
                continue;
            }
            $formated_column = "`" . $column . "`";
            if ($set_column_str) {
                $set_column_str .= ",";
            } else {
                $set_column_str .= " set ";
            }
            $set_column_str .= "$formated_column=:$column";
            $bind_value_arr[":$column"] = $value;
        }
        $sql .= $set_column_str;
        $sql .= $this->buildWhere();
        if ($this->limit_num) {
            $sql .= " limit " . intval($this->limit_num);
        }
        $bind_value_arr = array_merge($bind_value_arr, $this->getQueryMapping());
        $conn = $this->getMasterConnection();
        $stmt = $this->getPrepareStatement($conn, $sql);
        $this->executeBindParams($stmt, $sql, $bind_value_arr);
        return $stmt->rowCount();
    }
    /**
     * 删除数据
     * @return [bool] [description]
     */
    public function delete()
    {
        $sql = "delete from " . $this->getTableName();
        $sql .= $this->buildWhere();
        if ($this->limit_num) {
            $sql .= " limit " . intval($this->limit_num);
        }
        $conn = $this->getMasterConnection();
        $stmt = $this->getPrepareStatement($conn, $sql);
        $result = $this->executeBindParams($stmt, $sql, $this->getQueryMapping());
        return $result;
    }
    /**
     * prepare sql
     * @param  [type] $connection [description]
     * @param  [type] $sql        [description]
     * @return [type]             [description]
     */
    private function getPrepareStatement($connection, $sql)
    {
        $statement = $connection->prepare($sql);
        if ($statement === false) {
            ob_start(); 
            debug_print_backtrace(2); 
            $trace = ob_get_contents(); 
            ob_end_clean(); 
        }
        return $statement;
    }
    /**
     * 执行查询
     * @param  [type] $statement [description]
     * @param  [type] $sql       [description]
     * @param  [type] $mapping   [description]
     * @return [type]            [description]
     */
    private function executeBindParams($statement, $sql, $mapping)
    {
        try {
            $exec_result = $statement->execute($mapping);
            return  $exec_result;
        } catch (Exception $e) {
            // TODO 记录日志
            $e->getMessage();
        }
    }

}
