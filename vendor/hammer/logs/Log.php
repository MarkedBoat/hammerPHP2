<?php

namespace hammer\logs;

class Log
{
    private $recordToMemory = false;
    private $recordToFile   = true;

    private $logs = [];
    private $logDir;
    private $logFile;
    private $logFilePath;

    public function setDir($dir)
    {
        $this->logDir = $dir;
        return $this;
    }


    /**
     * 记录到内存中
     * @param $state
     * @return $this
     */
    public function setMemLog($state)
    {
        $this->recordToMemory = $state;
        return $this;
    }

    public function setFileLog($state)
    {
        $this->recordToFile = $state;
        return $this;
    }

    /**
     * 记录日志
     * @param $text
     * @return void
     */
    public function log($text, $data = [])
    {
        if ($this->recordToMemory)
        {
            $this->logs[]         = [$text, $data];
            $this->recordToMemory = false;
        }
        if ($this->recordToFile)
        {
            $this->logToFile($text, $data);
        }
        else
        {
            $this->recordToFile = true;
        }
    }


    public function logToFile($title, $data = [])
    {
        $date = date('Y-m-d H:i:s', time());

        if (empty($this->log_filename))
        {
            list($ym, $d, $h) = explode('/', date('Ym/d/H'));

            $filename = "{$this->logDir}/{$ym}/{$d}/{$h}.log";

            $fileDir = "{$this->logDir}/{$ym}/{$d}";
            if (!file_exists($fileDir))
            {
                mkdir($fileDir, 0777, true);
                usleep(10);
            }
            if (!file_exists($fileDir))
            {
                throw new \Exception("创建目录失败  [{$fileDir}]");
            }

            if (!file_exists($filename))
            {
                file_put_contents($filename, $date . "->create<-\n", FILE_APPEND);
            }
            if (file_exists($filename))
            {
                $this->log_filename = $filename;
            }
            else
            {
                throw new \Exception("写入文件失败  {$filename}");
            }
        }

        file_put_contents($this->log_filename, "{$date}->{$title}\n" . print_r($data,true) . "\n<-\n", FILE_APPEND);

    }

    public function getMemLogs()
    {
        return $this->logs;
    }

}