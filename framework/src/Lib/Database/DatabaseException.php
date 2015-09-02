<?php
namespace Lib\Database;

class DatabaseException extends \Exception
{

    CONST DB_CONNECT_FAIL = 2002;
    CONST DB_CONNECT_TIMEOUT = 30002;
    CONST SQL_ERROR = 30003;
    CONST COMPARISON_NOT_EXIST = 30004;
    CONST TABLE_PK_NOT_EXIST = 30005;

    public function __construct($code = 0, $extend = '') {
        parent::__construct($this->getErrorMessage($code) . "\t$extend", $code);
    }

    public function __toString() {
        return __CLASS__ . ":[{$this->code}]:" . $this->message . '\n';
    }

    public function getErrorMessage($code)
    {
        $mapping = [
            self::DB_CONNECT_FAIL => "数据库连接失败",
            self::DB_CONNECT_TIMEOUT => "数据库连接超时",
            self::SQL_ERROR => "sql语句错误",
            self::COMPARISON_NOT_EXIST => "比较符不存在",
            self::TABLE_PK_NOT_EXIST => "表主键不存在",
        ];
        return $mapping[$code];
    }


}