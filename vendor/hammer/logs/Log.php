<?php

namespace hammer\logs;

class Log implements InterfaceLog
{

    private $logDir;
    private $logDatePathStyle = 'Ym/d/H';
    private $logDatePath      = '';
    private $dataStyle        = 'php';
    private $logFile;


    private $isMemoryLogging = false;
    private $isFileLogging   = true;
    private $isPretty        = false;


    private $tmpSetting = [];

    private $logs = [];


    /**
     * 设置日志目录
     * @param $dir
     * @return static
     */
    public function setDir($dir)
    {

        $this->logDir = $dir;
        //var_dump($dir,  $this->logDir);
        return $this;
    }

    /**
     * 设置 文件路径风格
     * @param string $style 默认  'Ym/d/H'
     * @return static
     */
    public function setlogDatePathStyle($style)
    {
        $this->logDatePathStyle = $style;
        return $this;
    }


    /**
     * 记录到内存中
     * @param bool $state
     * @return static
     */
    public function letMemLogging($state)
    {
        $this->isMemoryLogging = $state;
        return $this;
    }

    public function tmpLetMemLogging()
    {
        $this->tmpSetting['isMemoryLogging'] = true;
        return $this;
    }

    /**
     * @param $state
     * @return static
     */
    public function letFileLogging($state)
    {
        $this->isFileLogging = $state;
        return $this;
    }

    public function tmpLetFileLogging()
    {
        $this->tmpSetting['isFileLogging'] = true;
        return $this;
    }

    public function letLogPretty($state)
    {
        $this->isPretty = $state;
        return $this;
    }

    public function tmpLetLogPretty()
    {
        $this->tmpSetting['isPretty'] = true;
        return $this;
    }

    public function setDataStyle($flag)
    {
        $this->dataStyle = $flag;
        return $this;
    }


    public function tmpSetDataStyle($flag)
    {
        $this->tmpSetting['dataStyle'] = $flag;
        return $this;
    }

    /**
     * 记录日志
     * @param $text
     * @return bool
     */
    public function log($text, $data = [])
    {
        if ($this->isMemoryLogging || isset($this->tmpSetting['isMemoryLogging']))
        {
            $this->logs[] = [$text, $data];
        }
        if ($this->isFileLogging || isset($this->tmpSetting['isFileLogging']))
        {
            $this->logToFile($text, $data);
        }
        $this->tmpSetting = [];
        return true;
    }


    public function logToFile($title, $data = [])
    {
        // var_dump($this->logDir);die;
        $ts       = time();
        $date     = date('Y-m-d H:i:s', $ts);
        $datePath = date($this->logDatePathStyle, $ts);

        if ($datePath !== $this->logDatePath)
        {
            $this->logDatePath = $datePath;
            $filename          = "{$this->logDir}/{$this->logDatePath}.log";
            //   var_dump($this->logDir,$filename);die;
            $fileDir = dirname($filename);
            if (!file_exists($fileDir))
            {
                mkdir($fileDir, 0777, true);
                usleep(100);
            }
            if (!file_exists($fileDir))
            {
                throw new \Exception("创建目录失败  [{$fileDir}]");
            }

            if (!file_exists($filename))
            {
                exec("touch {$filename}; chmod 777 {$filename}");
                file_put_contents($filename, $date . "->create<-\n", FILE_APPEND);
            }
            if (file_exists($filename))
            {
                $this->logFile = $filename;
                //@chmod($this->logFile, 0777);
            }
            else
            {
                throw new \Exception("写入文件失败  {$filename}");
            }
        }

        if (($this->tmpSetting['dataStyle'] ?? $this->dataStyle) === 'php')
        {
            if ($this->tmpSetting['isPretty'] ?? $this->isPretty)
            {
                file_put_contents($this->logFile, "{$date}->{$title}##DATA##_PHP_PRETTY:\n" . print_r($data, true) . "\n", FILE_APPEND);
            }
            else
            {
                file_put_contents($this->logFile, "{$date}->{$title}##DATA##_PHP_LINE:" . str_replace("\n", ' ', print_r($data, true)) . "\n", FILE_APPEND);
            }
        }
        else
        {
            if ($this->tmpSetting['isPretty'] ?? $this->isPretty)
            {
                file_put_contents($this->logFile, "{$date}->{$title}##DATA##_JSON_PRETTY:\n" . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
            }
            else
            {
                file_put_contents($this->logFile, "{$date}->{$title}##DATA##_JSON_LINE:" . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);

            }
        }
    }

    public function getMemLogs()
    {
        return $this->logs;
    }

}