<?php

namespace modules\dev\v1\dao\video;

use hammer\db\DbModel;
use models\auto\kl\FfmpegTasksAR;
use models\common\Def;
use hammer\sys\Sys;
use modules\dev\v1\api\KL_PC\xx\op\model\LsCmdFileinfo;


class FfmpegTask extends FfmpegTasksAR
{
    public $srcFileInfo = [];
    public $dstFileInfo = [];

    /**
     * @var LsCmdFileinfo
     */
    public $srcLsCmdFileinfo;
    /**
     * @var LsCmdFileinfo
     */
    public $preLsCmdFileinfo;

    /**
     * @var LsCmdFileinfo
     */
    public $tmpLsCmdFileinfo;

    /**
     * @var LsCmdFileinfo
     */
    public $dstLsCmdFileinfo;

    public function initData()
    {

    }

    public function logError($err_msg)
    {
        $tmp = $this->task_info;
        if (!isset($tmp['error']))
        {
            $tmp['error'] = [];
        }
        $date            = date('Y-m-d H:i:s');
        $tmp['error'][]  = "{$date} {$err_msg}";
        $this->task_info = $tmp;
        $this->is_err    = Def::staYes;
        $this->save();
    }

    public function getSrcVideoInfo($srcRootDir)
    {
        if (empty($this->srcLsCmdFileinfo))
        {
            $this->srcLsCmdFileinfo = new LsCmdFileinfo();
        }
        $this->srcLsCmdFileinfo->findTaskedFile($srcRootDir, $this->id36);

        if ($this->srcLsCmdFileinfo->filesize > 10000)
        {
            $this->srcFileInfo['filesize'] = $this->srcLsCmdFileinfo->filesize;
            $this->srcFileInfo['filename'] = $this->srcLsCmdFileinfo->fullFilename;
        }
        else
        {
            $this->srcFileInfo['filesize'] = 0;
            $this->srcFileInfo['filename'] = '';
        }
        return $this->srcFileInfo;
    }

    public function getDstVideoInfo($rootDir)
    {
        $relativeResFilename = $this->dst_video_info['src'] ?? '';

        if (strlen($this->album_dir) > 0)
        {
            $albumDirPath = "/{$this->album_dir}/";
        }
        else
        {
            $albumDirPath = "/";
        }

        if (intval($this->is_pre) === Def::staYes)
        {
            $dstVideoFilename = "{$rootDir}{$relativeResFilename}";
            $dstVideoFilename = "{$rootDir}{$albumDirPath}{$this->video_title} ^{$this->id36}^.mp4";
        }
        else
        {
            $dstVideoFilename = "{$rootDir}{$relativeResFilename}";
        }

        // $dstVideoFilename                 = "{$rootDir}{$relativeResFilename}";
        $this->dstFileInfo['dstFilename'] = $dstVideoFilename;
        if (file_exists($dstVideoFilename))
        {
            $this->dstFileInfo['filesize'] = filesize($dstVideoFilename);
            $this->dstFileInfo['filename'] = $dstVideoFilename;
        }
        else
        {
            $this->dstFileInfo['filesize'] = 0;
            $this->dstFileInfo['filename'] = '';
        }
        return $this->dstFileInfo;
    }

    public function getDstVideoInfoForce($rootDir)
    {
        if (strlen($this->album_dir) > 0)
        {
            $albumDirPath = "/{$this->album_dir}/";
        }
        else
        {
            $albumDirPath = "/";
        }
        $videoNameFullStr = strtolower("{$albumDirPath}/{$this->video_title}");


        $dstFinnalRelativeFilename = "/FFOutput_res{$albumDirPath}{$this->video_title} ^{$this->id36}^.mp4";
        $dstFinnalAbsoluteFilename = "{$rootDir}{$dstFinnalRelativeFilename}";

        $dstVideoFilename = $dstFinnalAbsoluteFilename;

        $this->dstFileInfo['dstFilename'] = $dstVideoFilename;


        if (file_exists($dstVideoFilename))
        {
            $this->dstFileInfo['filesize'] = filesize($dstVideoFilename);
            $this->dstFileInfo['filename'] = $dstVideoFilename;
        }
        else
        {
            $this->dstFileInfo['filesize'] = 0;
            $this->dstFileInfo['filename'] = '';
        }
        return $this->dstFileInfo;
    }


    /**
     * @param $targetDir
     * @param array $skips
     * @return array|LsCmdFileinfo[]
     */
    public function getId36KV($targetDir, array $skips = [])
    {
        // 1. 检查目录是否存在
        if (!is_dir($targetDir))
        {
            die("错误：目标目录不存在：$targetDir\n");
        }

        // 2. 执行 ls -lR（不加任何管道过滤，保证目录标题行完整）
        $cmd = "ls -lR " . escapeshellarg($targetDir);
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0)
        {
            die("执行命令失败，返回码：$returnCode\n");
        }

        if (empty($output))
        {
            die("命令没有输出，请检查目录权限或路径是否正确。\n");
        }
        $rootDir       = dirname($targetDir);
        $video_info_kv = [];
        $currentDir    = ''; // 当前目录标识，从目录标题行解析

        // 文件行正则（兼容 ACL/SELinux 标记）
        $filePattern = '/^([-d])([rwx-]{9})([+.]?)\s+\d+\s+\S+\s+\S+\s+(\d+)\s+(\w+)\s+(\d+)\s+(\d+:\d+|\d{4})\s+(.+)$/';

        foreach ($output as $line)
        {
            $line = rtrim($line);
            if ($line === '')
                continue;

            // --- 检测目录标题行（修正：匹配整行直到冒号，支持空格）---
            if (preg_match('/^(.+):$/', $line, $dirMatch))
            {
                $currentDir = $dirMatch[1];
                continue;
            }

            // 跳过 total 行
            if (strpos($line, 'total') === 0)
            {
                continue;
            }

            // 尝试匹配文件信息行
            if (!preg_match($filePattern, $line, $matches))
            {
                continue;
            }

            // 只处理普通文件
            if ($matches[1] !== '-')
            {
                continue;
            }

            $size       = (int)$matches[4];
            $month      = $matches[5];
            $day        = $matches[6];
            $timeOrYear = $matches[7];
            $filename   = $matches[8];

            // ---- 在 PHP 端根据 $skips 过滤文件（不影响目录行） ----
            $shouldSkip = false;
            $skipsp[]   = '_skip_';
            foreach ($skips as $skip)
            {
                if (strpos($filename, $skip) !== false)
                {
                    $shouldSkip = true;
                    break;
                }
            }
            if ($shouldSkip)
            {
                continue;
            }

            // ---- 构建绝对路径 ----
            if ($currentDir === '')
            {
                // fallback（正常情况下不会发生）
                $fullPath = $targetDir . '/' . $filename;
            }
            else
            {
                // $currentDir 可能是绝对路径或以 ./ 开头的相对路径
                if (strpos($currentDir, '/') === 0)
                {
                    // 绝对路径（如 /mnt/.../some_dir）
                    $fullPath = $currentDir . '/' . $filename;
                }
                else
                {
                    // 相对路径（如 "./subdir" 或 "subdir"），基于 targetDir 拼接
                    $relative = ltrim($currentDir, './');
                    $fullPath = $targetDir . '/' . $relative . '/' . $filename;
                }
            }
            // 规范化路径（消除多余的 /./ 或 //）
            $fullPath = preg_replace('#/+#', '/', $fullPath);

            // ---- 计算相对路径（相对于 $targetDir） ----
            if (strpos($fullPath, $targetDir) === 0)
            {
                $relativePath = substr($fullPath, strlen($targetDir) + 1);
            }
            else
            {
                $relativePath = $filename; // fallback
            }

            // ---- 解析时间 ----
            $timeString = "$month $day $timeOrYear";
            $timestamp  = strtotime($timeString);
            if ($timestamp === false)
            {
                $timestamp = time();
            }

            // ---- 从文件名中提取键（仅匹配 ^字母数字^） ----
            $key = null;
            if (preg_match('/(\^[A-Za-z0-9]+\^)/', $filename, $keyMatch))
            {
                $key = trim($keyMatch[1], '^');
            }

            $file_info = pathinfo($fullPath, PATHINFO_DIRNAME | PATHINFO_BASENAME | PATHINFO_EXTENSION | PATHINFO_FILENAME);

            // ---- 构建信息数组 ----
            $obj = new LsCmdFileinfo();

            $obj->srcRootDir   = $targetDir;
            $obj->rootDir      = $rootDir;
            $obj->filesize     = $size;
            $obj->timestamp    = $timestamp;
            $obj->date         = date('Y-m-d H:i:s', $timestamp);
            $obj->fullFilename = $fullPath;
            $obj->relativePath = $relativePath;
            $obj->ext          = $file_info['extension'];
            $obj->title        = $file_info['filename'];
            $obj->dirname      = $file_info['dirname'];
            $obj->albumNameDir = trim(str_replace($targetDir, '', $file_info['dirname']), '/');

            // ---- 存储 ----
            if ($key !== null)
            {
                $obj->id36           = $key;
                $obj->id             = base_convert($key, 36, 10);
                $video_info_kv[$key] = $obj;
            }

            // 未匹配键则忽略（可按需调整）
        }

        return $video_info_kv;
    }

    public function getTmpTitle()
    {
        return "{$this->id}^{$this->id36}^";
    }

    public function getLsCmdFileinfos($rootDir)
    {
        $srcRootDir = "{$rootDir}/src";
        $preRootDir = "{$rootDir}/FFoutput_pre";
        $tmpRootDir = "{$rootDir}/FFoutput_tmp";
        $dstRootDir = "{$rootDir}/FFoutput_res";

        $this->srcLsCmdFileinfo = (new LsCmdFileinfo())->findTaskedFile($srcRootDir, $this->id36);
        $this->preLsCmdFileinfo = (new LsCmdFileinfo())->findTaskedFile($preRootDir, $this->id36);
        $this->tmpLsCmdFileinfo = (new LsCmdFileinfo())->findTaskedFile($tmpRootDir, $this->id36);
        $this->dstLsCmdFileinfo = (new LsCmdFileinfo())->findTaskedFile($dstRootDir, $this->id36);

    }

    public function getLsCmdFileinfosBySrc(LsCmdFileinfo $srcLsCmdFileinfo)
    {
        //var_export($srcLsCmdFileinfo);
        $this->srcLsCmdFileinfo = $srcLsCmdFileinfo;

        $preRootDir = "{$srcLsCmdFileinfo->rootDir}/FFoutput_pre";
        $tmpRootDir = "{$srcLsCmdFileinfo->rootDir}/FFoutput_tmp";
        $dstRootDir = "{$srcLsCmdFileinfo->rootDir}/FFoutput_res";
        $tmpTitle   = $this->getTmpTitle();
        $tmp        = [
            'pre' => ['k' => 'preLsCmdFileinfo', 'srcDir' => $preRootDir, 'title' => $tmpTitle, 'album' => '/'],
            'tmp' => ['k' => 'tmpLsCmdFileinfo', 'srcDir' => $tmpRootDir, 'title' => $tmpTitle, 'album' => '/'],
            'dst' => ['k' => 'dstLsCmdFileinfo', 'srcDir' => $dstRootDir, 'title' => "{$srcLsCmdFileinfo->title}", 'album' => "/{$srcLsCmdFileinfo->albumNameDir}/"],
        ];
        foreach ($tmp as $cfg)
        {
            $fullPath  =str_replace('//', '/',"{$cfg['srcDir']}{$cfg['album']}{$cfg['title']}.mp4");
            $file_info = pathinfo($fullPath, PATHINFO_DIRNAME | PATHINFO_BASENAME | PATHINFO_EXTENSION | PATHINFO_FILENAME);

            $obj = new LsCmdFileinfo();
            if (file_exists($fullPath))
            {
                $obj->filesize = filesize($fullPath);
            }
            $obj->srcRootDir   = $cfg['srcDir'];
            $obj->rootDir      = $this->srcLsCmdFileinfo->rootDir;
            $obj->fullFilename = $fullPath;
            $obj->ext          = $file_info['extension'];
            $obj->title        = $file_info['filename'];
            $obj->dirname      = $file_info['dirname'];
            $obj->albumNameDir = trim($cfg['album'], '/');

            $obj->id   = $this->id;
            $obj->id36 = $this->id36;

            $k        = $cfg['k'];
            $this->$k = $obj;
        }

    }

}