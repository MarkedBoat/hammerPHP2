<?php


namespace cli\redis;

use hammer\cli\CmdBase;
use hammer\sys\Sys;


class CmdTmp extends CmdBase
{

    public static function getClassName()
    {
        return __CLASS__;
    }


    public function init()
    {
        parent::init();
    }

    public function ttl()
    {
        $str = 'captcha_freq_limit:web:202004241732:1111:ddd';
        preg_match_all('/^[\w+:]+\d+/i', $str, $ar);
        preg_match('/^(.*?)\d+/i', $str, $ar);

        // var_export($ar);
        //  die;
        $redis = Sys::app()->redis();
        $csv   = '/home/kinglone/redis_key';
        $file  = Sys::app()->params['cli']['webFileDir'] . '/no_ttl_keys.csv';

        file_put_contents($file, '');
        $f        = fopen($csv, 'r');
        $fileLine = 0;
        $rows     = [];
        $keys     = [];
        while (!feof($f))
        {
            $fileLine++;
            $key  = trim(fgets($f));
            $ttl  = $redis->ttl($key);
            $date = date('H:i:s', time());
            //echo "{$date}:{$fileLine}:{$key}:{$ttl}\n";
            if ($ttl === -1)
            {
                $prefix = $this->getPrefix($key);

                if (in_array($prefix, ['likevideo:', 'sugv6_', 'likemedia:', 'sub_count:', 'sug_'], true))
                {
                    file_put_contents($file, $key . "\n", FILE_APPEND);
                }

                $keys[] = $prefix;
            }
        }
        fclose($f);
        echo "\n";
        $ar = array_count_values($keys);
        // sort($ar);
        var_export($ar);
        echo "\n";

        echo "\n";
        ksort($ar);
        var_export($ar);
        echo "\n";

    }

    public function getPrefix($key)
    {
        if (strstr($key, ':') && strstr($key, '_'))
        {
            echo "fuck {$key}\n";

            preg_match('/^(.*?)\d+/i', $key, $ar);

            if (count($ar) === 2)
            {
                return $ar[1];
            }
            else
            {
                return $key;
            }

        }
        else
        {
            if (strstr($key, ':'))
            {
                $ar = explode(':', $key);
                unset($ar[count($ar) - 1]);
                return join(':', $ar) . ':';
            }
            else if (strstr($key, '_'))
            {
                $ar = explode('_', $key);
                return $ar[0] . '_';

            }
            else
            {
                echo "fuck2 {$key}\n";
                return 'none';
            }
        }

    }

    public function setttl()
    {
        $host    = '127.0.0.1';
        $port    = '6379';
        $psw     = '';
        $file    = '/data/upload/cli_out/no_ttl_keys.csv';
        $outfile = '/data/upload/cli_out/result_ttl.csv';
        echo "{$host}:{$port} {$psw}\n{$file} => {$outfile}\n";
        $redis = new \Redis();
        $redis->connect($host, $port);
        $info = $redis->info();
        var_dump($info);
        $keys = array('total_system_memory_human', 'connected_clients', 'expired_keys', 'db0');
        foreach ($keys as $key)
        {
            echo "{$key}=====>{$info[$key]}\n";
        }
        die;
        if ($psw)
        {
            $redis->auth($psw);
        }
        file_put_contents($outfile, '');
        $f          = fopen($file, 'r');
        $sec_in_day = 3600 * 24;
        $fileLine   = 0;
        while (!feof($f))
        {
            $fileLine++;
            $key  = trim(fgets($f));
            $ttl  = $redis->ttl($key);
            $date = date('H:i:s', time());
            if ($ttl === -1)
            {
                $redis->expire($key, $sec_in_day + rand(0, 90000));
                $ttl2 = $redis->ttl($key);
                file_put_contents($outfile, "{$key},{$ttl},{$ttl2}\n", FILE_APPEND);
                echo "{$date}:{$fileLine}:{$key}:{$ttl}=>>>>{$ttl2}\n";
            }
            else
            {
                echo "{$date}:{$fileLine}:{$key}:{$ttl} ***********************\n";
            }
            usleep(300);
        }
        fclose($f);
    }

    public function t()
    {
        $host    = '10.1.6.6';
        $port    = '6370';
        $psw     = '';
        $file    = '/home/users/yangjl/no_ttl_keys.csv';
        $date    = date('H_i_s', time());
        $outfile = "/home/users/yangjl/result_ttl_{$date}.csv";
        echo "{$host}:{$port} {$psw}\n";
        echo "{$file} => {$outfile}\n";

        $redis = new \Redis();
        $redis->connect($host, $port);
        if ($psw)
        {
            $redis->auth($psw);
        }
        file_put_contents($outfile, '');
        $f          = fopen($file, 'r');
        $sec_in_day = 3600;
        $fileLine   = 0;
        while (!feof($f))
        {
            $fileLine++;
            $key  = trim(fgets($f));
            $ttl  = $redis->ttl($key);
            $date = date('H:i:s', time());

            if ($ttl === -1)
            {
                $redis->expire($key, $sec_in_day + rand(0, 36000));
                $ttl2 = $redis->ttl($key);
                file_put_contents($outfile, "{$key},{$ttl},{$ttl2}\n", FILE_APPEND);
                echo "{$date}:{$fileLine}:{$key}:{$ttl}=>>>>{$ttl2}\n";
            }
            else
            {
                echo "{$date}:{$fileLine}:{$key}:{$ttl} ***********************\n";
            }
            usleep(500);
            if ($fileLine % 1000 === 0)
            {
                $info = $redis->info();
                $keys = array('total_system_memory_human', 'connected_clients', 'expired_keys', 'db0');
                foreach ($keys as $key)
                {
                    echo "{$key}=====>{$info[$key]}\n";
                }
                echo "\n{$outfile}\n";
                sleep(10);
            }
        }
        fclose($f);
    }

    public function starter()
    {
        $files = array();
        $cmd   = 'usr/local/php5.3.11/bin/php redis_worker.php';
        for ($i = 0; $i < 10; $i++)
        {
            $cmdstr  = "{$cmd} {$i} > /home/users/yangjl/log_checked_{$i}.txt";
            $files[] = popen($cmdstr, 'w');
            echo "{$cmdstr}\n";
        }
        foreach ($files as $file)
            pclose($file);

    }

    public function worker()
    {
        $redis_array = array(
            array(
                'host' => '10.1.6.6',
                'port' => 6370
            ),
            array(
                'host' => '10.1.6.7',
                'port' => 6370
            ),
            array(
                'host' => '10.1.6.7',
                'port' => 6380
            ),
            array(
                'host' => '10.1.6.7',
                'port' => 6390
            ),
            array(
                'host' => '10.1.6.8',
                'port' => 6370
            ),
            array(
                'host' => '10.1.6.8',
                'port' => 6380
            ),
            array(
                'host' => '10.1.6.8',
                'port' => 6390
            ),
            array(
                'host' => '10.1.6.9',
                'port' => 6370
            ),
            array(
                'host' => '10.1.6.9',
                'port' => 6380
            ),
            array(
                'host' => '10.1.6.9',
                'port' => 6390
            )
        );
        $keys        = array();
        $file        = '/home/users/yangjl/no_ttl_keys.csv';
        $f           = fopen($file, 'r');
        echo "{$file}\n";
        while (!feof($f))
        {
            $keys[] = trim(fgets($f));
        }
        fclose($f);

        for ($index = 0; $index < 10; $index++)
        {
            $host = $redis_array[$index]['host'];
            $port = $redis_array[$index]['port'];
            echo "{$host}:{$port} \n";
            $redis = new \Redis();
            $redis->connect($host, $port);
            for ($i = 0; $i < 2000; $i++)
            {
                $key  = $keys[rand(100, 50000)];
                $ttl  = $redis->ttl($key);
                $date = date('H:i:s', time());
                echo "{$date}/index:{$index}/key:{$key}/ttl:{$ttl}===>";
                if ($ttl === -1)
                {
                    echo "ERROR";
                }
                else if ($ttl === -2)
                {
                    echo "DEL";
                }
                else
                {
                    echo "OK";
                }
                echo "\n";
                usleep(30000);
            }
        }
    }
}