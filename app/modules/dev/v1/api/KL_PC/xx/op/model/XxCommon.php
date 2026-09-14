<?php

namespace modules\dev\v1\api\KL_PC\xx\op\model;


use models\ext\tool\Printer;

class XxCommon
{

    private Printer $printer;
    public function __construct()
    {

    }

    /**
     * @param Printer $printer
     * @return static
     */
    public function setPrinter(Printer $printer)
    {
        $this->printer = $printer;
        return $this;
    }
    public function getVideos($rootDir)
    {

        $rootDir = '/' . trim($rootDir, '/');

        $cmd = "find '{$rootDir}' -type f|grep -v '.png'|grep -v '.jpg'|grep -v '.gif'";
        //  $cmd = "find '{$dir}' -type f|grep -v '.png'|grep -v '.jpg'|grep -v '.gif'|grep -v FFOutput|grep -v 'VIF_'|grep -v '.json'";
        $videoExts = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'm4v', 'mpg', 'mpeg', '3gp', 'webm', 'ogv', 'ts', 'mts', 'm2ts'];

        exec($cmd, $ar);
        $this->printer->tabEcho($ar);
        $map = [];
        foreach ($ar as $i => $filepath)
        {

            $file_info = pathinfo($filepath);
            // var_dump($file_info);
            $ext = $file_info['extension'];
            if (in_array(strtolower($ext), $videoExts, true))
            {
                $filenameStr    = $file_info['filename'];
                $videoDir       = $file_info['dirname'];
                $albumNameDir   = trim(str_replace($rootDir, '', $videoDir), '/');
                $map[$filepath] = [
                    'ext'          => $ext,
                    'filenameText' => $filenameStr,
                    'dirname'      => $videoDir,
                    'filename'     => $filenameStr,
                    'albumNameDir' => $albumNameDir
                ];
            }


        }
        return $map;


    }


}