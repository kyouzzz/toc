<?php
namespace Lib\Database;

class PDOManager
{

    public static $pdo_list = [];

    public static $instance = null;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new PDOManager();
        }
        return self::$instance;
    }

    public function getPDO($host, $port, $user, $password, $dbname)
    {
        $unique_key = $this->getUniqueKey($host, $port, $dbname);

        if (!isset(self::$pdo_list[$unique_key])) {
            try {
                // cache pdo
                $new_pdo = new \PDO(
                    "mysql:host=$host;dbname=$dbname;port=$port",
                    $user,
                    $password);
                $new_pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
                self::$pdo_list[$unique_key] = $new_pdo;
            } catch (\PDOException $e) {
                $code = $e->getCode();
                throw new DatabaseException($code, $unique_key);
            }
        }
        return self::$pdo_list[$unique_key];
    }

    public function getUniqueKey($host, $port, $dbname)
    {
        return "$dbname@$host:$port";
    }


}
