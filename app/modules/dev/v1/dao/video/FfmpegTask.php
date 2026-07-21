<?php

namespace modules\dev\v1\dao\video;

use hammer\db\DbModel;
use models\common\Def;
use hammer\sys\Sys;

/**
 * @property int id
 * @property string src_video_info
 * @property string dst_video_info
 * @property string task_info 任务目标
 * @property string root_dir
 * @property string album_dir
 * @property string video_basename
 * @property string video_ext
 * @property int is_err 出现错误
 * @property int is_pre 是否是预先转化，有些格式需要预先转化
 * @property int is_src_exist
 * @property int is_auto_del_src 是否自动删除src
 * @property int run_lc 运行 锁
 * @property int is_ok
 * @property int pre_id
 * @property string create_date
 * @property string update_date
 */
class FfmpegTask extends DbModel
{

    const tableName = 'ffmpeg_tasks';
    const fields    = '`id`,`src_video_info`,`dst_video_info`,`task_info`,`root_dir`,`album_dir`,`video_basename`,`video_ext`,`is_err`,`is_pre`,`is_src_exist`,`is_auto_del_src`,`run_lc`,`is_ok`,`pre_id`,`create_date`,`update_date`';

    const staYes = 1;
    const staNot = 2;

    public $srcFileInfo = [];
    public $dstFileInfo = [];

    public function getTableName()
    {
        return self::tableName;
    }

    public function getConnection()
    {
        return Sys::app()->db('kl');
    }

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

    public function getSrcVideoInfo($rootDir)
    {
        if (strlen($this->album_dir) > 0)
        {
            $albumDirPath = "/{$this->album_dir}/";
        }
        else
        {
            $albumDirPath = "/";
        }
        $src_video_path = "{$rootDir}{$albumDirPath}{$this->video_basename}.{$this->video_ext}";
        if (file_exists($src_video_path))
        {
            $this->srcFileInfo['filesize'] = filesize($src_video_path);
            $this->srcFileInfo['filename'] = $src_video_path;
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
        $relativeResFilename              = $this->dst_video_info['src'] ?? '';

        if (strlen($this->album_dir) > 0)
        {
            $albumDirPath = "/{$this->album_dir}/";
        }
        else
        {
            $albumDirPath = "/";
        }

        if(intval($this->is_pre)===Def::staYes){
            $dstVideoFilename = "{$rootDir}{$relativeResFilename}";
            $dstVideoFilename = "{$rootDir}{$albumDirPath}{$this->video_basename} kl{$this->id}.mp4";
        }else{
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
        $videoNameFullStr = strtolower("{$albumDirPath}/{$this->video_basename}");


        $dstFinnalRelativeFilename = "/FFOutput_res{$albumDirPath}{$this->video_basename} kl{$this->id}.mp4";
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

}