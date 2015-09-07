<?php
namespace Lib\Database;

use Lib\Database\Connection;
use Lib\Util\Config;

/**
*   数据库查询类
*/
class QueryBuilder
{

    protected $connection = null;

    protected static $db_config_list;

    protected $where_arr = [];
    protected $order_arr = [];
    protected $group_arr = [];
    protected $filter_column = [];
    protected $count_column = null;
    protected $sum_column = null;
    protected $limit_num = null;

    protected $query_mapping = [];

    protected $comparison = ["=", "<", "<=", ">", ">=", "in"];

    CONST DB_CONFIG_FILE = "database";

    public function __construct()
    {
    }

    protected function getMasterConnection()
    {
        return $this->getConnection(true);
    }

    protected function getSlaveConnection()
    {
        return $this->getConnection(false);
    }

    protected function getConnection($is_write = true)
    {
        // 获取配置文件数据库配置
        $db_config_list = $this->getConfigList();
        if ($is_write) {
            $db_config = $db_config_list[$this->master_db];
        } else {
            $db_config = $db_config_list[$this->slave_db];
        }
        $port = isset($db_config['port']) ? $db_config['port'] : 3306;
        $pdo = PDOManager::instance()->getPDO(
            $db_config['host'], $port, $db_config['name'], 
            $db_config['password'], $db_config['database']);
        return $pdo;
    }

    public function getConfigList()
    {
        if (empty(self::$db_config_list)) {
            self::$db_config_list = Config::get("db", self::DB_CONFIG_FILE);
        }
        return self::$db_config_list;
    }

    protected function getTableName()
    {
        // TODO check ` exist
        return "`" . $this->table . "`";
    }
    /**
     * 
     * @param  string $column [查询字段]
     * @return [type]         [description]
     */
    public function column($column = '')
    {
        if ($column && !in_array($column, $this->filter_column)) {
            $this->filter_column[] = "`" . trim($column) . "`";
        }
        return $this;
    }
    /**
     * where 查询 
     * @param  [type] $column       字段名
     * @param  [type] $compare_flag 比较符 $this->comparison
     * @param  [type] $mixed_value  比较值
     * @return [type]               $this
     */
    public function where($column, $compare_flag = null, $mixed_value = null)
    {
        if ($mixed_value === null) { // 只传两个参数 默认使用 "="
            $mixed_value = $compare_flag;
            $compare_flag = "=";
        }
        if (!in_array($compare_flag, $this->comparison)) {
            throw new DatabaseException(DatabaseException::COMPARISON_NOT_EXIST);
        }
        $this->where_arr[] = [trim($column), $compare_flag, $mixed_value];
        return $this;
    }
    /**
     * 查询返回条数
     * @param  [type] $limit_num 条数
     * @return [type]            [description]
     */
    public function limit($limit_num = null)
    {
        if (is_numeric($limit_num)) {
            $this->limit_num = $limit_num; 
        }
        return $this;
    }
    /**
     * 排序
     * @param  string $column     [description]
     * @param  string $order_type [description]
     * @return [type]             [description]
     */
    public function order($column = '', $order_type = "DESC")
    {
        $this->order_arr[] = [trim($column), $order_type];
        return $this;
    }
    /**
     * 分组条件
     * @param  [type] $column_mixed [description]
     * @return [type]               [description]
     */
    public function group($column_mixed = null)
    {
        $this->group_arr = (array) $column_mixed;
        return $this;
    }

    public function sum($column = '')
    {
        $this->sum_column = trim($column);
        return $this;
    }
    /**
     * 创建查询sql
     * @return [type] [description]
     */
    protected function buildQuerySql()
    {
        $sql = "select " . $this->buildColumn() . 
            " from " . $this->getTableName();

        $sql .= $this->buildWhere();
        $sql .= $this->buildGroup();
        $sql .= $this->buildOrder();
        if ($this->limit_num) {
            $sql .= " limit " . intval($this->limit_num);
        }
        return $sql;
    }

    protected function buildColumn()
    {
        $column_str = "*";
        if ($this->count_column) {
            $formated_column = is_numeric($this->count_column) ?
                $this->count_column : "`$this->count_column`";
            $column_str = "count($formated_column)";
        }
        if ($this->sum_column) {
            $column_str = "sum(`$this->sum_column`)";
        }
        if ($this->filter_column) {
            $column_str = implode(",", $this->filter_column);
        }
        return $column_str;
    }
    /**
     * 构建where条件
     * @return [type] [description]
     */
    protected function buildWhere()
    {
        $temp_arr = [];
        foreach ($this->where_arr as $item) {
            $column = trim($item[0]);
            $formated_column = "`" . $column . "`";
            $compare_flag = strtolower(trim($item[1]));
            $compare_value = $item[2];
            if ($compare_flag == "in" && is_array($compare_value)) {
                //TODO  strip_tags
                //TODO 自定义过滤函数,记录违法语句 & ip 
                // $in_query = implode(',', array_fill(0, count($compare_value) - 1, '?'));
                $temp_arr[] = "$formated_column in (" . implode(",", $compare_value) . ")";
            } else {
                $temp_arr[] = "$formated_column $compare_flag :$column";
                $this->query_mapping[":$column"] = $compare_value;
            }
        }
        if ($temp_arr) {
            return " where " . implode(" and ", $temp_arr);
        }
        return "";
    }
    /**
     * Bind parameter array
     * @return [type] [description]
     */
    public function getQueryMapping()
    {
        return $this->query_mapping;
    }
    /**
     * 排序方法
     * @return [type] [description]
     */
    public function buildOrder()
    {
        $order_str = "";
        foreach ($this->order_arr as $value) {
            $formated_column = $this->formateColumn($value[0]);
            $order_type = "$value[1]";
            if ($order_str) {
                $order_str .= ",";
            } else {
                $order_str .= " order by ";
            }
            $order_str .= "$formated_column $order_type";
        }
        return $order_str;
    }
    /**
     * 分组方法
     * @return [type] [description]
     */
    public function buildGroup()
    {
        if (empty($this->group_arr)) {
            return "";
        }

        $group_str = " group by ";
        $formated_arr = [];
        foreach ($this->group_arr as $column) {
            $formated_arr[] = $this->formateColumn($column);
        }
        $group_str .= implode(",", $formated_arr);
        return $group_str;
    }
    /**
     * 格式化列
     * @param  [type] $column [description]
     * @return [type]         [description]
     */
    private function formateColumn($column)
    {
        $column = str_replace("`", "", $column);
        $formated_column = "`$column`";
        return $formated_column;
    }


}