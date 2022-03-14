<?php

namespace Kolgaev\SiteStatsLite;

use mysqli;

class Database
{
    /**
     * @var \mysqli
     */
    protected $mysql;

    /**
     * Инициализация объекта
     * 
     * @return void
     */
    public function __construct()
    {
        $localhost = getenv('DB_HOST') ?: 'localhost';
        $root = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASS') ?: 'password';
        $database = getenv('DB_NAME') ?: 'database';
        $port = getenv('DB_PORT') ?: '3306';

        $this->mysqli = new mysqli($localhost, $root, $password, $database, $port);
    }
}
