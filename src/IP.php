<?php

namespace Kolgaev\SiteStatsLitle;

use Exception;

class IP extends Database
{
    /**
     * Флаг блокировки клиента
     * 
     * @var null|bool
     */
    protected $block = null;

    /**
     * Флаг автоматической блокировки клиента
     * 
     * @var null|bool
     */
    protected $auto_block = null;

    /**
     * Флаг блокировки клиента по имени хоста
     * 
     * @var null|bool
     */
    protected $host_block = null;

    /**
     * Массив ошибок
     * 
     * @var array
     */
    protected $errors = [];

    /**
     * Массив ответа
     * 
     * @var array
     */
    protected $default_response = [
        'block' => null,
        'block_auto' => null,
        'block_host' => null,
        'block_period' => null,
        'block_ip' => null,
        'requests' => 0,
        'visits' => 0,
        'visits_drops' => 0,
        'visits_all' => 0,
        'ip' => null,
    ];

    /**
     * Проверка ip, учет статистики и вывод данных
     * 
     * @return array
     */
    public function check()
    {
        $this->ip = $this->ip();
        $this->host = gethostbyaddr($this->ip);

        $this->block = $this->checkBlock();

        $story = $this->writeStory();

        $response = [
            'block' => $this->block ?? null,
            'block_auto' => $this->auto_block ?? null,
            'block_host' => $this->host_block ?? null,
            'block_period' => $this->period_block ?? null,
            'block_ip' => $this->ip_block ?? null,
            'ip' => $this->ip,
        ];

        if (count($this->errors)) {
            $response['errors'] = $this->errors;
        }

        return array_merge($this->default_response, $story, $response);
    }

    /**
     * Проверка блокировки
     * 
     * @return bool|null
     */
    public function checkBlock()
    {
        if ($block = $this->checkBlockIp())
            return $block;

        $this->host_block = $this->checkBlockHost();

        return $this->host_block !== null ? $this->host_block : $block;
    }

    /**
     * Проверка наличия блокировки IP
     * 
     * @return bool|null
     */
    public function checkBlockIp()
    {
        if ($ip_block = $this->checkBlockIpAddr())
            return $ip_block;

        if ($auto_block = $this->checkAutoBlock())
            return $auto_block;

        if ($period_block = $this->checkBlockPeriod())
            return $period_block;

        return false;
    }

    /**
     * Првоерка блокировки по жесткому совпадению адреса
     * 
     * @return bool|null
     */
    public function checkBlockIpAddr()
    {
        try {
            $result = $this->mysqli->query("SELECT * FROM `blocks` WHERE `host` = '{$this->ip} AND `is_block` = 1'");
            return $this->ip_block = $result->num_rows > 0;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return null;
        }
    }

    /**
     * Проверка блокировки по диапазону
     * 
     * @return bool|null
     */
    public function checkBlockPeriod()
    {
        if (!$long = ip2long($this->ip))
            return null;

        try {
            $result = $this->mysqli->query("SELECT * FROM `blocks` WHERE `is_period` = 1 AND `is_block` = 1 AND `period_start` <= '$long' AND `period_stop` >= '$long'");
            return $this->period_block = $result->num_rows > 0;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return null;
        }
    }

    /**
     * Проверка автоматической блокировки
     * 
     * @return bool|null
     */
    public function checkAutoBlock()
    {
        try {
            $date = date("Y-m-d");
            $result = $this->mysqli->query("SELECT * FROM `automatic_blocks` WHERE `ip` = '{$this->ip}' AND `date` = '{$date}'");
            return $this->auto_block = $result->num_rows > 0;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return null;
        }
    }

    /**
     * Проверка блокировки по хосту
     * 
     * @return bool|null
     */
    public function checkBlockHost()
    {
        if (!$this->host)
            return false;

        try {
            $result = $this->mysqli->query("SELECT * FROM `blocks` WHERE `is_hostname` = 1 AND `is_block` = 1");

            while ($row = $result->fetch_object()) {
                if (strripos($this->host, $row->host) !== false or $this->ip == $row->host) {
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            return null;
        }
    }

    /**
     * Запись истории посещения
     * 
     * @return array
     */
    public function writeStory()
    {
        try {

            $is_blocked = (int) $this->block;
            $page = isset($_SERVER['REQUEST_URI']) ? "'" . $_SERVER['REQUEST_URI'] . "'" : 'null';
            $method = isset($_SERVER['REQUEST_METHOD']) ? "'" . $_SERVER['REQUEST_METHOD'] . "'" : 'null';
            $referer = isset($_SERVER['HTTP_REFERER']) ? "'" . $_SERVER['HTTP_REFERER'] . "'" : 'null';
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? "'" . $_SERVER['HTTP_USER_AGENT'] . "'" : 'null';

            $request_data = addslashes(json_encode([
                'headers' => getallheaders(),
                'post' => isset($_POST) ? $_POST : [],
                'get' => isset($_GET) ? $_GET : [],
            ], JSON_UNESCAPED_UNICODE));

            $query = "INSERT INTO `visits` SET
                `ip` = '{$this->ip}',
                `is_blocked` = $is_blocked,
                `page` = $page,
                `method` = $method,
                `referer` = $referer,
                `user_agent` = $user_agent,
                `request_data` = '$request_data',
                `created_at` = NOW()
            ";

            $this->mysqli->query($query);
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        try {
            $date = date("Y-m-d");
            $visits = $visits_drops = 0;

            $result = $this->mysqli->query("SELECT * FROM `statistics` WHERE `date` = '$date' AND `ip` = '{$this->ip}'");

            if (!($result->num_rows ?? null)) {
                $this->mysqli->query("INSERT INTO `statistics` SET `date` = '$date', `ip` = '{$this->ip}'");
            } else {
                $row = $result->fetch_object();

                $visits = $row->visits;
                $visits_drops = $row->visits_drops;
            }

            if ($this->block)
                $visits_drops++;
            else
                $visits++;

            $this->mysqli->query("UPDATE `statistics` SET `visits` = '$visits', `visits_drops` = '$visits_drops'  WHERE `date` = '$date' AND `ip` = '{$this->ip}'");

            return [
                'visits' => (int) $visits,
                'requests' => (int) ($row->requests ?? 0),
                'visits_drops' => (int) $visits_drops,
                'visits_all' => (int) ($visits + $visits_drops),
            ];
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        return [];
    }

    /**
     * Проверка ip клиента
     * 
     * @param bool $array Вернуть массив адресов
     * @return string|null
     */
    public function ip($array = false)
    {
        $list = [];

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = explode(',', $_SERVER['HTTP_CLIENT_IP']);
            $list = array_merge($list, $ip);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $list = array_merge($list, $ip);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $list[] = $_SERVER['REMOTE_ADDR'];
        }

        $list = array_unique($list);

        return $array ? $list : ($list[0] ?? null);
    }
}
