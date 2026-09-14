<?php


namespace cli\homepc;

use hammer\cli\CmdBase;
use models\common\Def;
use hammer\param\Param;
use hammer\sys\Sys;
use models\ext\tool\CLIFormatter;
use models\ext\tool\CLIStrFormatter;
use models\ext\tool\Printer;
use models\ext\VicWord\Lib\VicWord;
use modules\dev\v1\dao\video\FfmpegTask;

ini_set('memory_limit', '3072M');


class CmdXx2 extends CmdBase
{

    private $file_name_ver = '1';

    private $rootDir      = '';
    private $isPreivew    = true;
    private $rubbishWords = [];
    private $fixNameMap   = [];

    /**
     * @var Printer
     */
    protected $printer = false;

    public function init()
    {
        parent::init();
        $this->printer = new  Printer();
    }


    /**
     * cd /mnt/f/doc/bfcode/porter/app/; ./app  homepc/file formatName2 --env=dev0  --root_dir='/mnt/f/tmp2' --preview=no1
     * cd /mnt/f/doc/bfcode/porter/app/; ./app  homepc/file formatName2 --env=dev0  --root_dir='/mnt/f/tmp2/tmp' --preview=no
     * @throws \Exception
     */
    public function formatName2()
    {
        Sys::app()->initPrinter();
        Sys::app()->getPrinter()->setBaseTabNumber(0)->newTabEcho('init', '初始化根目录');
        $this->rootDir = $this->inputBox->tryGetString('root_dir') ? $this->inputBox->tryGetString('root_dir') : '/mnt/f/tmp2';
        if (!is_dir($this->rootDir))
        {
            die("\nnot dir:{$this->rootDir}\n");
        }
        $this->isPreivew = !($this->inputBox->tryGetString('preview') === 'no');

        VersionControl::init();


        Sys::app()->getPrinter()->newTabEcho('pre_fix_all_files', '预先清理全部文件');
        $findAllFilesCmd = "find '{$this->rootDir}'";
        $filenames       = [];
        exec($findAllFilesCmd, $filenames);

        $rubbishWords     = array_diff(VersionControl::$rubbishWords, ['/', '\/']);
        $rubbishWordEmpty = array_fill(0, count($rubbishWords), ' ');

        var_export($rubbishWords);

        foreach ($filenames as $filename)
        {
            $newFullFilename = str_replace($rubbishWords, $rubbishWordEmpty, $filename);
            $newFullFilename = preg_replace('/\/\s+/isU', '/', $newFullFilename);
            $newFullFilename = preg_replace('/\s+\./isU', '.', $newFullFilename);
            // $newFullFilename = preg_replace('/(.\w+)$/isU', 'xxx.$1', $newFullFilename);

            if ($newFullFilename != $filename)
            {
                Sys::app()->getPrinter()->tabEcho("diff\n{$filename}\n{$newFullFilename}");
                $dirname = dirname($newFullFilename);
                if (!file_exists($dirname))
                {
                    mkdir($dirname, 0777, true);
                }
                if (file_exists($newFullFilename))
                {
                    $unique          = base_convert(str_replace('.', '', microtime(true)), 10, 32);
                    $newFullFilename = preg_replace('/(.\w+)$/isU', "{$unique}$1", $newFullFilename);
                    Sys::app()->getPrinter()->tabEcho("rename\n{$newFullFilename}");
                }
                rename($filename, $newFullFilename);

            }
            else
            {
                Sys::app()->getPrinter()->tabEcho("ok\n{$filename}");
            }
        }
        Sys::app()->getPrinter()->endTabEcho('pre_fix_all_files', '预先清理全部文件');


        $rootScanedDir         = new ScanedDir(false, $this->rootDir);
        $rootScanedDir->isRoot = true;
        $retry                 = $this->inputBox->tryGetString('retry') === 'yes';

        //                Sys::app()->getPrinter()->newTabEcho('1.2', '1.2');
        //                Sys::app()->getPrinter()->newTabEcho('1.2.3', '1.2.3');
        //                Sys::app()->getPrinter()->newTabEcho('1.2.3.4', '1.2.3.4');
        //                Sys::app()->getPrinter()->endTabEcho('1.2.3.4', '1.2.3.4');
        //                Sys::app()->getPrinter()->endTabEcho('1.2.3', '1.2.3');
        //                Sys::app()->getPrinter()->endTabEcho('1.2', '1.2');


        Sys::app()->getPrinter()->endTabEcho('init', '初始化 根目录 ok');


        Sys::app()->getPrinter()->newTabEcho('scan_lev1_files', '扫描一级文件');

        foreach ($rootScanedDir->scanedFiles as $deep1ScanFile)
        {

            //   echo "1级子文件 {$deep1ScanFile->filePath}\n";
            Sys::app()->getPrinter()->tabEcho("1级子文件 {$deep1ScanFile->filePath}\n");

            if ($this->dealFile($deep1ScanFile))
            {
                if ($deep1ScanFile->isVideo())
                {
                    Sys::app()->getPrinter()->newTabEcho('scan_lev1_files.file.isVideo', "\n属于视频，准备处理");

                    Sys::app()->getPrinter()->newTabEcho('scan_lev1_files.file.getFormatedName', "获取规范名称");
                    $versioned_filename_name = $deep1ScanFile->formatVideoFile();
                    Sys::app()->getPrinter()->endTabEcho('scan_lev1_files.file.getFormatedName', "获取规范名称");

                    $repeat_checked_filename = $versioned_filename_name;
                    $dst_filename            = "{$this->rootDir}/{$versioned_filename_name}.{$deep1ScanFile->ext}";
                    if ($dst_filename !== $deep1ScanFile->filePath && is_file("{$this->rootDir}/{$versioned_filename_name}.{$deep1ScanFile->ext}"))
                    {
                        Sys::app()->getPrinter()->tabEcho("有重复,加随机\n");
                        $rand                    = rand(1, 10000);
                        $repeat_checked_filename = "{$versioned_filename_name}_{$rand}.{$deep1ScanFile->ext}";
                    }

                    $new_full_filename = "{$this->rootDir}/{$repeat_checked_filename}.{$deep1ScanFile->ext}";
                    Sys::app()->getPrinter()->tabEcho("only_video move_file:\n{$deep1ScanFile->filePath} -> \n{$new_full_filename}\n");
                    if ($this->isPreivew)
                    {
                        Sys::app()->getPrinter()->tabEcho("注意是预览，未修改\n");
                    }
                    else
                    {
                        rename($deep1ScanFile->filePath, $new_full_filename);
                    }
                    Sys::app()->getPrinter()->endTabEcho('scan_lev1_files.file.isVideo', "\n属于视频，处理完毕\n\n\n\n\n");

                }
            }
        }
        Sys::app()->getPrinter()->endTabEcho('scan_lev1_files', '扫描一级文件');

        Sys::app()->getPrinter()->newTabEcho('scan_lev1_dirs', '扫描一级目录');

        foreach ($rootScanedDir->scanedDirs as $deep1ScanedDir)
        {
            if ($deep1ScanedDir->isVersionedName())
            {
                //其实加不加都无所谓，主要为了打个提醒
                continue;
            }
            Sys::app()->getPrinter()->newTabEcho('lev_1_dir.forEach', "1级子目录 {$deep1ScanedDir->fullPath}");


            Sys::app()->getPrinter()->newTabEcho('lev_1_dir.lev_2_files.forEach', " 2级子文件 开始遍历");

            $videos_cnt = 0;
            $imgs_cnt   = 0;
            foreach ($deep1ScanedDir->scanedFiles as $deep2ScanFile)
            {
                //echo "1级子目录 2级子文件 {$deep2ScanFile->filePath}\n";
                if ($deep2ScanFile->isReadableFile())
                {
                    if ($deep2ScanFile->isVideo())
                    {
                        $videos_cnt = $videos_cnt + 1;
                    }
                    else if ($deep2ScanFile->isImg())
                    {
                        $imgs_cnt = $imgs_cnt + 1;
                    }
                }
            }
            Sys::app()->getPrinter()->endTabEcho('lev_1_dir.lev_2_files.forEach', " 2级子文件 结束遍历");

            // Sys::app()->getPrinter()->tabEcho("1级子目录 {$deep1ScanedDir->fullPath}\n");

            Sys::app()->getPrinter()->newTabEcho('lev_1_dir.lev_2_dirs.forEach', " 2级子目录 开始遍历");

            foreach ($deep1ScanedDir->scanedDirs as $deep2ScanedDir)
            {
                echo "2级子目录 {$deep2ScanedDir->fullPath}\n";

                Sys::app()->getPrinter()->newTabEcho('scan_lev1_files.file.getFormatedName', "获取规范名称");
                $formatedName = $deep2ScanedDir->getFormatedName();
                Sys::app()->getPrinter()->endTabEcho('scan_lev1_files.file.getFormatedName', "获取规范名称");

                $repeat_checked_filename = "{$this->rootDir}/{$formatedName}";
                if (is_dir("{$this->rootDir}/{$formatedName}"))
                {
                    Sys::app()->getPrinter()->tabEcho("有重复\n");
                    $rand                    = rand(1, 10000);
                    $repeat_checked_filename = "{$repeat_checked_filename}_{$rand}";
                }

                $new_full_filename = "{$repeat_checked_filename}";
                Sys::app()->getPrinter()->tabEcho("move_dir :{$deep2ScanedDir->fullPath} -> {$new_full_filename}\n");
                if ($this->isPreivew)
                {
                    Sys::app()->getPrinter()->tabEcho("注意是预览，未修改\n");
                }
                else
                {
                    rename($deep2ScanedDir->fullPath, $new_full_filename);
                }

            }
            Sys::app()->getPrinter()->endTabEcho('lev_1_dir.lev_2_dirs.forEach', " 2级子目录 结束遍历");

            Sys::app()->getPrinter()->endTabEcho('lev_1_dir.forEach', "1级子目录 遍历结束 dirs:[{$deep1ScanedDir->dirsCnt}] files:[{$deep1ScanedDir->filesCnt}]  videos:[$videos_cnt] imgs:[$imgs_cnt]\n");

            if ($deep1ScanedDir->dirsCnt === 0)
            {
                Sys::app()->getPrinter()->tabEcho("无子目录");

                if ($deep1ScanedDir->filesCnt === 0)
                {
                    Sys::app()->getPrinter()->tabEcho("{$deep1ScanedDir->fullPath} 下未统计到文件，无文件 移除 ");
                    rmdir($deep1ScanedDir->fullPath);
                }
                else if ($deep1ScanedDir->filesCnt === 1)
                {
                    Sys::app()->getPrinter()->tabEcho("名下一个文件");

                    if ($videos_cnt === 1)
                    {
                        $deep1ScanedFile = $deep1ScanedDir->scanedFiles[0];
                        Sys::app()->getPrinter()->tabEcho("子文件 为纯一个视频 \nold:{$deep1ScanedFile->filePath} ");

                        if ($deep1ScanedFile->isVideo())
                        {
                            Sys::app()->getPrinter()->newTabEcho('scan_lev2_files.file.isVideo', "\n属于视频，准备处理");

                            Sys::app()->getPrinter()->newTabEcho('scan_lev2_files.file.getFormatedName', "获取规范名称");
                            $versioned_filename_name = $deep1ScanedFile->formatVideoFile();
                            Sys::app()->getPrinter()->endTabEcho('scan_lev2_files.file.getFormatedName', "获取规范名称");

                            $repeat_checked_filename = $versioned_filename_name;
                            if (is_file("{$this->rootDir}/{$versioned_filename_name}.{$deep1ScanedFile->ext}"))
                            {
                                Sys::app()->getPrinter()->tabEcho("有重复\n");
                                $rand                    = rand(1, 10000);
                                $repeat_checked_filename = "{$versioned_filename_name}_{$rand}.{$deep1ScanedFile->ext}";
                            }

                            $new_full_filename = "{$this->rootDir}/{$repeat_checked_filename}.{$deep1ScanedFile->ext}";
                            Sys::app()->getPrinter()->tabEcho("only_video move_file:{$deep1ScanedFile->filePath} -> {$new_full_filename}\n");
                            if ($this->isPreivew)
                            {
                                Sys::app()->getPrinter()->tabEcho("注意是预览，未修改\n");
                            }
                            else
                            {
                                rename($deep1ScanedFile->filePath, $new_full_filename);
                            }
                            Sys::app()->getPrinter()->endTabEcho('scan_lev2_files.file.isVideo', "\n属于视频，处理完毕\n\n\n\n\n");

                        }

                    }
                }
                else
                {
                    Sys::app()->getPrinter()->tabEcho("名下多个文件");

                    $versioned_dir_name = trim($deep1ScanedDir->formatNameWord('dir', $deep1ScanedDir->onlyName, $deep1ScanedDir->onlyName));
                    //   $versioned_dir_name = $deep1ScanedDir->addNameVersion($versioned_dir_name);
                    // $versioned_dir_name = $deep1ScanedDir->addNameVersion($deep1ScanedDir->onlyName);
                    if ($videos_cnt > 1 && $deep1ScanedDir->filesCnt === $videos_cnt)
                    {
                        Sys::app()->getPrinter()->tabEcho("子文件都是【视频】，应该加版本了 \nold:{$deep1ScanedDir->fullPath} \nnew:{$rootScanedDir->fullPath}/{$versioned_dir_name}\n");

                        if ($this->isPreivew)
                        {
                            Sys::app()->getPrinter()->tabEcho("注意是预览，未修改\n");
                        }
                        else
                        {
                            rename($deep1ScanedDir->fullPath, "{$rootScanedDir->fullPath}/{$versioned_dir_name}");
                        }

                    }

                    if ($imgs_cnt > 1 && $deep1ScanedDir->filesCnt === $imgs_cnt)
                    {
                        Sys::app()->getPrinter()->tabEcho("子文件都是【图片】，应该加版本了 \nold:{$deep1ScanedDir->fullPath} \nnew:{$rootScanedDir->fullPath}/{$versioned_dir_name}\n");
                        if ($this->isPreivew)
                        {
                            Sys::app()->getPrinter()->tabEcho("注意是预览，未修改\n");
                        }
                        else
                        {
                            rename($deep1ScanedDir->fullPath, "{$rootScanedDir->fullPath}/{$versioned_dir_name}");
                        }
                    }

                }
            }


        }
        Sys::app()->getPrinter()->endTabEcho('scan_lev1_dirs', '扫描一级目录');


    }

    /**
     *  cd /mnt/f/doc/bfcode/porter/app/; ./app  homepc/file removeHasFormatedOldFile --env=dev0  --formated_dir='/mnt/f/tmp2/format _N_V_2023_02_02_/wait/FFOutput' --old_dir='/mnt/f/tmp2/format _N_V_2023_02_02_/wait' --preview=yes
     *
     * /app  homepc/file removeHasFormatedOldFile --env=dev0  --formated_dir='/mnt/f/tmp2/format _N_V_2023_02_02_/wait/FFOutput' --old_dir='/mnt/f/tmp2/format _N_V_2023_02_02_/wait' --preview=yes
     * /mnt/f/tmp2/format _N_V_2023_02_02_/wait/FFOutput
     *
     * @throws \Exception
     */


    public function dealFile(ScanedFile $scanedFile)
    {
        if ($scanedFile->isDelFile())
        {
            unlink($scanedFile->filePath);
            return false;
        }

        if ($scanedFile->isZipFile())
        {
            $cmd = "unzip -o {$scanedFile->filePath} -d {$this->rootDir}";
            $cmd = "unzip -O GBK {$scanedFile->filePath} -d {$this->rootDir}";

            echo "\nUNZIP:{$cmd}\n";

            exec($cmd, $ar);
            var_dump($cmd, $ar);
            return false;
        }
        return true;
    }



    //ffmpeg -i  1.mp4 -map_metadata 0 -c copy 2.mp4
    //
    //ffmpeg -i   /mnt/f/tmp2/format\ _name_ver_2023_02_02_/dirs/tv/高顔長腿爆奶人妻『JBS』超尺度爆表《人體壽司》\ _name_ver_2023_02_02_/人體壽司\ \(3\)\ \[720-H264\].mp4 -map_metadata 0 -c copy /mnt/f/tmp2/format\ _name_ver_2023_02_02_/dirs/tv/高顔長腿爆奶人妻『JBS』超尺度爆表《人體壽司》\ _name_ver_2023_02_02_/3.mp4
    //
    //
    // /mnt/f/tmp2/format\ _name_ver_2023_02_02_/dirs/tv/高顔長腿爆奶人妻『JBS』超尺度爆表《人體壽司》\ _name_ver_2023_02_02_/人體壽司\ \(3\)\ \[720-H264\].mp4
    //
    //
    //ffprobe -v error -of flat=s=_ -select_streams v:0 -show_entries stream=height,width  '/mnt/f/tmp2/tmp/高清AV系列可爱到爆炸#天花板级清纯小可爱妹妹身上全身青春的气息超级粉嫩美穴爆肏颜值党福利2 _name_ver_2023_02_02_.mp4'
    //
    //
    //ffprobe -hide_banner  -v panic  -show_entries format=duration,size,bit_rate,filename -select_streams v:0 -show_entries stream=height,width -print_format json  '/mnt/f/tmp2/tmp/高清AV系列可爱到爆炸#天花板级清纯小可爱妹妹身上全身青春的气息超级粉嫩美穴爆肏颜值党福利2 _name_ver_2023_02_02_.mp4'


    public function formatDirVideoNames($rootDir)
    {

        $rootDir = '/' . trim($rootDir, '/');

        $cmd = "find '{$rootDir}' -type f|grep -v '.png'|grep -v '.jpg'|grep -v '.gif'|grep -v FFOutput";
        //  $cmd = "find '{$dir}' -type f|grep -v '.png'|grep -v '.jpg'|grep -v '.gif'|grep -v FFOutput|grep -v 'VIF_'|grep -v '.json'";

        exec($cmd, $ar);
        //var_dump($ar);


        $rubbishWords     = VersionControl::$rubbishWords;
        $rubbishWords[]   = "'";
        $rubbishWords[]   = '"';
        $rubbishWords     = array_unique($rubbishWords);
        $rubbishWordEmpty = array_fill(0, count($rubbishWords), ' ');
        $tmp              = ['加勒比' => 'carib-', '一本道' => '1pondo-', 'HEYZO' => 'heyzo-'];
        foreach ($tmp as $k => $v)
        {
            $rubbishWords[]     = $k;
            $rubbishWordEmpty[] = $v;
        }


        $pattern = '/[^' . '\p{Han}'                   // 中文汉字
            . '\p{Hiragana}'              // 日文平假名
            . '\p{Katakana}'              // 日文片假名
            . '\x{30FC}'                  // 日文长音符号
            . '\p{Hangul}'                // 韩文
            . '\p{Cyrillic}'              // 西里尔字母 (俄语、乌克兰语等)
            . 'a-zA-Z'                    // 英文字母
            . '0-9'                       // 数字
            . '_'                         // 下划线
            . '\.'                        // 点
            . '\-'                        // -
            . '\p{Latin}'                 // 拉丁字母 (包含法语、德语等)
            . ']/u';
        foreach ($ar as $i => $filepath)
        {
            $file_info = pathinfo($filepath);
            // var_dump($file_info);
            $ext          = strtolower($file_info['extension']);
            $filenameStr  = $file_info['filename'];
            $videoDir     = $file_info['dirname'];
            $albumNameDir = trim(str_replace($rootDir, '', $videoDir), '/');

            $this->printer->tabEcho("src:{$i} {$filepath}");
            $fullFilePath = $filepath;

            $newfilenameStr = $filenameStr;
            if (preg_match('/^-id-\d+-id-/isU', $filenameStr))
            {

            }
            else
            {


            }


            $newfilenameStr = preg_replace($pattern, ' ', $newfilenameStr);
            $newfilenameStr = preg_replace('/(\.\.mp4\.mp4)/isU', '.mp4', $newfilenameStr);

            $newfilenameStr = preg_replace('/(_LOW_|_H_|mp4|video)/isU', '', $newfilenameStr);
            $newfilenameStr = preg_replace('/(TV_|PHONE_)/isU', '', $newfilenameStr);
            $newfilenameStr = preg_replace('/(_uniq\d+)/isU', '', $newfilenameStr);
            $newfilenameStr = preg_replace('/(_N_[\w\d_]+\d+)/isU', '', $newfilenameStr);
            $newfilenameStr = str_replace($rubbishWords, $rubbishWordEmpty, $newfilenameStr);

            $newfilenameStr = trim(str_replace(['_.mp4', '.��', '.ts', '.mp4'], '', $newfilenameStr));
            $newfilenameStr = trim($newfilenameStr, '_ ');
            $newfilenameStr = preg_replace('/\s+/', ' ', $newfilenameStr);

            if (strlen($newfilenameStr) === 0)
            {
                $newfilenameStr = date('Ymd-His-') . rand(100, 999);
            }

            if (strlen($albumNameDir) > 0)
            {
                $albumNameDir = preg_replace($pattern, ' ', $albumNameDir);
                $albumNameDir = str_replace($rubbishWords, $rubbishWordEmpty, $albumNameDir);
                $albumNameDir = preg_replace('/\s+/', ' ', $albumNameDir);
            }
            else
            {

            }
            if (strlen($albumNameDir) > 0)
            {
                $videoDir = "{$rootDir}/{$albumNameDir}";

            }
            if (file_exists($videoDir))
            {
                chmod($videoDir, 0777);
            }
            else
            {
                mkdir($videoDir, 0777, true);
                usleep(200000);
            }

            $newFullFilePath = "{$videoDir}/{$newfilenameStr}.{$ext}";


            // var_dump($filenameStr);
            //  var_dump(preg_match('/^flag-\d+-\d+-.*?/isU', $newfilenameStr));
            if ($newFullFilePath !== $fullFilePath)
            {
                $this->printer->tabEcho("res:{$i} {$newFullFilePath}\n");
                rename($fullFilePath, $newFullFilePath);
            }


        }


    }

    public function getVideos($rootDir)
    {

        $rootDir = '/' . trim($rootDir, '/');

        $cmd = "find '{$rootDir}' -type f|grep -v '.png'|grep -v '.jpg'|grep -v '.gif'|grep -v FFOutput";
        //  $cmd = "find '{$dir}' -type f|grep -v '.png'|grep -v '.jpg'|grep -v '.gif'|grep -v FFOutput|grep -v 'VIF_'|grep -v '.json'";

        exec($cmd, $ar);
        $this->printer->tabEcho($ar);
        $map = [];
        foreach ($ar as $i => $filepath)
        {

            $file_info = pathinfo($filepath);
            // var_dump($file_info);
            $ext            = $file_info['extension'];
            $filenameStr    = $file_info['filename'];
            $videoDir       = $file_info['dirname'];
            $albumNameDir   = trim(str_replace($rootDir, '', $videoDir), '/');
            $map[$filepath] = [
                'ext'           => $ext,
                'videoBasename' => $filenameStr,
                'dirname'       => $videoDir,
                'filename'      => $filenameStr,
                'albumNameDir'  => $albumNameDir
            ];

        }
        return $map;


    }

    public function make_task()
    {
        Sys::app()->initPrinter();
        $printer = $this->printer;
        $this->printer->setOutputState(true);

        $dst_flag   = $this->inputBox->tryGetString('dst_flag');
        $rootDir    = $this->inputBox->tryGetString('dir');
        $forceRenew = $this->inputBox->tryGetString('force_renew') === 'yes';

        if (empty($rootDir))
        {
            $rootDir = '/mnt/f/tmp2/format/wait/php';
        }
        chmod($rootDir, 0777);

        if (0)
        {
            $this->formatDirVideoNames($rootDir);
            sleep(1);
            $this->formatDirVideoNames($rootDir);
            sleep(1);
        }

        $getDirsCmd = "find '{$rootDir}' -maxdepth 2 -type d|grep -v FFOutput_res|grep -v FFOutput_tmp";
        $getDirsCmd = "find '{$rootDir}' -maxdepth 2 -type d|grep -v FFOutput";

        //$getDirsCmd        = "find '{$rootDir}' -maxdepth 2 -type d";
        $clearEmptyDirsFun = function ($rootDir) use ($getDirsCmd)
        {
            echo "\n{$getDirsCmd}\n";
            exec($getDirsCmd, $dirs);
            foreach ($dirs as $dir)
            {
                if ($dir === $rootDir || $dir === "{$rootDir}/FFOutput_res" || $dir === "{$rootDir}/FFOutput_tmp" || $dir === "{$rootDir}/FFOutput")
                {
                    echo "skip:{$dir}";
                    continue;
                }
                $lsCmd = "ls -l '{$dir}'";
                echo "\nls cmd:{$lsCmd}\n";
                $lastStr = exec($lsCmd);
                if (strstr($lastStr, 'total 0'))
                {
                    @rmdir($dir);
                    echo "rmdir '{$dir}'\n";
                }
            }
        };
        for ($tmp_i = 0; $tmp_i < 5; $tmp_i++)
        {
            $clearEmptyDirsFun($rootDir);
        }

        $this->printer->setOutputState(false);

        $videoInfoKV = $this->getVideos($rootDir);
        $this->printer->setOutputState(true);
        $taskM = new FfmpegTask();
        $this->printer->newTabEcho('try_insert_new', 'try_insert_new 先记录文件');
        foreach ($videoInfoKV as $videoFUllPath => $info)
        {
            $taskM = new FfmpegTask();
            $taskM->setLogState(true);
            $existM = $taskM->findByAttributes(['album_dir' => $info['albumNameDir'], 'video_title' => $info['videoBasename'], 'video_ext' => $info['ext'],]);
            //$this->printer->tabEcho($taskM->getLogs());
            if ($existM === false)
            {
                $taskM->album_dir      = $info['albumNameDir'];
                $taskM->video_title = $info['videoBasename'];
                $taskM->video_ext      = $info['ext'];
                $taskM->root_dir       = $rootDir;
                $taskM->src_video_info = '{}';
                $taskM->dst_video_info = '{}';
                $taskM->task_info      = '{}';
                $taskM->is_src_exist   = Def::staYes;
                $taskM->save();
                $id36        = base_convert($taskM->id, 10, 36);
                $taskM->id36 = "{$id36}";
                $taskM->save();
                $this->printer->tabEcho("[{$taskM->id}] insert_new");
            }
            else
            {
                $this->printer->tabEcho("[{$existM->id}]  exist_and_check is_error:{$existM->is_err} is_ok:{$existM->is_ok}  is_pre:{$existM->is_pre} is_exist:{$existM->is_src_exist} ext:{$info['ext']}/{$existM->video_ext} {$existM->create_date}/{$taskM->update_date} run_lc:{$existM->run_lc}\n\t{$videoFUllPath}");
                $existM->is_pre = Def::staNot;

                $id36         = base_convert($existM->id, 10, 36);
                $existM->id36 = "{$id36}";
                $existM->save();

                if ($forceRenew)
                {
                    $existM->is_ok = Def::staNot;
                    $existM->save();
                }
                if ($existM->is_src_exist === Def::staNot)
                {
                    $existM->is_src_exist = Def::staYes;
                    $existM->save();
                }
                if (intval($existM->run_lc) > 0)
                {
                    $existM->run_lc = 0;
                    $existM->save();
                }
            }
            $taskM->setLogState(false);
        }
        $this->printer->endTabEcho('try_insert_new', 'try_insert_new');

        $taskMs = $taskM->findAllByAttributes(['is_ok' => Def::staNot, 'JSON_LENGTH(src_video_info) = 0']);
        //$taskMs = $taskM->findAllByAttributes(['is_ok' => Def::staNot, 'video_title like "%CWPBD%"']);
        $taskMs = $taskM->findAllByAttributes(['is_ok' => Def::staNot,]);

        $MIN_RATE_K         = 1600;
        $MIN_FPS            = 24;
        $MIN_HEIGHT         = 720;
        $MAX_HEIGHT         = 720;
        $KEEP_SRC_VIDEO_KWS = ['黑丝', '天使', '星宫', '葵', '夏目', '二宫', '野野', '浦暖', 'ol', '本庄'];
        $EXPECT_TAG2CFG_KV  = [
            'NO_MOSAIC' => [
                // 'kws'    => 'tokyo,1pon,加勒比,carib,店长推荐,MKD,heyzo,hey,一本道,CWP,x-art,xart,ppv,kin8,MKBD,FC2,CWPBD,Blacked,',
                'kws'    => 'tokyo,1pon,加勒比,carib,店长推荐,MKD,heyzo,hey,一本道,CWP,x-art,xart,ppv,kin8,MKBD,CWPBD,',
                'pregs'  => ['/n\d+/isU'],
                'expect' => ['height' => 720, 'fps' => 30, 'rate' => 3000],
            ],
            'HEIGHT'    => [
                'kws'    => '',
                'pregs'  => ['/dst1080p/isU'],
                'expect' => ['height' => 1080, 'fps' => 30, 'rate' => 3000],
            ],
            'DOC'       => [
                'kws'    => '_doc_',
                'pregs'  => [],
                'expect' => ['height' => 1080, 'fps' => 12, 'rate' => 3000],
            ],
        ];
        foreach ($EXPECT_TAG2CFG_KV as $tag => $cfg)
        {
            $EXPECT_TAG2CFG_KV[$tag]['kws'] = array_filter(array_map(function ($str) { return strtolower(trim($str)); }, explode(',', $cfg['kws'])), function ($str) { return strlen($str) > 0; });
        }
        $KEEP_SRC_VIDEO_KWS = array_merge($KEEP_SRC_VIDEO_KWS, $EXPECT_TAG2CFG_KV['NO_MOSAIC']['kws']);
        $KEEP_SRC_VIDEO_KWS = array_merge($KEEP_SRC_VIDEO_KWS, $EXPECT_TAG2CFG_KV['DOC']['kws']);
        $KEEP_SRC_VIDEO_KWS = array_unique($KEEP_SRC_VIDEO_KWS);


        echo "\n";


        $ver_ctrl = new VersionControl();
        $ver_ctrl::init();


        $ts       = time();
        $true_i   = 0;
        $true_cnt = count($taskMs);
        $this->printer->newTabEcho('make dst config', "make task config ,task cnt:{$true_cnt} 准备获取videos info\n");

        foreach ($taskMs as $i => $taskM)
        {
            $taskM->initData();


            $true_i++;
            //   echo "\n****************************************************************\n";
            //  echo "{$willStr}";

            if (strlen($taskM->album_dir) > 0)
            {
                $albumDirPath = "/{$taskM->album_dir}/";
            }
            else
            {
                $albumDirPath = "/";
            }
            $videoNameFullStr = strtolower("{$albumDirPath}/{$taskM->video_title}");
            $video_path       = "{$taskM->root_dir}{$albumDirPath}{$taskM->video_title}.{$taskM->video_ext}";
            $this->printer->tabEcho("file:{$video_path}");

            $this->printer->endTabEcho('make_single_video_task');

            $this->printer->tabEcho("\n\n\n\ncurr: {$true_i}/{$true_cnt}    ID:{$taskM->id}  ERR:{$taskM->is_err} OK:{$taskM->is_ok}  PRE:{$taskM->is_pre} SRC_EXIST:{$taskM->is_src_exist} EXT:{$taskM->video_ext} \n{$video_path}   \n");
            $this->printer->newTabEcho('make_single_video_task', 'make single task,get video info');
            $srcVideo = new VideoFile($video_path);
            $srcVideo->setPrinter($printer);
            // $srcVideo->initFilename();
            // var_dump($srcVideo->getHalfTrimFlagFullPath(), $srcVideo->getFullTrimFlagFullPath());die;
            $ext = $taskM->video_ext;
            // if (in_array($ext, ['ts', 'mkv'], true))
            $ext_lower = strtolower($ext);
            if (!in_array($ext_lower, ['mp4', 'avi'], true))
            {
                $taskM->is_pre = Def::staYes;
                $taskM->save();
                $this->printer->tabEcho('not avi/mp4  ,skip');
                continue;
            }

            if (isset($taskM->src_video_info['width']))
            {
                $srcVideo->width           = intval($taskM->src_video_info['width']);
                $srcVideo->height          = intval($taskM->src_video_info['height']);
                $srcVideo->fps             = intval($taskM->src_video_info['fps']);
                $srcVideo->rateK           = intval($taskM->src_video_info['rateK']);
                $srcVideo->durationSeconds = intval($taskM->src_video_info['duration']);
                $srcVideo->isPhone         = $srcVideo->height > $srcVideo->width;
            }
            else
            {
                $msgs = [];
                if ($srcVideo->initVideoInfo($msgs) === false)
                {
                    $taskM->is_err = Def::staYes;
                    $taskM->save();
                    $msgsStr = join('|', $msgs);
                    echo CLIStrFormatter::error("ERROR initVideo失败\n{$video_path}\n{$msgsStr}\n");
                    $this->printer->tabEcho('error  ,skip');
                    continue;
                }
            }


            $this->printer->tabEcho("\nSRC:width:{$srcVideo->width} height:{$srcVideo->height} fps:{$srcVideo->fps} bit_rate:{$srcVideo->rateK} duration:{$srcVideo->durationSeconds}");
            if (empty($srcVideo->fps) || empty($srcVideo->width) || empty($srcVideo->height) || empty($srcVideo->rateK))
            {
                $taskM->is_err = Def::staYes;
                $taskM->save();
                $this->printer->tabEcho(CLIStrFormatter::error("ERROR video_info_lost {$video_path}\n"));

                continue;
            }

            $ffCmd = new ffCmd($srcVideo);

            $isPhone = $srcVideo->isPhone;


            foreach ($KEEP_SRC_VIDEO_KWS as $kw)
            {
                if (strstr($ffCmd->src->baseName, $kw))
                {
                    $ffCmd->delSrc = false;
                    break;
                }
            }
            if ($ffCmd->delSrc)
            {
                $taskM->is_auto_del_src = Def::staYes;
                $taskM->save();
            }

            $this->printer->newTabEcho('auto_dst_config', 'auto dst config');
            /**   $expectFps int 预期fps */
            $expectFps = $MIN_FPS;
            /**   $MIN_RATE_K int 预期rate k */
            $expectRateK = $MIN_RATE_K;
            /**   $MIN_HEIGHT int 预期高度 */
            $expectHeight = $MIN_HEIGHT;
            foreach ($EXPECT_TAG2CFG_KV as $tag => $cfg)
            {
                $tmpMatches = [];

                foreach ($cfg['kws'] as $kw)
                {
                    if (strstr($videoNameFullStr, $kw) || strstr($videoNameFullStr, $kw))
                    {
                        $tmpMatches[] = $kw;
                    }
                }
                foreach ($cfg['pregs'] as $pattern)
                {
                    if (preg_match($pattern, $videoNameFullStr))
                    {
                        $tmpMatches[] = $pattern;
                    }
                }
                if (count($tmpMatches) > 0)
                {
                    $expectFps    = $cfg['expect']['fps'];
                    $expectRateK  = $cfg['expect']['rate'];
                    $expectHeight = $cfg['expect']['height'];
                    $this->printer->tabEcho("\nKW_EXEPCT:[$tag] h:{$expectHeight} rate:{$expectRateK} fps:{$expectFps}" . join(' | ', $tmpMatches) . "\n");
                    break;
                }
            }

            $flags = ['copy' => true];
            if ($expectRateK > $srcVideo->rateK)
            {
                if ($srcVideo->fps < $MIN_FPS)
                {
                    $ffCmd->dstFlag = 'ffCopy';
                    $ffCmd->dstFlag = 'low2';

                    $fpss = [12, 15, 18, 24, 30];
                    if (in_array($srcVideo->fps, $fpss))
                    {
                        $ffCmd->dstFps = $srcVideo->fps;
                    }
                    else
                    {
                        foreach ($fpss as $fps)
                        {
                            if ($srcVideo->fps < $fps)
                            {
                                break;
                            }
                            $ffCmd->dstFps = $fps;
                        }

                    }
                    $ffCmd->dstMaxRateK  = intval($srcVideo->rateK * 0.9);
                    $ffCmd->dstMaxHeight = $srcVideo->height;
                    $this->printer->tabEcho("rate  video < DEFAULT && fps  video < MIN   {$srcVideo->rateK}  < {$expectRateK} / {$srcVideo->fps} < {$MIN_FPS} ,so dst flag is [ low2 ]");

                    $flags = [
                        'type'     => $ffCmd->dstCode,
                        'height'   => $ffCmd->dstMaxHeight,
                        'maxRateK' => "{$ffCmd->dstMaxRateK}K",
                        'fps'      => $ffCmd->dstFps,
                    ];

                }
                else
                {
                    $ffCmd->dstMaxRateK = intval($srcVideo->rateK * 0.9);
                    $this->printer->tabEcho("rate  video < DEFAULT && fps  video > MIN  {$srcVideo->rateK}  < {$expectRateK}  / {$srcVideo->fps} > {$MIN_FPS} ,so dst rate is [ 9/10 *video.rate ]");

                }
            }
            else
            {
                $ffCmd->dstMaxRateK = $expectRateK;
                $this->printer->tabEcho("rate  video > DEFAULT   {$srcVideo->rateK}  > {$expectRateK} ,so dst rate is [ DEFAULT ]");

            }


            if ($ffCmd->dstFlag === 'ffCopy')
            {

            }
            else if ($ffCmd->dstFlag === 'low2')
            {
                $flags['copy'] = false;

            }
            else
            {
                $flags = ['copy' => false];
                if ($isPhone)
                {
                    if ($srcVideo->height >= $MAX_HEIGHT)
                    {
                        $ffCmd->dstMaxHeight = $MAX_HEIGHT;
                    }
                    else
                    {
                        if ($srcVideo->height >= $MIN_HEIGHT)
                        {
                            $ffCmd->dstMaxHeight = $MIN_HEIGHT;
                        }
                    }
                }
                else
                {
                    if ($srcVideo->height >= $expectHeight)
                    {
                        $ffCmd->dstMaxHeight = $expectHeight;
                    }
                    else if ($srcVideo->height >= $MIN_HEIGHT)
                    {
                        $ffCmd->dstMaxHeight = $MIN_HEIGHT;
                    }
                }

                if ($srcVideo->fps >= $expectFps)
                {
                    $ffCmd->dstFps = $expectFps;
                }
                else
                {
                    if ($srcVideo->fps > 24 && $srcVideo->fps < 30)
                    {
                        $ffCmd->dstFps = 24;
                    }
                    else
                    {
                        $ffCmd->dstFps = $srcVideo->fps;
                    }
                }

                $flags = [
                    'type'     => $ffCmd->dstCode,
                    'height'   => $ffCmd->dstMaxHeight,
                    'maxRateK' => "{$ffCmd->dstMaxRateK}K",
                    'fps'      => $ffCmd->dstFps,
                ];

                if ($expectHeight > $srcVideo->height)
                {
                    $flags['height'] = $srcVideo->height;;
                }

            }

            if (!isset($taskM->src_video_info['width']))
            {
                $taskM->src_video_info = [
                    'width'    => $srcVideo->width,
                    'height'   => $srcVideo->height,
                    'rateK'    => $srcVideo->rateK,
                    'fps'      => $srcVideo->fps,
                    'duration' => $srcVideo->durationSeconds,
                ];
            }
            $taskInfo = [];
            if (!isset($taskM->task_info['hasExpect']))
            {
                $taskM->task_info = [];
            }
            else
            {
                $taskInfo = $taskM->task_info;
            }

            $taskInfo['expect']    = $flags;
            $taskInfo['hasExpect'] = true;
            $taskM->task_info      = $taskInfo;
            $taskM->save();
            $this->printer->endTabEcho('auto_dst_config', '');

            $ts1   = time();
            $date1 = date('Y-m-d H：i：s', $ts1);
            $this->printer->tabEcho("[$date1] {$i}/{$true_cnt}");
            //  var_export($taskM->src_video_info . $taskM->task_info);

            $this->printer->tabEcho('DST:' . json_encode($ffCmd->getDstInfo(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $this->printer->endTabEcho('make_single_video_task', 'make task end');
        }
        $this->printer->endTabEcho('make_single_video_task');
        $this->printer->newTabEcho('check_exist_state', 'check_not_exist');
        //$taskMs = $taskM->findAllByAttributes(['is_ok' => Def::staNot,]);
        $taskMs = $taskM->findAllByAttributes(['is_src_exist' => Def::staYes,]);
        foreach ($taskMs as $i => $taskM)
        {
            $srcInfo = $taskM->getSrcVideoInfo($rootDir);
            if ($srcInfo['filesize'] === 0)
            {
                $taskM->is_src_exist = Def::staNot;
                $taskM->is_ok        = 1;
                $taskM->save();
            }
            $this->printer->tabEcho("check_exist_state [{$taskM->id}] {$taskM->create_date}/{$taskM->update_date} [{$srcInfo['filesize']}] {$taskM->album_dir}/{$taskM->video_title}.{$taskM->video_ext} ");
        }
        $this->printer->endTabEcho('check_exist_state', 'check_not_exist');
        $this->printer->endTabEcho('make dst config', "make task config");
    }


    public function test1()
    {
        $srcFile = '/mnt/f/tmp2/format/wait/php/Enkai enkai987Fansone 走马探花 Ysabella仙女降临超骚性技口技吓吓叫AV导演_恩凯.mp4';
        $dstFIle = '/mnt/f/tmp2/format/wait/php/FFOutput_tmp/Enkai enkai987Fansone 走马探花 Ysabella仙女降临超骚性技口技吓吓叫AV导演_恩凯 1548 _NC_.mp4';
        if (file_exists($dstFIle))
        {
            @unlink($dstFIle);
        }
        $cmd = "ffmpeg -hide_banner -i '{$srcFile}' -ss 24 -to 2917 \
-map 0:v:0 -c:v hevc_nvenc -crf 0 -maxrate 1600k -bufsize 4000k \
-vf \"scale = -2:if (gte(ih\,720)\,720\,ih),fps = 24\" -preset p7 \
-map 0:a? -c:a aac -ac 2 -b:a 192k \
-map 0:s? -c:s mov_text \
-map_metadata 0 -movflags use_metadata_tags \
'{$dstFIle}'";
        $cmd = "script -q -f -c \"sudo ffmpeg -hide_banner -i '/mnt/f/tmp2/format/wait/php/Enkai enkai987Fansone 走马探花 Ysabella仙女降临超骚性技口技吓吓叫AV导演_恩凯.mp4' -ss 24 -to 2917 -map 0:v:0 -c:v hevc_nvenc -crf 0 -maxrate 1600k -bufsize 4000k -vf 'scale=-2:if(gte(ih\,720)\,720\,ih),fps=24' -preset p7 -map 0:a? -c:a aac -ac 2 -b:a 192k -map 0:s? -c:s mov_text -map_metadata 0 -movflags use_metadata_tags '/mnt/f/tmp2/format/wait/php/FFOutput_tmp/Enkai enkai987Fansone 走马探花 Ysabella仙女降临超骚性技口技吓吓叫AV导演_恩凯 1548 _NC_.mp4'\" ";
        // $sse->simpleExecCmd($cmd);


        echo "开始转码: $cmd\n\n";
        $command = $cmd;
        $handle  = popen($command, 'r');

        if (!$handle)
        {
            die("无法启动ffmpeg\n");
        }

        $lastLine = '';

        while (!feof($handle))
        {
            $line = fgets($handle);
            if ($line === false)
                continue;

            $line = rtrim($line, "\r\n");
            if (empty($line))
                continue;

            // 如果是进度行，用回车符覆盖
            if (strpos($line, 'time=') !== false)
            {
                echo "\r\033[K" . $line;
                $lastLine = $line;
            }
            else
            {
                // 普通输出
                if ($lastLine)
                {
                    echo "\n";
                    $lastLine = '';
                }
                echo $line . "\n";
            }
            echo "\nxxxx\n";

        }

        pclose($handle);

    }

    public function convFuckType2Mp4($type, $srcFile, $dstFile)
    {
        $this->printer->tabEcho("convFuckType2Mp4 {$type}\n{$srcFile}\n{$dstFile}");
        switch ($type)
        {

            case 'ts':
            case 'm2ts':
            case 'mov':

                $cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
  -map 0:v:0 -c:v:0 hevc_nvenc -vf \"fps=24\" \
  -map 0:a:0 -c:a aac -ac 2 -b:a 192k \
  -map 0:v:1? -c:v:1 copy -disposition:v:1 attached_pic \
  -map 0:s? -c:s copy \
  -map_metadata 0 -movflags use_metadata_tags \
  '{$dstFile}'";
                break;
            case 'mkv':
                $cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
  -map 0:v -c:v hevc_nvenc -preset p4 -crf 23 \
  -map 0:a:0 -c:a aac -ac 2 -b:a 192k \
  -map 0:d? -c:d copy \
  -map 0:s? -c:s copy \
  -map_metadata 0 -movflags +faststart \
  '{$dstFile}'";
                break;
            case 'flv':
                // FLV处理代码
                $cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
  -c:v copy \
  -c:a copy \
  -map 0 \
  -f flv '{$dstFile}'";
                $cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
  -map 0:v:0 -c:v:0 h264_nvenc -vf \"fps=24,format=yuv420p\" \
  -preset p4 -crf 23 \
  -map 0:a:0 -c:a aac -ac 2 -b:a 192k \
  -map 0:s? -c:s copy \
  -map_metadata 0 \
  -f flv '{$dstFile}'";
                $cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
  -map 0:v:0 -c:v:0 libx264 -vf \"fps=24,format=yuv420p\" \
  -preset medium -crf 23 \
  -map 0:a:0 -c:a aac -ac 2 -b:a 192k \
  -map 0:s? -c:s copy \
  -map_metadata 0 \
  -f flv '{$dstFile}'";
                break;
            case 'wmv':
                $cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
  -map 0:v:0 -c:v:0 hevc_nvenc -vf \"fps=24,format=yuv420p\" \
  -map 0:a:0 -c:a:0 aac -ac 2 -b:a:0 192k \
  -map 0:a:1? -c:a:1 aac -ac 2 -b:a:1 128k \
  -map_metadata 0 \
  -movflags +faststart \
  '{$dstFile}'";
                break;
            default:
                die("\nold_no_dst_flag [{$type}] !!!\n");
                break;
        }


        switch ($type)
        {

            case 'ts':
            case 'm2ts':
            case 'mov':

                $cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
  -map 0:v:0 -c:v:0 hevc_nvenc -vf \"fps=24\" \
  -map 0:a:0 -c:a aac -ac 2 -b:a 192k -af \"loudnorm=I=-16:TP=-1.5:LRA=11\" \
  -map 0:v:1? -c:v:1 copy -disposition:v:1 attached_pic \
  -map 0:s? -c:s copy \
  -map_metadata 0 -movflags use_metadata_tags \
  '{$dstFile}'";
                break;

            case 'mkv':

                $cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
  -map 0:v -c:v hevc_nvenc -preset p4 -crf 23 \
  -map 0:a:0 -c:a aac -ac 2 -b:a 192k -af \"loudnorm=I=-16:TP=-1.5:LRA=11\" \
  -map 0:s? -c:s copy \
  -map_metadata 0 -movflags +faststart \
  '{$dstFile}'";
                break;

            case 'flv':

                $cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
  -map 0:v:0 -c:v:0 libx264 -vf \"fps=24,format=yuv420p\" \
  -preset medium -crf 23 \
  -map 0:a:0 -c:a aac -ac 2 -b:a 192k -af \"loudnorm=I=-16:TP=-1.5:LRA=11\" \
  -map 0:s? -c:s copy \
  -map_metadata 0 \
  -f flv '{$dstFile}'";
                break;
            case 'wmv':
                $cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
  -map 0:v:0 -c:v:0 hevc_nvenc -vf \"fps=24,format=yuv420p\" \
  -map 0:a:0 -c:a aac -ac 2 -b:a 192k -af \"loudnorm=I=-16:TP=-1.5:LRA=11\" \
  -map 0:s? -c:s copy \
  -map_metadata 0 \
  -movflags +faststart \
  '{$dstFile}'";
                break;
            default:
                die("\nno_dst_flag [{$type}] !!!\n");
                break;
        }
        echo "\nconvFuckType2Mp4 CMD:\n{$cmd}\n";
        passthru($cmd);
    }


    public function fetchTasks()
    {

        echo "\nfetchTasks\n";

        Sys::app()->initPrinter();
        $printer  = Sys::app()->getPrinter();
        $ver_ctrl = new VersionControl();
        $ver_ctrl::init();
        $this->printer = $printer;


        $taskM = new FfmpegTask();
        $tn    = $taskM->getTableName();
        $db    = $taskM->getConnection();


        $taskMs = $taskM->findAllByAttributes(['is_ok' => Def::staNot,]);
        //$taskMs = $taskM->findAllByAttributes(['id' => 172,]);


        $ts       = time();
        $true_i   = 0;
        $true_cnt = count($taskMs);
        for ($i = -1; $i < $true_cnt; $i++)
        {
            echo "\n****************************************************************\n";
            $taskM = (new FfmpegTask())->findByAttributes(['is_ok' => Def::staNot, 'run_lc' => 0]);
            if (empty($taskM))
            {
                echo CLIStrFormatter::success("\ncurr: {$true_i}/{$true_cnt} \n");
                continue;
            }
            $taskM->initData();
            $true_i++;


            if (strlen($taskM->album_dir) > 0)
            {
                $albumDirPath = "/{$taskM->album_dir}/";
            }
            else
            {
                $albumDirPath = "/";
            }
            $videoNameFullStr = strtolower("{$albumDirPath}/{$taskM->video_title}");
            $video_path       = "{$taskM->root_dir}{$albumDirPath}{$taskM->video_title}.{$taskM->video_ext}";


            echo CLIStrFormatter::success("\ncurr: {$true_i}/{$true_cnt}   ID:{$taskM->id}  ERR:{$taskM->is_err} OK:{$taskM->is_ok}  PRE:{$taskM->is_pre} SRC_EXIST:{$taskM->is_src_exist} EXT:{$taskM->video_ext} Lock:{$taskM->run_lc}   \n{$video_path}\n");
            echo "\n" . json_encode($taskM->src_video_info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $lc = intval(date('YmdHis'));
            $db->setText("update {$tn}  set run_lc={$lc} ,try_times=try_times+1 where id={$taskM->id} and run_lc=0")->execute();
            if (intval($db->setText("select  run_lc from {$tn}  where id={$taskM->id}")->queryScalar()) !== $lc)
            {
                echo "\n lock fail,continue\n";
                continue;
            }
            echo "\n****************************************************************\n";
            //  echo "{$willStr}";
            echo "\ncurr : {$true_i}/{$true_cnt}    {$video_path}  \n";
            $srcVideo = new VideoFile($video_path);
            $srcVideo->setFileVersionController($ver_ctrl);
            $srcVideo->setPrinter($printer);

            $ext = strtolower($srcVideo->ext);
            if ($taskM->is_pre === Def::staYes)
            {
                $new_mp4_path = "{$taskM->root_dir}{$albumDirPath}{$taskM->video_title} ^{$taskM->id36}^.mp4";

                if (in_array($ext, ['ts', 'mkv', 'mov', 'm2ts', 'flv', 'wmv'], true))
                {
                    $this->convFuckType2Mp4($ext, $video_path, $new_mp4_path);
                    usleep(1000);
                    $srcVideo = new VideoFile($new_mp4_path);
                    $srcVideo->setFileVersionController($ver_ctrl);
                    $srcVideo->setPrinter($printer);
                    if ($srcVideo->initVideoInfo())
                    {
                        $taskM->is_ok = Def::staYes;
                    }
                    else
                    {
                        $taskM->is_err = Def::staYes;
                        echo(CLIStrFormatter::error("ERROR conv_fail {$video_path}\n"));

                    }
                    $taskM->save();
                    // exec("/app  homepc/xx/task make --env=dev0");
                    continue;

                }
            }


            $ts1   = time();
            $date1 = date('Y-m-d H：i：s', $ts1);

            echo "[$date1] {$i}/{$true_cnt} \n{$video_path}\n";

            // var_dump($new_filename);
            //$this->convAction($new_file_flag, $video_path, $new_filename, $video_max_bitrateK_set, $tmpMatches);
            echo json_encode($taskM->task_info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);


            //$ffCmd->delSrc=true;
            while (true)
            {
                $sta = trim(file_get_contents('/mnt/f/tmp2/format/wait/php_task_state.txt'));
                if ($sta === 'stop')
                {
                    echo "\nstop\n";
                    die;
                }
                else if ($sta === 'sleep')
                {
                    $date  = date('Y-m-d H:i:s');
                    $sleep = 30;
                    echo "{$date} sleep {$sleep}s\n}";
                    sleep($sleep);
                }
                else if ($sta === 'ok')
                {
                    break;
                }
                else
                {
                    break;
                }
            }
            $this->convTask($taskM);

            $ts2       = time();
            $date2     = date('Y-m-d H：i：s', $ts2);
            $cost_curr = $ts2 - $ts1;
            $cost_all  = $ts2 - $ts;
            echo "\n" . json_encode($taskM->src_video_info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);;
            echo "\n[$date2] curr : {$true_i}/{$true_cnt}   curr cost:{$cost_curr}  all cost:{$cost_all}\n";
            //die;
        }
    }

    public function convTask(FfmpegTask $task)
    {
        if (!isset($task->task_info['expect']['maxRateK']) && !isset($task->task_info['expect']['copy']))
        {
            return false;
        }
        $expectSetting = $task->task_info['expect'];

        if (strlen($task->album_dir) > 0)
        {
            $albumDirPath = "/{$task->album_dir}/";
        }
        else
        {
            $albumDirPath = "/";
        }
        $videoNameFullStr = strtolower("{$albumDirPath}/{$task->video_title}");
        $srcVideoFullPath = "{$task->root_dir}{$albumDirPath}{$task->video_title}.{$task->video_ext}";
        /**  $dstFinnalRelativeFilename string 最终结果 相对路径 */
        $dstFinnalRelativeFilename = '';
        /**  $dstFinnalAbsoluteFilename string 最终结果 绝对路径 */
        $dstFinnalAbsoluteFilename = '';

        /**  $tmpCoverAbsoluteFilename string 图片 绝对路径 */
        $tmpCoverAbsoluteFilename = '';
        if ($task->is_pre === Def::staYes)
        {
            $tmpVideoAbsoluteFilename = "{$task->root_dir}{$albumDirPath}{$task->video_title} ^{$task->id36}^ _NC_.mp4";
        }
        else
        {
            $tmpVideoAbsoluteFilename = "{$task->root_dir}/FFOutput_tmp{$albumDirPath}{$task->video_title} ^{$task->id36}^ _NC_.mp4";
            $tmpCoverAbsoluteFilename = "{$task->root_dir}/FFOutput_tmp{$albumDirPath}{$task->video_title} ^{$task->id36}^ Cover.jpg";

            $dstFinnalRelativeFilename = "/FFOutput_res{$albumDirPath}{$task->video_title} ^{$task->id36}^.mp4";
            $dstFinnalAbsoluteFilename = "{$task->root_dir}{$dstFinnalRelativeFilename}";
        }
        $dstTmpVideoDir = dirname($tmpVideoAbsoluteFilename);
        if (!file_exists($dstTmpVideoDir))
        {
            $this->printer->tabEcho("mkdir tmp [{$dstTmpVideoDir}]");
            mkdir($dstTmpVideoDir, 0777, true);
        }
        $dstTmpResDir = dirname($dstFinnalAbsoluteFilename);
        if (!file_exists($dstTmpResDir))
        {
            $this->printer->tabEcho("mkdir res [{$dstTmpVideoDir}]");
            mkdir($dstTmpResDir, 0777, true);
        }
        if (file_exists($tmpVideoAbsoluteFilename))
        {
            @unlink($tmpVideoAbsoluteFilename);
            usleep(3000);
        }

        if (is_file($dstFinnalAbsoluteFilename))
        {
            @unlink($dstFinnalAbsoluteFilename);
        }

        $srcFile = $srcVideoFullPath;

        $cmdTpl        = '';
        $isTmpAsRes    = false;
        $manualSetting = $task->task_info['manual'] ?? [];
        if (isset($manualSetting['isCopy']) && ($manualSetting['isCopy'] === true || $manualSetting['isCopy'] === 'true'))
        {
            $this->printer->tabEcho(CLIStrFormatter::info("走修复路线\n" . json_encode($manualSetting) . "\n"));
            $this->printer->tabEcho($manualSetting);

            $fixedVideo     = str_replace('_NC_', '_FIX_', $tmpVideoAbsoluteFilename);
            $trueFixedVideo = $fixedVideo;
            $cutSs          = 0;
            $cutTo          = 0;
            $csf            = 22;
            if (isset($task->task_info['manual']['ss']) || isset($task->task_info['manual']['to']))
            {

                $cutSs = intval($manualSetting['ss'] ?? 0);
                $cutTo = intval($manualSetting['to'] ?? 0);
                if (isset($manualSetting['csf']))
                {
                    $csf = intval($manualSetting['csf']);
                }

            }
            $cutAr = [];
            if ($cutSs)
            {
                $cutAr[] = "-ss {$cutSs}";
            }
            if ($cutTo)
            {
                $cutAr[] = "-to {$cutTo}";
            }
            $cutStr = '';
            if (count($cutAr))
            {
                // $cutAr[] = '\\';
                $cutStr = implode(' ', $cutAr);
            }
            $resTypeAs = $manualSetting['resAs'];
            $resVer    = $manualSetting['resVer'];
            switch ($resTypeAs)
            {
                case 'mkv':
                    $trueFixedVideo = "{$fixedVideo}.mkv";
                    switch ($resVer)
                    {
                        case '':
                        default:
                            $fixCmdTpl = "ffmpeg -hide_banner -i '{srcFile}' {$cutStr} \
-video_track_timescale 90000 \
-c:v hevc_nvenc -crf 22 -maxrate 3600k -bufsize 8000k \
-vf \"scale=-2:if(gte(ih\,1080)\,1080\,ih),fps=24,setpts=PTS-STARTPTS\" -preset p7 \
-c:a aac -ac 2 -b:a 192k -af \"loudnorm=I=-16:TP=-1.5:LRA=11,asetpts=PTS-STARTPTS\" \
-c:s mov_text \
-map_metadata 0 \
'{resFile}.mkv'
";
                            break;
                    }
                    break;

                case 'h264':
                    $trueFixedVideo = "{$fixedVideo}.mp4";
                    switch ($resVer)
                    {
                        case '26.06.22':
                        case '[v26.06.22]':
                            $fixCmdTpl  = "ffmpeg -hwaccel cuda -hwaccel_output_format cuda {$cutStr} -i '{srcFile}' \
  -map 0:v:0 -c:v hevc_nvenc -rc vbr -cq 22 -maxrate 3600k -bufsize 9000k \
  -vf \"scale_cuda=-2:if(gte(ih\,1080)\,1080\,ih),fps=24,setpts=PTS-STARTPTS\" \
  -preset p7 -enc_time_base -1 -vsync 0 \
  -map 0:a? -c:a aac -ac 2 -b:a 192k -af \"volume=5dB\" \
  -map_metadata 0 -movflags use_metadata_tags \
  -avoid_negative_ts make_zero -write_tmcd 0 \
  '{resFile}.mp4'";
                            $isTmpAsRes = true;

                            break;
                        case '':
                        default:
                            $fixCmdTpl = "ffmpeg -hide_banner -i '{srcFile}' {$cutStr} \
-c:v libx264 -preset fast -crf 18 \
-vf \"scale=-2:if(gte(ih\,1080)\,1080\,ih),fps=24,setpts=PTS-STARTPTS\" \
-c:a aac -ac 2 -b:a 192k -af \"loudnorm=I=-16:TP=-1.5:LRA=11,asetpts=PTS-STARTPTS\" \
-c:s mov_text \
-map_metadata 0 -movflags use_metadata_tags+faststart \
'{fixedFile}.mp4'
";
                            break;
                    }
                    break;
                case 'h265':
                default:
                    switch ($resVer)
                    {
                        default:
                            $cmd = " ffmpeg -hide_banner -i '{$srcFile}' {$cutStr} -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc  -c copy '{$tmpVideoAbsoluteFilename}'";

                            $cmd    = " ffmpeg -hide_banner -fflags +genpts -i '{$srcFile}' {$cutStr} -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc  -c copy '{$tmpVideoAbsoluteFilename}'";
                            $cmdTpl = " ffmpeg -hide_banner -fflags +genpts -i '{srcFile}'  {$cutStr} -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc  -c copy '{resFile}'";

                            $cmd = "ffmpeg -hide_banner -fflags +genpts -i '{$srcFile}' {$cutStr} \
-c:v copy \
-c:a aac -ac 2 -b:a 192k \
-c:s copy \
-map_metadata 0 \
-movflags use_metadata_tags \
'{$tmpVideoAbsoluteFilename}'";

                            $fixCmd2   = "
ffmpeg -hide_banner -i '{$srcFile}' {$cutStr}  \
-vf \"setpts=PTS-STARTPTS,fps=24\" \
-af \"asetpts=PTS-STARTPTS\" \
-c:v libx264 -preset ultrafast -crf 18 \
-c:a aac -b:a 192k \
-c:s mov_text \
-map_metadata 0 \
-movflags use_metadata_tags \
'{$fixedVideo}'
            ";
                            $fixCmdTpl = "
ffmpeg -hide_banner -i '{srcFile}' {$cutStr}\
-vf \"setpts=PTS-STARTPTS,fps=24\" \
-af \"asetpts=PTS-STARTPTS\" \
-c:v libx264 -preset ultrafast -crf 18 \
-c:a aac -b:a 192k \
-c:s mov_text \
-map_metadata 0 \
-movflags use_metadata_tags \
'{resFile}'
";
                            $fixCmdTpl = "
ffmpeg -hide_banner -i '{srcFile}' {$cutStr} \
-video_track_timescale 90000 \
-c:v hevc_nvenc -crf 22 -maxrate 3600k -bufsize 8000k \
-vf \"scale=-2:if(gte(ih\,1080)\,1080\,ih),fps=24,setpts=PTS-STARTPTS\" -preset p7 \
-c:a aac -ac 2 -b:a 192k -af \"loudnorm=I=-16:TP=-1.5:LRA=11,asetpts=PTS-STARTPTS\" \
-c:s mov_text \
-map_metadata 0 -movflags use_metadata_tags+faststart \
-output_ts_offset 0 \
'{resFile}'";
                            break;
                    }
                    break;
            }

            $fixCmd = str_replace(['{srcFile}', '{resFile}'], [$srcFile, $fixedVideo], $fixCmdTpl);
            if (file_exists($trueFixedVideo))
            {
                @unlink($trueFixedVideo);
            }
            echo "\nfix tmp special video CMD:\n{$fixCmd}\n";
            echo "\nfix tmp special video CMD tpl:\n{$fixCmdTpl}\n";

            passthru($fixCmd);

            $srcFile      = $fixedVideo;
            $newVideoName = "{$task->root_dir}{$albumDirPath}{$task->video_title} fixed.mp4";

            rename($trueFixedVideo, $dstFinnalAbsoluteFilename);
            $this->printer->tabEcho(CLIStrFormatter::warning("已经修复了特殊视频，请查看：{$dstFinnalAbsoluteFilename}"));
            $resFile = new VideoFile($dstFinnalAbsoluteFilename);
            $resFile->setPrinter($this->printer);
            if ($resFile->initVideoInfo())
            {
                $task->is_ok          = Def::staYes;
                $task->dst_video_info = [
                    'src'      => $dstFinnalRelativeFilename,
                    'width'    => $resFile->width,
                    'height'   => $resFile->height,
                    'rateK'    => $resFile->rateK,
                    'fps'      => $resFile->fps,
                    'duration' => $resFile->durationSeconds,
                ];
                $task->save();
            }
            return false;
        }
        else
        {
            $this->printer->tabEcho(CLIStrFormatter::info('走正常路线'));


            $dstMaxRateK  = intval(str_replace('K', '', $expectSetting['maxRateK']));
            $dstMaxHeight = intval($expectSetting['height']);
            $dstFps       = intval($expectSetting['fps']);

            $cutSs = 0;
            $cutTo = 0;
            $csf   = 22;
            if (isset($task->task_info['manual']['maxRateK']))
            {
                $manualSetting = $task->task_info['manual'];
                $dstMaxRateK   = intval(str_replace('K', '', $manualSetting['maxRateK']));
                $dstMaxHeight  = intval($manualSetting['height']);
                $dstFps        = intval($manualSetting['fps']);

                $cutSs = intval($manualSetting['ss'] ?? 0);
                $cutTo = intval($manualSetting['to'] ?? 0);
                if (isset($manualSetting['csf']))
                {
                    $csf = intval($manualSetting['csf']);
                }

            }
            $cutAr = [];
            if ($cutSs)
            {
                $cutAr[] = "-ss {$cutSs}";
            }
            if ($cutTo)
            {
                $cutAr[] = "-to {$cutTo}";
            }
            $cutStr = '';
            if (count($cutAr))
            {
                // $cutAr[] = '\\';
                $cutStr = implode(' ', $cutAr);
            }


            $bufsize = 4000;
            $map     = ['{maxrateStr}' => '', '{vfStr}' => ''];
            if ($dstMaxRateK > 0)
            {
                $map['{maxrateStr}'] = "-maxrate {$dstMaxRateK}k";
                $tmpBufsize          = $dstMaxRateK * 2;
                if ($tmpBufsize > $bufsize)
                {
                    $bufsize = $tmpBufsize;
                }
            }
            $vfs = [];
            if ($dstMaxHeight > 0)
            {
                $vfs[] = "scale=-2:if(gte(ih\,{$dstMaxHeight})\,{$dstMaxHeight}\,ih)";
            }

            if ($dstFps > 0)
            {
                $vfs[] = "fps={$dstFps}";
            }
            if (count($vfs) > 0)
            {
                $vfs[]          = "setpts=PTS-STARTPTS";
                $tmp_str        = implode(",", $vfs);
                $map['{vfStr}'] = "-vf \"{$tmp_str}\"";
            }
            $common_cmd = "ffmpeg -hide_banner -i '{$srcFile}' {$cutStr} \
-map 0:v:0 -c:v hevc_nvenc -crf {$csf} {maxrateStr} -bufsize {$bufsize}k \
{vfStr} -preset p7 \
-map 0:a? -c:a aac -ac 2 -b:a 192k -af \"loudnorm=I=-16:TP=-1.5:LRA=11\" \
-map 0:s? -c:s mov_text \
-map_metadata 0 -movflags use_metadata_tags \
'{$tmpVideoAbsoluteFilename}'";
            $common_cmd = "ffmpeg -hide_banner -copyts -start_at_zero -i '{$srcFile}' {$cutStr} \
-map 0:v:0 -c:v hevc_nvenc -crf {$csf} {maxrateStr} -bufsize {$bufsize}k \
{vfStr} -preset p7 \
-map 0:a? -c:a aac -ac 2 -b:a 192k -af \"loudnorm=I=-16:TP=-1.5:LRA=11,asetpts=PTS-STARTPTS\" \
-map 0:s? -c:s mov_text \
-map_metadata 0 -movflags use_metadata_tags \
'{$tmpVideoAbsoluteFilename}'";
            $cmdTpl     = "ffmpeg -hide_banner -copyts -start_at_zero -i '{srcFile}' {$cutStr} \
-map 0:v:0 -c:v hevc_nvenc -crf {$csf} {maxrateStr} -bufsize {$bufsize}k \
{vfStr} -preset p7 \
-map 0:a? -c:a aac -ac 2 -b:a 192k -af \"loudnorm=I=-16:TP=-1.5:LRA=11,asetpts=PTS-STARTPTS\" \
-map 0:s? -c:s mov_text \
-map_metadata 0 -movflags use_metadata_tags \
'{resFile}'";
            $cmd        = str_replace(array_keys($map), array_values($map), $common_cmd);
            $cmdTpl     = str_replace(array_keys($map), array_values($map), $cmdTpl);


            echo "\nmake tmp video CMD:\n{$cmd}\n";
            echo "\nmake tmp video CMD tpl:\n{$cmdTpl}\n";

            passthru($cmd);
            // echo CLIStrFormatter::error("ERROR initVideo失败\n{$video_path}\n{$msgsStr}\n");

            $this->printer->tabEcho(CLIStrFormatter::warning("已经生成tmp文件"));
            $this->printer->tabEcho("已经生成临时文件，准备截图");
            $this->printer->tabEcho($isTmpAsRes);
            if ($isTmpAsRes)
            {
                $this->printer->tabEcho("isTmpAsRes==true ");

                //  return false;
            }
            else
            {
                $this->printer->tabEcho("xxxxx ");

                //   return false;
            }

            // 1. 获取视频时长
            $cmdDuration = "ffprobe -v error -show_entries format=duration -of csv=p=0 " . escapeshellarg($tmpVideoAbsoluteFilename);
            $duration    = floatval(trim(shell_exec($cmdDuration)));
            if ($duration <= 0)
            {
                echo CLIStrFormatter::error("ERROR 获取临时文件时长失败\n{$tmpVideoAbsoluteFilename}\n");
                $task->logError('获取临时文件时长失败');
                return false;
            }

            // 2. 计算中点时间
            $mid = $duration / 2;

            // 3. 提取中点帧
            $cmdCover = sprintf('ffmpeg -hide_banner -ss %.3f -i %s -vframes 1 -q:v 2 %s -y', $mid, escapeshellarg($tmpVideoAbsoluteFilename), escapeshellarg($tmpCoverAbsoluteFilename));
            exec($cmdCover, $output, $ret);
            if ($ret !== 0)
            {
                $tmpErrNsg = '从临时文件提取封面失败';
                echo CLIStrFormatter::error("ERROR {$tmpErrNsg}\n{$tmpVideoAbsoluteFilename}\n");
                $task->logError($tmpErrNsg);
                @unlink($tmpCoverAbsoluteFilename);
                usleep(1000);
            }


            if (file_exists($tmpCoverAbsoluteFilename))
            {
                $append_cover_cmd     = "ffmpeg -hide_banner -i '{$tmpVideoAbsoluteFilename}' -i '{$tmpCoverAbsoluteFilename}' \
-map 0 -map 1:v -c copy -c:v:1 copy -disposition:v:1 attached_pic \
'{$dstFinnalAbsoluteFilename}'";
                $append_cover_cmd_tpl = "ffmpeg -hide_banner -i '{srcVideoFile}' -i '{srcImgFile}' \
-map 0 -map 1:v -c copy -c:v:1 copy -disposition:v:1 attached_pic \
'{resVideoFile}'";
                echo "\nappend cover CMD:\n{$append_cover_cmd}\n";
                echo "\nappend cover CMD tpl:\n{$append_cover_cmd_tpl}\n";
                passthru($append_cover_cmd);
                @unlink($tmpCoverAbsoluteFilename);
                if (file_exists($dstFinnalAbsoluteFilename))
                {
                    @unlink($tmpVideoAbsoluteFilename);
                }

                $resFile = new VideoFile($dstFinnalAbsoluteFilename);
                $resFile->setPrinter($this->printer);
                if ($resFile->initVideoInfo())
                {
                    $task->is_ok          = Def::staYes;
                    $task->dst_video_info = [
                        'src'      => $dstFinnalRelativeFilename,
                        'width'    => $resFile->width,
                        'height'   => $resFile->height,
                        'rateK'    => $resFile->rateK,
                        'fps'      => $resFile->fps,
                        'duration' => $resFile->durationSeconds,
                    ];
                    $task->save();
                    if ($task->is_auto_del_src === Def::staYes)
                    {
                        @unlink($srcFile);
                        usleep(200000);
                        if (!file_exists($srcFile))
                        {
                            $task->is_src_exist = Def::staNot;
                            $task->save();
                        }
                    }
                }


            }

        }


        //die;
    }

    /**
     * 视频拼接脚本
     * 功能：扫描目录，按自然排序拼接视频，输出文件名由原文件名分词去重生成。
     *
     * 用法: php video_merge.php
     *
     * 依赖:
     *   - lizhichao/word: composer require lizhichao/word
     *   - ffmpeg: 需要确保命令行工具可用
     */

    public function mergeByDir_v1()
    {
        $targetDir = $this->inputBox->getNotEmptyString('dir');
        $outputDir = $targetDir;
        // 3. 支持的视频扩展名
        $allowedExtensions = ['mp4', 'avi', 'mov', 'mkv', 'flv', 'wmv', 'm4v'];
        // ==================== 结束配置 ====================

        // 初始化VicWord分词器
        $vicWord = new VicWord();

        // 创建输出目录（如果不存在）
        if (!is_dir($outputDir))
        {
            if (!mkdir($outputDir, 0755, true))
            {
                die("错误: 无法创建输出目录 {$outputDir}，请检查权限。\n");
            }
            echo "已创建输出目录: {$outputDir}\n";
        }

        // --- 1. 扫描目录，过滤出视频文件 ---
        echo "正在扫描目录: {$targetDir}\n";
        $files          = array_diff(scandir($targetDir), ['..', '.']);
        $videoFiles     = [];
        $videoBasenames = [];
        foreach ($files as $file)
        {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExtensions) && substr($file, 0, 4) !== 'res:')
            {
                $videoFiles[]     = $file;
                $videoBasenames[] = str_replace(".{$ext}", '', $file);

            }
        }

        if (empty($videoFiles))
        {
            die("错误: 在指定目录未找到任何视频文件。\n");
        }
        echo "找到 " . count($videoFiles) . " 个视频文件。\n";

        // --- 2. 对文件列表进行自然排序（数字/字母优先）---
        natsort($videoFiles);
        $videoFiles = array_values($videoFiles); // 重新索引数组，确保是顺序的
        echo "文件排序完成。\n";

        // --- 3. 生成输出文件名（基于所有原文件名的分词、去重、拼接）---
        $videoBasenames = implode(' ', $videoBasenames);
        $segments       = $vicWord->getAutoWord($videoBasenames);
        $uniqueSegments = array_unique(array_map(function ($ar) { return trim($ar[0]); }, $segments));

        // 清除文件名中的非法字符
        $safeSegments = array_map(function ($segment)
        {
            return preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fa5}_-]/u', '', $segment);
        }, $uniqueSegments);
        $safeSegments = array_filter($safeSegments); // 去除空字符

        $baseName       = !empty($safeSegments) ? implode('_', $safeSegments) : 'merged_video';
        $outputFileName = $baseName . '.mp4';
        $outputPath     = $outputDir . '/res:' . $outputFileName;
        echo "生成输出文件名: {$outputFileName}\n";


        // --- 4. 构建FFmpeg文件列表，并执行拼接命令---
        $listFilePath   = $outputDir . '/files_to_concat.txt';
        $listFileHandle = fopen($listFilePath, 'w');

        foreach ($videoFiles as $videoFile)
        {
            $escapedPath = $targetDir . '/' . $videoFile;
            // 写入FFmpeg要求的格式: file '文件完整路径'
            fwrite($listFileHandle, "file '{$escapedPath}'\n");
        }
        fclose($listFileHandle);
        echo "已生成FFmpeg文件列表: {$listFilePath}\n";
        // 构建FFmpeg命令 (使用concat demuxer)
        $ffmpegCommand = sprintf('ffmpeg -y -f concat -safe 0 -i %s -c copy %s 2>&1', escapeshellarg($listFilePath), escapeshellarg($outputPath));

        echo "正在执行视频拼接...\n";
        exec($ffmpegCommand, $output, $returnCode);

        // 清理临时文件
        unlink($listFilePath);

        if ($returnCode === 0)
        {
            echo "\n🎉 成功！视频已拼接完成！\n";
            echo "保存路径: {$outputPath}\n";
        }
        else
        {
            echo "\n❌ 视频拼接失败，请检查以下FFmpeg的错误信息:\n";
            echo implode("\n", $output) . "\n";
            exit(1);
        }
    }

    public function mergeByDir()
    {
        $targetDir         = $this->inputBox->getNotEmptyString('dir');
        $outputDir         = $targetDir;
        $allowedExtensions = ['mp4', 'avi', 'mov', 'mkv', 'flv', 'wmv', 'm4v'];

        // 水印字体文件（WSL下访问Windows字体）
        $fontFile = '/mnt/f/doc/bfcode/porter/app/static/fonts/msyhbd.ttc';
        if (!file_exists($fontFile))
        {
            die("错误: 字体文件不存在: {$fontFile}\n");
        }

        // 初始化 VicWord 分词器
        $vicWord = new VicWord();

        // 创建输出目录
        if (!is_dir($outputDir))
        {
            if (!mkdir($outputDir, 0755, true))
            {
                die("错误: 无法创建输出目录 {$outputDir}\n");
            }
            echo "已创建输出目录: {$outputDir}\n";
        }

        // 1. 扫描视频文件
        echo "正在扫描目录: {$targetDir}\n";
        $files          = array_diff(scandir($targetDir), ['..', '.']);
        $videoFiles     = [];
        $videoBasenames = [];
        foreach ($files as $file)
        {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExtensions) && substr($file, 0, 4) !== 'res:')
            {
                $videoFiles[]     = $file;
                $videoBasenames[] = str_replace(".{$ext}", '', $file);
            }
        }
        if (empty($videoFiles))
        {
            die("错误: 在指定目录未找到任何视频文件。\n");
        }
        echo "找到 " . count($videoFiles) . " 个视频文件。\n";

        // 2. 自然排序
        natsort($videoFiles);
        $videoFiles = array_values($videoFiles);
        echo "文件排序完成。\n";

        // 3. 生成输出文件名（基于分词去重）
        $allNames        = implode(' ', $videoBasenames);
        $segments        = $vicWord->getAutoWord($allNames);
        $uniqueSegments  = array_unique(array_map(function ($ar) { return trim($ar[0]); }, $segments));
        $safeSegments    = array_filter(array_map(function ($segment)
        {
            return preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fa5}_-]/u', '', $segment);
        }, $uniqueSegments));
        $baseName        = !empty($safeSegments) ? implode('_', $safeSegments) : 'merged_video';
        $outputFileName  = $baseName . '.mp4';
        $finalOutputPath = $outputDir . '/res:' . $outputFileName;
        echo "最终输出文件名: {$outputFileName}\n";

        // ------------------- 第一步：快速合并（无重新编码） -------------------
        $tempMerged = $outputDir . '/temp_merged_no_watermark.mp4';
        $listFile   = $outputDir . '/files_to_concat.txt';
        $fh         = fopen($listFile, 'w');
        foreach ($videoFiles as $file)
        {
            $fullPath = $targetDir . '/' . $file;
            fwrite($fh, "file '{$fullPath}'\n");
        }
        fclose($fh);

        $cmdMerge = sprintf('ffmpeg -y -f concat -safe 0 -i %s -c copy %s 2>&1', escapeshellarg($listFile), escapeshellarg($tempMerged));
        echo "正在快速合并视频...\n";
        exec($cmdMerge, $mergeOutput, $mergeCode);
        unlink($listFile);

        if ($mergeCode !== 0)
        {
            echo "❌ 合并失败，FFmpeg 输出:\n" . implode("\n", $mergeOutput) . "\n";
            exit(1);
        }
        echo "✅ 合并完成，临时文件: {$tempMerged}\n";

        // ------------------- 第二步：获取每个视频的时长（用于水印时间轴） -------------------
        echo "正在分析各视频时长...\n";
        $durations = [];
        foreach ($videoFiles as $file)
        {
            $inputPath = $targetDir . '/' . $file;
            // 使用 ffprobe 获取时长（秒）
            $cmdDur = sprintf('ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s', escapeshellarg($inputPath));
            $dur    = trim(shell_exec($cmdDur));
            if ($dur === false || !is_numeric($dur))
            {
                die("错误: 无法获取文件 {$file} 的时长，请检查 ffprobe 是否可用。\n");
            }
            $durations[] = (float)$dur;
        }

        // 计算每个片段在合并后视频中的起始/结束时间
        $segmentsTime = [];
        $cumulative   = 0.0;
        foreach ($videoFiles as $idx => $file)
        {
            $start          = $cumulative;
            $end            = $cumulative + $durations[$idx];
            $segmentsTime[] = [
                'file'  => $file,
                'start' => $start,
                'end'   => $end,
                'text'  => pathinfo($file, PATHINFO_FILENAME)  // 无扩展名
            ];
            $cumulative     = $end;
        }
        $totalDuration = $cumulative;
        echo "总时长: {$totalDuration} 秒\n";

        // ------------------- 第三步：构建动态水印滤镜（多个 drawtext，按时间区间显示） -------------------
        $filterComplexParts = [];
        // 水印样式：右下角，带半透明背景框
        $baseStyle = "fontfile='{$fontFile}':fontsize=24:fontcolor=white:box=1:boxcolor=black@0.5:boxborderw=5:x=w-tw-10:y=h-th-10";
        foreach ($segmentsTime as $seg)
        {
            $safeText             = addcslashes($seg['text'], "':\\");  // 转义单引号和反斜杠
            $enableCond           = "between(t,{$seg['start']},{$seg['end']})";
            $filterComplexParts[] = "drawtext={$baseStyle}:text='{$safeText}':enable='{$enableCond}'";
        }
        $filterChain = implode(',', $filterComplexParts);
        $vf          = $filterChain;  // drawtext 滤镜可以直接放在 -vf 参数（单个流）

        // 最终输出文件路径
        $finalOutputPath = $outputDir . '/res:' . $outputFileName;

        // 执行重新编码并添加水印
        $cmdWatermark = sprintf('ffmpeg -y -i %s -vf "%s" -c:v libx264 -crf 23 -preset medium -c:a copy %s 2>&1', escapeshellarg($tempMerged), $vf, escapeshellarg($finalOutputPath));
        echo "正在添加动态水印（重新编码）...\n";
        echo "FFmpeg 命令: {$cmdWatermark}\n";
        exec($cmdWatermark, $watermarkOutput, $watermarkCode);

        // 清理临时文件
        if (file_exists($tempMerged))
        {
            unlink($tempMerged);
        }

        if ($watermarkCode === 0)
        {
            echo "\n🎉 成功！视频已拼接并添加动态水印！\n";
            echo "保存路径: {$finalOutputPath}\n";
        }
        else
        {
            echo "\n❌ 添加水印失败，FFmpeg 输出:\n" . implode("\n", $watermarkOutput) . "\n";
            exit(1);
        }
    }
}

class VersionControl
{
    private $file_name_ver = '1';
    public  $onlyName      = '';

    public static $rubbishWords     = [];
    public static $rubbishWordEmpty = [];
    public static $fixNameMap       = [];

    public function trimNameVersion($versioned_filename)
    {
        return str_replace(" _N_V_{$this->file_name_ver}_", '', $versioned_filename);
    }

    public function addNameVersion($onlyName)
    {
        return $this->isVersionedName($onlyName) ? $onlyName : "{$onlyName} _N_V_{$this->file_name_ver}_";
    }

    public function isVersionedName()
    {
        return strstr($this->onlyName, "_N_V_") === false ? false : true;
    }


    public static function init()
    {
        self::$rubbishWords = explode(',', '[Thz.la],  ,『,』,【,】,【】,[,],〖,〗,《,》,@,顶级無碼,sex8.cc,guochan2048.com,Night24.com,2048社区,-big2048.com,SEX8.CC,Weagogo,kpxvs.com,miohot0428,jav20s8.com,Woxav.Com,fun2048.com,最新流出,最新,钻石级,❤️,推荐,会所尊享,❤,推荐,蜜桃,传媒,特辑,新作,SEX8.CC,爱剪辑,我的视频,无码破解,破解,独家爆料,#,~,「,」,[,],(,),（,）,Uncensored,uncensored, MP4 ,MP4-,MP4_,BVPP, _final_ ,_name_ver_2023_02_02,_N_V__ _N_V__,⚫️,_N_V_1_,✅,\\,\/,/,|,?,#,+,付費福利,超精品,重磅,独家更新,約炮大神,高价定制,自译征用,精品福利,精品,：,中文字幕,自译征用,新模型,AI增强,video,中配中字,独家泄密,珍藏级,无水原档,91原始精英博主,入駐推特,高端泄密流出');

        $rubishWordsFilename = __APP_DIR__ . '/config/file/xx_video/rubish_words.txt';
        if (file_exists($rubishWordsFilename))
        {
            self::$rubbishWords = explode("\n", file_get_contents($rubishWordsFilename));
        }
        self::$rubbishWords[]   = "'";
        self::$rubbishWords[]   = '"';
        self::$rubbishWordEmpty = array_fill(0, count(self::$rubbishWords), ' ');
        self::$fixNameMap       = ['加勒比' => 'carib-', '一本道' => '1pondo-', 'HEYZO' => 'heyzo-'];
    }

    public function formatNameWord($flag, $full_fileanme, $old_str)
    {
        $new_str = $this->trimNameVersion($old_str);
        $new_str = str_replace(self::$rubbishWords, self::$rubbishWordEmpty, $old_str);
        foreach (self::$fixNameMap as $old_tag => $fixed_name)
        {
            if (strstr($full_fileanme, $old_tag))
            {
                $new_str = "{$fixed_name} " . str_replace($old_tag, '#', $new_str);
            }
        }

        //$new_name = trim(str_replace($rubbish_words, '', $filename_name));

        $tmp_str = preg_replace('/^([\d\-\.]?)+\s/i', '$2', $new_str);
        if ($tmp_str !== $new_str)
        {
            //   Sys::app()->getPrinter()->tabEcho(">>番号:\n");
            // Sys::app()->getPrinter()->dump($tmp_str);
            $new_str = $tmp_str;
        }
        preg_match_all('/([a-zA-Z0-9]+\.[a-zA-Z]+)/i', $new_str, $tm_ar);
        if (count($tm_ar) && count($tm_ar[1]))
        {
            //   Sys::app()->getPrinter()->tabEcho(">>去网址:\n");
            //  Sys::app()->getPrinter()->dump($new_str, $tm_ar);
            // $new_name = $tmp_str;
            $new_name2 = str_replace($tm_ar[1][0], '', $new_str);
            Sys::app()->getPrinter()->tabEcho("去网址\n{$new_str}\n{$new_name2}\n");

        }

        if ($new_str[0] === '-')
        {
            //  echo ">>去垃圾开头:\n";
            $tmp_str = preg_replace('/^(-)/i', '$2', $new_str);
            //var_dump($tmp_str);
            $new_str = $tmp_str;
        }


        $strs    = array_unique(explode('#', $new_str));
        $new_str = join(' ', $strs);

        if ($old_str !== $new_str)
        {
            Sys::app()->getPrinter()->tabEcho("\n******************************* formatNameWord {$flag} \nold:[{$old_str}] \nnew[{$new_str}]\n");
        }
        // return $new_str;
        return $new_str;
    }


    public function getSn($str)
    {

        $str = $this->trimNameVersion($str);

        $str     = trim($str);
        $old_str = $str;
        //var_dump($str);
        preg_match_all('/[\d\w]+\.[\w]+/i', $str, $ar);
        //var_dump($ar);
        if (count($ar[0]) === 1)
        {
            $str = str_replace($ar[0][0], '', $str);
        }

        preg_match_all('/[\w\d]+[\d\w\-\s]+\d/i', $str, $ar);
        if (count($ar[0]) > 0)
        {
            //   var_dump($ar);

            $tmp_ar = [];
            foreach ($ar[0] as $tmp_str)
            {
                $tmp_str = trim($tmp_str);
                $tmp_int = intval($tmp_str);
                if (strval($tmp_int) !== $tmp_str)
                {
                    $tmp_ar[] = $tmp_str;
                }
            }
            $str = join(' ', $tmp_ar) . str_replace($tmp_ar, '', $str);
        }
        if ($old_str !== $str)
        {
            Sys::app()->getPrinter()->tabEcho("\n******************************* getSn \nold:[{$old_str}] \nnew[{$str}]<\n");

        }


        return $str;
    }

    /**
     * 去除英文数词 特殊符号
     * @param $only_filename
     * @return string|string[]|null
     * @throws \Exception
     */
    public function getDescFilename($only_filename)
    {
        $only_filename = $this->trimNameVersion($only_filename);
        //$trimed_str = preg_replace('/\s|[\x21-\x7e-A-Za-z0-9]/i', '', $only_filename);
        $trimed_str = preg_replace('/[\x21-\x7e-A-Za-z0-9]/i', '', $only_filename);

        if ($trimed_str !== $only_filename)
        {
            Sys::app()->getPrinter()->tabEcho("\n描述性文件名:[$trimed_str]   原文件名:[$only_filename]\n");
        }
        return $trimed_str;
    }

    public function getDescFilenameLength($versioned_filename)
    {
        return mb_strlen($this->trimNameVersion($versioned_filename));
    }


}

class ScanedFile extends VersionControl
{
    public static $del_exts   = ['html', 'htm', 'exe', 'torrent', 'txt', 'chm', 'lnk', 'url'];
    public static $video_exts = ['mp4', 'ts', 'avi', 'wmv', 'mkv', 'mov', 'srt'];
    public static $img_exts   = ['gif', 'jpg', 'jpeg', 'png'];

    public $ext      = '';
    public $onlyName = '';
    public $baseName = '';
    public $dirPath  = '';
    public $filePath = '';

    /**
     * @var ScanedDir
     */
    public $parentScanedDir;

    public function __construct(ScanedDir $scanedDir, $base_file_name)
    {
        $this->parentScanedDir = $scanedDir;
        $this->filePath        = "{$scanedDir->fullPath}/{$base_file_name}";

        $ar             = pathinfo($this->filePath);
        $this->dirPath  = $ar['dirname'];
        $this->ext      = strtolower($ar['extension']);
        $this->onlyName = $ar['filename'];//无路径 也无后缀
        $this->baseName = $ar['basename'];
        // [dirname] => /some/path
        //    [basename] => .test
        //    [extension] => test
        //    [filename] =>
    }

    public function isVideo()
    {
        return in_array($this->ext, self::$video_exts);
    }

    public function isImg()
    {
        return in_array($this->ext, self::$img_exts);

    }

    public function isDelFile()
    {
        return in_array($this->ext, self::$del_exts);
    }

    public function isZipFile()
    {
        return in_array($this->ext, ['zip']);
    }

    public function isReadableFile()
    {
        if ($this->isDelFile())
        {
            unlink($this->filePath);
            return false;
        }

        if ($this->isZipFile())
        {
            $cmd = "unzip -o {$this->filePath} -d {$this->dirPath}";
            $cmd = "unzip -O GBK {$this->filePath} -d {$this->dirPath}";

            echo "\nUNZIP:{$cmd}\n";

            exec($cmd, $ar);
            var_dump($cmd, $ar);
            return false;
        }
        return true;
    }

    public function formatVideoFile()
    {
        $filename_name = trim($this->baseName);
        Sys::app()->getPrinter()->newTabEcho('scan_lev1_files.file.getFormatedName.format', "尝试1");

        $formated_filename_name = trim($this->formatNameWord('单个文件dir', $this->filePath, $this->onlyName));
        Sys::app()->getPrinter()->endTabEcho('scan_lev1_files.file.getFormatedName.format', "尝试1");

        Sys::app()->getPrinter()->newTabEcho('scan_lev1_files.file.getFormatedName.getSN', "序号");
        $sned_filename_name = $this->getSn($formated_filename_name);
        Sys::app()->getPrinter()->endTabEcho('scan_lev1_files.file.getFormatedName.getSN', "序号");

        Sys::app()->getPrinter()->newTabEcho('scan_lev1_files.file.getFormatedName.getDesc', "获取描述信息");
        $desc_name = $this->getDescFilename($sned_filename_name);
        Sys::app()->getPrinter()->endTabEcho('scan_lev1_files.file.getFormatedName.getDesc', "获取描述信息");

        $desc_verifyed_filename = $sned_filename_name;
        if ($this->getDescFilenameLength($desc_name) < 15)
        {
            $desc_verifyed_filename = "{$this->parentScanedDir->onlyName} {$desc_verifyed_filename}";
            Sys::app()->getPrinter()->tabEcho("原dir name 描述信息too short，需要拼接一级dir name \nold:{$this->onlyName} \nnew:{$desc_verifyed_filename}\n");
        }
        $tmp_ar = explode(' ', $desc_verifyed_filename);

        $tmp_ar3 = [];
        foreach ($tmp_ar as $i => $tmp_str)
        {
            $tmp_str = trim($tmp_str);
            //var_dump($tmp_str);
            if (preg_match('/^[\d\-\w]+$/isU', $tmp_str) > 0)
            {
                $tmp_str = strtolower($tmp_str);
                // var_dump($tmp_str);
                if (in_array($tmp_str, $tmp_ar3, true))
                {
                    unset($tmp_ar[$i]);
                }
                else
                {
                    $tmp_ar3[] = $tmp_str;
                }
            }

        }
        //  var_dump($tmp_ar);
        $desc_verifyed_filename = join(' ', array_unique($tmp_ar));
        $desc_verifyed_filename = Param::convertStrType($desc_verifyed_filename, 'TOSBC');

        return trim($desc_verifyed_filename);


    }

}

class ScanedDir extends VersionControl
{
    public $dirsCnt  = 0;
    public $filesCnt = 0;

    public $dirs    = [];
    public $files   = [];
    public $dellist = [];

    public $isRoot   = false;
    public $fullPath = '';
    public $dirName  = '';
    public $onlyName = '';
    /**
     * @var ScanedDir|bool
     */
    public $parentScanedDir = false;

    /**
     * @var ScanedDir[]
     */
    public $scanedDirs = [];
    /**
     * @var ScanedFile[]
     */
    public $scanedFiles = [];

    static $i = 0;

    public function __construct($parentScanedDir, $dirName)
    {
        if ($parentScanedDir)
        {
            $this->parentScanedDir = $parentScanedDir;
            $this->fullPath        = "{$this->parentScanedDir->fullPath}/{$dirName}";
            $this->dirName         = $dirName;
            $this->onlyName        = $dirName;
        }
        else
        {
            $this->fullPath = $dirName;
            $this->isRoot   = true;
            $this->scanSelfDir();
        }
        if ($this->isVersionedName() === false && $this->isRoot === false)
        {
            //            var_dump($dirName);
            //            self::$i++;
            //            if(self::$i>2){
            //                throw new \Exception('dd');
            //                //debug_print_backtrace();die;
            //            }
            $this->scanSelfDir();
        }

    }

    public function scanSelfDir()
    {
        $sub_names = scandir($this->fullPath);
        // var_dump($this->fullPath);
        // echo "fullPath:{$this->fullPath}\n";
        foreach ($sub_names as $sub_name)
        {
            if (in_array($sub_name, ['.', '..']))
            {//scan 的结果，这两个不一定排在最前面，所以不能用array_slice
                continue;
            }
            $sub_path       = "{$this->fullPath}/{$sub_name}";
            $lower_sub_name = strtolower($sub_name);


            //如果是目录，直接移出去
            if (is_dir($sub_path))
            {
                $this->dirsCnt = $this->dirsCnt + 1;
                //  echo "\nsub {$this->fullPath}/{$sub_name} \n";
                $this->scanedDirs[] = new ScanedDir($this, $sub_name);
            }

            if (is_file($sub_path))
            {
                $this->scanedFiles[] = new ScanedFile($this, $sub_name);
                $this->filesCnt      = $this->filesCnt + 1;

            }
        }
    }


    public function getFormatedName()
    {
        Sys::app()->getPrinter()->newTabEcho('dir.getFormatedName.format', "尝试1");

        $formated_filename_name = trim($this->formatNameWord('单个文件dir', $this->fullPath, $this->onlyName));
        Sys::app()->getPrinter()->endTabEcho('dir.getFormatedName.format', "尝试1");

        Sys::app()->getPrinter()->newTabEcho('dir.getFormatedName.getSN', "序号");
        $sned_filename_name = $this->getSn($formated_filename_name);
        Sys::app()->getPrinter()->endTabEcho('dir.getFormatedName.getSN', "序号");

        Sys::app()->getPrinter()->newTabEcho('dir.getFormatedName.getDesc', "获取描述信息");
        $desc_name = $this->getDescFilename($sned_filename_name);
        Sys::app()->getPrinter()->endTabEcho('dir.getFormatedName.getDesc', "获取描述信息");

        $desc_verifyed_filename = $sned_filename_name;
        if ($this->getDescFilenameLength($desc_name) < 15)
        {
            $desc_verifyed_filename = "{$this->parentScanedDir->onlyName} {$desc_verifyed_filename}";
            Sys::app()->getPrinter()->tabEcho("原dir name 描述信息too short，需要拼接一级dir name \nold:{$this->onlyName} \nnew:{$desc_verifyed_filename}\n");

        }
        $desc_verifyed_filename = join(' ', array_unique(explode(' ', $desc_verifyed_filename)));
        $desc_verifyed_filename = Param::convertStrType($desc_verifyed_filename, 'TOSBC');

        // return $this->addNameVersion($desc_verifyed_filename);
        return $desc_verifyed_filename;

    }


}

class VideoFile
{
    public Printer        $printer;
    public VersionControl $fileVersionControl;
    public string         $fullPath;
    public string         $dir;
    public string         $fileName;

    public string $baseName;
    public string $ext;

    public int    $rateK;
    public int    $height;
    public int    $width;
    public int    $fps;
    public int    $durationSeconds;
    public string $durationStr;

    public bool $isPhone;

    public function __construct(string $fullPath)
    {
        $this->reloadFileinfo($fullPath);
    }

    private function reloadFileinfo(string $fullPath)
    {
        $this->fullPath = $fullPath;
        $file_info      = pathinfo($fullPath);
        $this->dir      = $file_info['dirname'];
        $this->baseName = $file_info['filename'];
        $this->ext      = $file_info['extension'];
        return $this;
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

    /**
     * @param VersionControl $versionControl
     * @return static
     */
    public function setFileVersionController(VersionControl $versionControl)
    {
        $this->fileVersionControl = $versionControl;
        return $this;
    }

    public function initVideoInfo(&$msgs = [])
    {
        $tmp     = [];
        $tmp_cmd = "ffprobe -hide_banner  -v panic  -show_entries format=duration,size,bit_rate,filename -select_streams v:0 -show_entries stream=height,width,nb_frames,avg_frame_rate,r_frame_rate  -print_format json '{$this->fullPath}'";
        $this->printer->tabEcho("info common CMD: \n$tmp_cmd\n");
        exec($tmp_cmd, $tmp);
        $video_info = json_decode(join('', $tmp), true);
        $this->printer->tabEcho(json_encode($video_info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $this->width           = intval($video_info['streams'][0]['width'] ?? 0);
        $this->height          = intval($video_info['streams'][0]['height'] ?? 0);
        $video_rate            = intval($video_info['format']['bit_rate'] ?? 0);
        $this->durationSeconds = 0;
        // var_dump($video_info);
        $this->fps = 0;
        if (isset($video_info['format']['duration']))
        {
            $this->durationSeconds = intval($video_info['format']['duration']);
        }
        if (isset($video_info['streams'][0]['nb_frames']))
        {

            if ($this->durationSeconds < 2)
            {
                $msgs[] = 'duration length error:' . $this->durationSeconds;
                return false;
            }
            else
            {
                $this->fps = intval($video_info['streams'][0]['nb_frames'] / $this->durationSeconds);
            }
            // var_dump($fps);
        }
        else if (isset($video_info['streams'][0]['avg_frame_rate']) && str_ends_with($video_info['streams'][0]['avg_frame_rate'], '/1'))
        {
            $this->fps = intval(str_replace('-1', '', $video_info['streams'][0]['avg_frame_rate']));
        }
        else
        {
            $tmp_cmd = "ffprobe -hide_banner -v error \
  -v panic\
  -select_streams v:0 \
  -show_entries stream=codec_name,avg_frame_rate,r_frame_rate,width,height,nb_read_frames \
  -show_entries format=duration,bit_rate,size \
  -print_format json '{$this->fullPath}'";
            $this->printer->tabEcho("info special CMD: \n$tmp_cmd\n");
            $tmp = [];
            exec($tmp_cmd, $tmp);
            $video_info = json_decode(join('', $tmp), true);
            $this->printer->tabEcho(json_encode($video_info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            if (isset($video_info['format']['duration']))
            {
                $this->durationSeconds = intval($video_info['format']['duration']);
            }
            if (isset($video_info['streams'][0]['nb_read_frames']))
            {
                $this->fps = intval($video_info['streams'][0]['nb_read_frames'] / $video_info['format']['duration']);
            }
            elseif (isset($video_info['streams'][0]['avg_frame_rate']) && str_ends_with($video_info['streams'][0]['avg_frame_rate'], '/1'))
            {
                $this->fps = intval(str_replace('-1', '', $video_info['streams'][0]['avg_frame_rate']));
            }
            else
            {
                $msgs[] = 'no nb_read_frames or duration:' . $this->durationSeconds;
            }
        }
        $this->rateK = intval($video_rate / 1024);
        echo "\nwidth:{$this->width} height:{$this->height} fps:{$this->fps} bit_rate:{$this->rateK} duration:{$this->durationSeconds}\n****************************************************************\n";
        if (empty($this->fps) || empty($this->width) || empty($this->height) || empty($video_rate) || empty($this->rateK))
        {
            $msgs[] = "error: width:{$this->width} height:{$this->height} fps:{$this->fps} bit_rate:{$this->rateK} duration:{$this->durationSeconds}";
            return false;
        }
        $this->isPhone = $this->height > $this->width;
        return true;
    }

    public function initFilename()
    {
        $filename = $this->baseName;
        $ext      = strtolower($this->ext);
        $filename = $this->fileVersionControl->formatNameWord('', $filename, $filename);
        $filename = "-{$filename}";
        $filename = preg_replace('/VIF_\d+P_H/isU', '', $filename);

        if ($filename !== $this->baseName)
        {
            $newFullPath = "{$this->dir}/{$filename}.{$ext}";
            rename($this->fullPath, $newFullPath);
            $this->reloadFileinfo($newFullPath);
        }
        if (!is_dir("{$this->dir}/FFOutput"))
        {
            exec("mkdir -p -m 777 '{$this->dir}/FFOutput'");
        }
    }

    public function isExist()
    {
        return file_exists($this->fullPath);
    }

    public function getHalfTrimFlagFullPath($flag = '')
    {
        $val = preg_replace('/^(-flag-)(\d{10}-)(\d+-)(\d+-)(flag\s)(.*)$/isU', '$1$2$3$4$5$6', $this->baseName);
        return "{$this->dir}/{$val}{$flag}.{$this->ext}";
    }

    public function getFullTrimFlagFullPath($flag = '')
    {
        $val = preg_replace('/^(-flag-)(\d{10}-)(\d+-)(\d+-)((flagFlag|flag)-\s)(.*)$/isU', '$7', $this->baseName);
        return "{$this->dir}/{$val}{$flag}.{$this->ext}";
    }

}

class ffCmd
{
    public VideoFile $src;
    public           $dstFps       = 0;
    public           $dstMaxHeight = 0;
    public           $dstMaxRateK  = 0;
    public           $dstCode      = '265';
    public string    $dstFlag      = '';
    public VideoFile $dst;
    public bool      $delSrc       = false;

    public function __construct(VideoFile $srcVideo)
    {
        $this->src = $srcVideo;
    }

    public function loadDstFile(VideoFile $dstVideo)
    {
        $this->dst = $dstVideo;
    }

    public function getDstInfo()
    {
        return ['code' => $this->dstCode, 'height' => $this->dstMaxHeight, 'rate' => $this->dstMaxRateK, 'fps' => $this->dstFps];
    }

}