<?php

namespace modules\dev\v1\api\KL_PC\xx\op\model;

class LsCmdFileinfo
{

    /**
     * @var string src根目录
     */
    public string $srcRootDir = '';

    /**
     * @var string 根目录,src根目录的上一级
     */
    public string $rootDir = '';


    public int    $filesize  = 0;
    public int    $timestamp = 0;
    public string $date      = '';
    /**
     * @var string 文件全路径
     */
    public string $fullFilename = '';
    /**
     * @var string 相对路径
     */
    public string $relativePath = '';
    /**
     * @var string 文件扩展名,未统一大小写
     */
    public string $ext = '';

    /**
     * @var string 文件名（不包括扩展名）
     */
    public string $title = '';
    /**
     * @var string 目录名（绝对路径）
     */
    public string $dirname = '';
    /**
     * @var string 专辑名目录（ 其实就是  相对路径的目录，去掉了两端 / ）
     */
    public string $albumNameDir = '';
    /**
     * @var string ID36
     */
    public string $id36 = '';
    /**
     * @var int ID
     */
    public int $id = 0;


    /**
     * @param $targetDir
     * @param $id36
     * @return LsCmdFileinfo
     */
    public function findTaskedFile($targetDir, $id36)
    {
        // $result = exec("find '{$dir}' -type f -exec grep -l '^{$id36}^' {} \; | head -1");
        // $result = exec("ls -l  '{$dir}'   ",$ar);
        // $cmd = "find '{$targetDir}' -type f -exec grep -l '\^{$id36}\^' {} \; | head -1";
        $cmd = "find '{$targetDir}' -type f | grep '\^{$id36}\^' | head -1 ";

        //  echo "\n{$cmd}\n";
        $fullPath = exec($cmd, $ar);

        // var_dump($dir);
        //var_dump($ar);
        if ($fullPath)
        {


            $file_info = pathinfo($fullPath, PATHINFO_ALL);

            $this->srcRootDir = $targetDir;
            // $this->rootDir      = $rootDir;
            $this->filesize = filesize($fullPath);
            // $this->timestamp    = $timestamp;
            // $this->date         = date('Y-m-d H:i:s', $timestamp);
            $this->fullFilename = $fullPath;
            $this->relativePath = trim(str_replace($targetDir, '', $fullPath));
            $this->ext          = $file_info['extension'];
            $this->title        = $file_info['filename'];
            $this->dirname      = $file_info['dirname'];
            $this->albumNameDir = trim(str_replace($targetDir, '', $file_info['dirname']), '/');

            $this->id36 = $id36;
            $this->id   = base_convert($id36, 36, 10);


            return $this;
        }
        return $this;
    }


}