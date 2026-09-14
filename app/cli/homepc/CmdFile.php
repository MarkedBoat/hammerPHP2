<?php


namespace cli\homepc;

use hammer\cli\CmdBase;
use hammer\param\Param;
use hammer\sys\Sys;
use models\ext\tool\Printer;

ini_set('memory_limit', '3072M');


class CmdFile extends CmdBase
{

    private $file_name_ver = '1';

    private $rootDir      = '';
    private $isPreivew    = true;
    private $rubbishWords = [];
    private $fixNameMap   = [];


    public function init()
    {
        parent::init();
    }

    /*
      cd /mnt/f/doc/bfcode/porter/app; ./app homepc/file moveThunderDownload --env=dev0
     sqlite3 '/mnt/e/Program Files (x86)/Thunder Network/Thunder/Profiles/TaskDb_bak.dat'
     */
    public function moveThunderDownload2()
    {
        $root_dir           = '/mnt/f/迅雷下载';
        $dir1_filenames     = scandir($root_dir);
        $dir1_filenames_cnt = count($dir1_filenames) - 2;
        $i                  = 0;
        $torrent_exts       = ['torrent'];
        $downloading_exts   = ['td'];
        $video_exts         = ['mp4', 'ts', 'avi', 'wmv', 'mkv', 'mov', 'srt'];

        $dirs_map = [
            'files_cnt'    => [],
            'torrents_cnt' => [],
            'no_videos'    => [],
            'seems_ok'     => [],
        ];

        foreach ($dir1_filenames as $dir1_filename)
        {
            if (in_array($dir1_filename, ['.', '..']))
            {
                continue;
            }
            $i = $i + 1;
            echo "\n{$i}/{$dir1_filenames_cnt}/{$dir1_filename}\n";
            $dir1_full_filename = "{$root_dir}/{$dir1_filename}";
            if (is_dir($dir1_full_filename))
            {
                $video_files       = [];
                $downloading_files = [];
                $torrent_files     = [];

                $files = [];


                $dir2_filenames = scandir($dir1_full_filename);
                foreach ($dir2_filenames as $dir2_filename)
                {
                    if (in_array($dir2_filename, ['.', '..']))
                    {
                        continue;
                    }
                    $dir2_full_filename  = "{$dir1_full_filename}/{$dir2_filename}";
                    $dir2_lower_filename = strtolower($dir2_filename);

                    if (is_file($dir2_full_filename))
                    {
                        $file_ext      = '';
                        $filename_name = '';
                        preg_match('/^(.*)\.(\w+)$/i', $dir2_lower_filename, $ar);
                        if (count($ar) === 3)
                        {
                            $filename_name = $ar[1];
                            $file_ext      = $ar[2];
                            if (in_array($file_ext, $torrent_exts))
                            {
                                $torrent_files[] = $dir2_filename;
                            }
                            else if (in_array($file_ext, $downloading_exts))
                            {
                                $downloading_files[] = $dir2_filename;
                            }
                            else if (in_array($file_ext, $video_exts))
                            {
                                $video_files[] = $dir2_filename;
                            }
                            else
                            {
                                var_dump($ar);
                                die("\n这个后缀怎么处理?\n");
                            }
                            $files[] = $dir2_filename;
                        }
                        else
                        {
                            var_dump($ar);
                            die("\n有问题 找不到后缀 看看吧\n");
                        }
                    }


                }

                $files_cnt       = count($files);
                $videos_cnt      = count($video_files);
                $torrents_cnt    = count($torrent_files);
                $downloading_cnt = count($downloading_files);
                $dir_info        = [
                    'dir'          => $dir1_filename,
                    'videos'       => $video_files,
                    'downloading'  => $downloading_files,
                    'files_cnt'    => $files_cnt,
                    'torrents_cnt' => $torrents_cnt,
                ];

                if ($files_cnt < 2)
                {
                    //die("文件数量异常");
                    $dirs_map['files_cnt'][] = $dir_info;
                }
                else
                {
                    if ($torrents_cnt !== 1)
                    {
                        //die("种子数量异常");
                        $dirs_map['torrents_cnt'][] = $dir_info;
                    }
                    else
                    {
                        if ($downloading_cnt === 0)
                        {
                            if ($videos_cnt < 1)
                            {
                                //die("没有下载的，但是又没有视频，也是异常");
                                $dirs_map['no_videos'][] = $dir_info;
                            }
                            else
                            {
                                $dirs_map['seems_ok'][$dir1_filename] = $dir_info;
                            }
                        }
                    }
                }
            }
        }
        echo "\n文件数量异常\n";
        $tmp_cnt = count($dirs_map['files_cnt']);
        $i       = 0;
        foreach ($dirs_map['files_cnt'] as $dir_info)
        {
            $i++;
            echo "\n文件数量异常{$i}/{$tmp_cnt}";
            var_export($dir_info);
            echo "\n";
        }


        echo "\n种子数量数量异常\n";
        $tmp_cnt = count($dirs_map['torrents_cnt']);
        $i       = 0;
        foreach ($dirs_map['torrents_cnt'] as $dir_info)
        {
            $i++;
            echo "\n种子数量数量异常{$i}/{$tmp_cnt}";
            var_export($dir_info);
            echo "\n";
        }


        echo "\n没视频\n";
        $tmp_cnt = count($dirs_map['no_videos']);
        $i       = 0;
        foreach ($dirs_map['no_videos'] as $dir_info)
        {
            $i++;
            echo "\n没视频{$i}/{$tmp_cnt}";
            var_export($dir_info);
            echo "\n";
        }


        echo "\n待确定\n";
        $tmp_cnt = count($dirs_map['seems_ok']);
        ksort($dirs_map['seems_ok']);
        $i = 0;
        foreach ($dirs_map['seems_ok'] as $dir_info)
        {
            $i++;
            echo "\n待确定{$i}/{$tmp_cnt}\n";
            //var_export($dir_info);
            echo json_encode($dir_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            echo "\n";
        }


    }

    /*
         cd /mnt/f/doc/bfcode/porter/app; ./app homepc/file moveThunderDownload --env=dev0
        sqlite3 '/mnt/e/Program Files (x86)/Thunder Network/Thunder/Profiles/TaskDb_bak.dat'
        */
    public function moveThunderDownload()
    {
        $from_dir = '/mnt/f/迅雷下载';
        $to_dir   = '/mnt/f/tmp2';

        $db_file = '/mnt/e/Program Files (x86)/Thunder Network/Thunder/Profiles/TaskDb.dat';
        //$db_file  = '/mnt/e/Program Files (x86)/Thunder Network/Thunder/Profiles/TaskDb_bak.dat';

        $sqlite = new \SQLite3($db_file);
        // $sqlite->open($db_file);
        var_export($sqlite);
        if (!$sqlite)
        {
            echo $sqlite->lastErrorMsg();
        }
        else
        {
            echo "Opened database successfully\n";
        }
        $sql    = <<<EOF
      select * from TaskBase;
EOF;
        $result = $sqlite->query('select SavePath,Status,Name from TaskBase where Status=10;');
        var_export($result);

        $i   = 0;
        $arr = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC))
        {
            $arr[$i] = $row;
            $i       += 1;
        }
        var_export($arr);


        echo "\n\n\n 请确认 迅雷已经关闭 [yes] !!!!!!!!!!!!!!!!!!!!!!!!!\n\n\n";
        $stdin = trim(fgets(STDIN));

        if ($stdin === 'yes')
        {
            echo "OK,go on……\n";
        }
        else
        {
            die("\n 停止 \n");
        }

        $cnt = count($arr);
        $i   = 0;
        foreach ($arr as $ar)
        {
            $i++;

            $src_file = "{$from_dir}/{$ar['Name']}";
            $to_file  = "{$to_dir}/{$ar['Name']}";
            echo "\n{$i}/{$cnt} {$src_file} -> {$to_file}\n";
            if (!file_exists($src_file))
            {
                die("\nsrc_file_not_exist:{$src_file}\n");
            }
            if (file_exists($to_file))
            {
                die("\nto_file_has_exist:{$to_file}\n");
            }
            rename($src_file, $to_file);
        }


    }


    /*
      cd /mnt/f/doc/bfcode/porter/app; ./app homepc/file clearDownload --env=dev0 >> ~/tmp2.txt
    cd /mnt/f/doc/bfcode/porter/app/; ./app  homepc/file moveFiles --env=dev0  --src='/mnt/f/download2' --dst='/mnt/f/tmp2/tmp' --csv='/mnt/f/tmp.txt'
     */
    public function moveFiles()
    {
        $src_dir = $this->inputBox->getNotEmptyString('src');
        if (!is_dir($src_dir))
        {
            throw new \Exception("dir error:{$src_dir}");
        }
        $dst_dir = $this->inputBox->getNotEmptyString('dst');
        if (!is_dir($dst_dir))
        {
            throw new \Exception("dir error:{$dst_dir}");
        }
        $input_file = $this->inputBox->getNotEmptyString('csv');
        if (!is_file($input_file))
        {
            throw new \Exception("csv file error:{$input_file}");
        }
        $f              = fopen($input_file, 'r');
        $fileLine       = 0;
        $errorFIlenames = [];
        while (!feof($f))
        {
            $fileLine++;
            $file_name    = trim(fgets($f));
            if(empty($file_name)){
                continue;
            }
            $src_filename = "{$src_dir}/{$file_name}";
            $dst_filename = "{$dst_dir}/{$file_name}";
            $src_flag     = file_exists($src_filename) ? 'OK' : 'NOT_EXSIT';
            $dst_flag     = file_exists($dst_filename) ? 'REPEAT' : 'OK';
            echo "\n{$fileLine} [{$file_name}] \n";


            $is_move_ok = 'XXXXX';
            $is_src_ok  = is_writable($src_filename) ? 'OK' : 'ERROR';
            $is_dst_ok  = is_writable($dst_dir) ? 'OK' : 'ERROR';

            if ($src_flag === 'OK' && $dst_flag === 'OK' && $is_dst_ok === 'OK' && $is_src_ok === 'OK')
            {
                $move_res = rename($src_filename, $dst_filename);
                if ($move_res)
                {
                    $is_move_ok = 'OK';
                }
                else
                {
                    $errorFIlenames[] = $file_name;
                    $is_move_ok       = 'XXXXX';
                }
            }

            $ar = [
                'res'          => $is_move_ok,
                'is_src_exist' => $src_flag,
                'is_dst_exist' => $dst_flag,
                'dst_writable' => $is_dst_ok,
                'src_writable' => $is_src_ok,
            ];
            $ks = [];
            $vs = [];
            foreach ($ar as $k => $v)
            {
                $v    = str_pad($v, 15, ' ');
                $k    = str_pad($k, 15, ' ');
                $ks[] = $k;
                $vs[] = $v;
            }
            echo join('', $ks) . "\n";
            echo join('', $vs) . "\n";

            echo "\t\t\t{$src_filename}\n\t\t\t{$dst_filename} \n";


        }
        fclose($f);
        if (count($errorFIlenames) > 0)
        {
            file_put_contents($input_file, join("\n", $errorFIlenames));
        }
        else
        {
            passthru("echo '' > {$input_file}");
        }
        passthru("chmod 777 -R '{$dst_dir}'");
    }


    public function delFiles()
    {

        $input_file = '/mnt/f/tmp_del.txt';
        if (!is_file($input_file))
        {
            throw new \Exception("csv file error:{$input_file}");
        }
        $f        = fopen($input_file, 'r');
        $fileLine = 0;
        while (!feof($f))
        {
            $fileLine++;
            $file_name = trim(fgets($f));
            if (empty($file_name))
                continue;
            if (strstr($file_name, '//'))
            {
                echo "\nskip {$fileLine} {$file_name}\n";
                continue;
            }

            $file_name = str_replace('F:/', '/mnt/f/', $file_name);
            echo "\n{$fileLine} {$file_name} \n";
            if (is_file($file_name) && is_writable($file_name))
            {
                echo "\n try";
                unlink($file_name);

            }
            else
            {
                echo "\n xxx";
            }

        }
        fclose($f);
    }

    //转换效果不理想，直接用老的，删除新的
    //  cd /mnt/f/doc/bfcode/porter/app; ./app homepc/file markOld --env=dev0
    public function markOld()
    {
        echo "\n 转换效果不理想，直接重命名老的源文件，删除新的转化结果, 继续请输入[  mei cuo ]\n";
        $stdin = trim(fgets(STDIN));

        if ($stdin !== 'mei cuo')
        {
            die("请确认后再执行");
        }
        $input_file = '/mnt/f/tmp_mark_old.txt';
        if (!is_file($input_file))
        {
            throw new \Exception("csv file error:{$input_file}");
        }
        $f        = fopen($input_file, 'r');
        $fileLine = 0;
        while (!feof($f))
        {
            $fileLine++;
            $old_file_name = trim(fgets($f));
            if (empty($old_file_name))
                continue;
            if (strstr($old_file_name, '//'))
            {
                echo "\nskip {$fileLine} {$old_file_name}\n";
                continue;
            }

            $new_file_name   = preg_replace('/(\.\w+)$/i', '~1.mp4', $old_file_name);
            $final_file_name = preg_replace('/(\.\w+)$/i', ' _final_ $1', $old_file_name);

            $new_file_exist_flag = 'no';
            $old_file_size       = 0;
            $new_file_size       = 0;
            $delete_flag         = '<';


            echo "\n{$fileLine} old:{$old_file_name} new:{$new_file_name}\n";
            if (is_file($old_file_name) && is_writable($old_file_name))
            {
                $old_file_handle = fopen($old_file_name, "r");
                $old_file_fstat  = fstat($old_file_handle);
                fclose($old_file_handle);
                $old_file_size = intval($old_file_fstat["size"] / 1024 / 1024);

                if (is_file($new_file_name) && is_writable($new_file_name))
                {
                    $new_file_exist_flag = 'yes';
                    $new_file_handle     = fopen($new_file_name, "r");
                    $new_file_fstat      = fstat($new_file_handle);
                    fclose($new_file_handle);
                    $new_file_size = intval($new_file_fstat["size"] / 1024 / 1024);
                    if ($new_file_size >= $old_file_size)
                    {
                        $delete_flag = '>=';
                    }
                    else
                    {
                        echo "\n error: {$new_file_name}";
                    }
                }
                else
                {
                    echo "\n new file not exist: {$new_file_name}";
                }
            }
            else
            {
                echo "\n old file not exist:{$old_file_name}";
            }
            echo "\n old:{$old_file_size}  [  {$delete_flag}  ] new: {$new_file_exist_flag} {$new_file_size}\n ";
            if ($delete_flag === '>=')
            {
                echo "\n{$final_file_name}";
                rename($old_file_name, $final_file_name);
                unlink($new_file_name);
            }

        }
        fclose($f);
    }

    // cd /mnt/f/doc/bfcode/porter/app; ./app homepc/file getDeleteList --env=dev0
    public function getDeleteList()
    {
        $input_file = '/mnt/f/tmp_pre_name.txt';
        if (!is_file($input_file))
        {
            throw new \Exception("csv file error:{$input_file}");
        }
        $f                = fopen($input_file, 'r');
        $fileLine         = 0;
        $old_biggers      = ["\n"];
        $new_biggers      = ["\n"];
        $nochange_biggers = ["\n"];

        while (!feof($f))
        {
            $fileLine++;
            $file_name = trim(fgets($f));
            if (empty($file_name))
                continue;
            $src_name = '/mnt/f/tmp2/' . $file_name;
            $new_name = preg_replace('/(\.\w+)$/i', '~1.mp4', $src_name);
            echo "\n{$fileLine} \n";
            if (is_file($src_name) && is_writable($src_name))
            {
                $old_file_handle = fopen($src_name, "r");
                //获取文件的统计信息
                $old_file_fstat = fstat($old_file_handle);
                // echo "文件名：".basename($new_name)."<br>";
                // echo "文件大小：".round($fstat["size"]/1024/1024,2)."Mb\n";
                //echo "最后修改时间：".date("Y-m-d h:i:s",$fstat["mtime"])."\n";
                // echo "create时间：".date("Y-m-d h:i:s",$fstat["ctime"])."\n";
                fclose($old_file_handle);
                $old_size = intval($old_file_fstat["size"] / 1024 / 1024);

                echo "\n src ok {$src_name} {$old_size}.Mb";
                if (is_file($new_name) && is_writable($new_name))
                {

                    $new_file_handle = fopen($new_name, "r");
                    //获取文件的统计信息
                    $new_file_fstat = fstat($new_file_handle);
                    fclose($new_file_handle);
                    $new_size = intval($new_file_fstat["size"] / 1024 / 1024);
                    echo "\n newfile ok {$new_name} {$new_size}.Mb";

                    $flag_str = '';
                    if ($new_size > $old_size)
                    {
                        $tmp_index     = (count($new_biggers) - 1) / 2;
                        $flag_str      = "//index:{$tmp_index} old:{$old_size} < new:{$new_size}  fail ";
                        $new_biggers[] = $src_name;
                        $new_biggers[] = $flag_str;
                    }
                    if ($new_size < $old_size)
                    {
                        $tmp_index     = (count($old_biggers) - 1) / 2;
                        $flag_str      = "//index:{$tmp_index} old:{$old_size} > new:{$new_size}  nice ";
                        $old_biggers[] = $src_name;
                        $old_biggers[] = $flag_str;

                    }
                    if ($new_size === $old_size)
                    {
                        $tmp_index          = (count($nochange_biggers) - 1) / 2;
                        $flag_str           = "//index:{$tmp_index} old:{$old_size} === new:{$new_size}";
                        $nochange_biggers[] = $src_name;
                        $nochange_biggers[] = $flag_str;
                    }
                    echo "\n{$flag_str}\n";
                }
                else
                {
                    echo "\n dst not exist {$new_name}";
                }

            }
            else
            {
                echo "\n src not exist {$src_name}";
            }

        }
        fclose($f);

        echo "\n\n\n //要查看 新文件更大 ??? \n\n\n";
        $stdin = trim(fgets(STDIN));

        if ($stdin === 'yes')
        {
            echo join("\n", $new_biggers);
        }


        echo "\n\n\n //要查看 老文件更大 ??? \n\n\n";
        $stdin = trim(fgets(STDIN));

        if ($stdin === 'yes')
        {
            echo join("\n", $old_biggers);
        }
        echo "\n";

        echo "\n\n\n //要查看 没改变大小的吗 ??? \n\n\n";
        $stdin = trim(fgets(STDIN));

        if ($stdin === 'yes')
        {
            echo join("\n", $nochange_biggers);
        }
        echo "\n";

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
                    Sys::app()->getPrinter()->tabEcho("无文件 移除");
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
    public function removeHasFormatedOldFile()
    {
        Sys::app()->initPrinter();
        Sys::app()->getPrinter()->setBaseTabNumber(0)->newTabEcho('init', '扫描 格式化后文件');
        $copyDir = $this->inputBox->tryGetString('formated_dir');
        if (!is_dir($copyDir))
        {
            die("\nnot dir:{$copyDir}\n");
        }
        $oldDir = $this->inputBox->tryGetString('old_dir');
        if (!is_dir($oldDir))
        {
            die("\nnot dir:{$oldDir}\n");
        }
        $this->isPreivew = !($this->inputBox->tryGetString('preview') === 'no');

        $tmp_ar      = scandir($copyDir);
        $copy_kw_map = [];
        foreach ($tmp_ar as $str)
        {
            if (strstr($str, '.mp4'))
            {

                $ar = [];
                preg_match('/^(.*?)\[(.*?)\]\.mp4$/isU', $str, $ar);
                // var_dump($str, $ar);
                if (count($ar) === 3)
                {
                    $s               = trim($ar[1]);
                    $copy_kw_map[$s] = $str;
                }
            }
        }
        echo "\n";
        foreach ($copy_kw_map as $s => $str)
        {
            // echo "{$s} {$str}\n";
        }
        var_dump($copy_kw_map);
        echo "\n";
        $source_files = scandir($oldDir);
        $cnt          = count($source_files);
        foreach ($source_files as $source_file)
        {
            echo "\n{$source_file}\n";
            $ar = [];
            preg_match('/^(.*?)\.mp4$/isU', $source_file, $ar);
            // var_dump($str,$ar);


            if (count($ar) === 2)
            {
                $kw = trim($ar[1]);

                // $kw = explode('_ver_', $source_file)[0];
                if (isset($copy_kw_map[$kw]))
                {
                    $matched = $copy_kw_map[$kw];
                    echo "match:\n{$matched}\n input command: [y]\n";
                    if ($this->isPreivew)
                    {
                        $stdin = trim(fgets(STDIN));
                        if ($stdin === 'y')
                        {
                            unlink("{$oldDir}/{$source_file}");
                            echo "del\n";
                        }
                        else
                        {
                            echo "不删除,跳过\n";
                        }
                    }
                    else
                    {
                        unlink("{$oldDir}/{$source_file}");
                        echo "auto del\n";
                    }
                }
                else
                {
                    echo "NOT MATCH:{$source_file}\n";
                }
            }
            else
            {
                echo "SKIP:{$source_file}\n";
            }
        }


    }

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

    public function adv_rm()
    {
        // '/mnt/f/tmp2/format _N_V_2023_02_02_/wait/dir/'
        $dstDir = $this->inputBox->getNotEmptyString('dir');
        $dirs   = [];
        exec("ls -d '{$dstDir}'/* ", $dirs);
        // var_dump($dirs);
        $i = 0;
        foreach ($dirs as $dir)
        {
            $i++;
            $get_old_cmd = "find  '{$dir}'   -maxdepth 2 -type f|grep -v FFOutput";
            $get_new_cmd = "find  '{$dir}'   -maxdepth 2 -type f|grep  FFOutput";

            $old_files = [];
            $new_files = [];

            exec($get_old_cmd, $old_files);
            exec($get_new_cmd, $new_files);
            echo "\n{$dir}\n\n";
            var_dump($old_files);
            echo "\n";
            var_dump($new_files);
            echo "\n";
            $new_files_cnt = count($new_files);
            if ($new_files_cnt >= count($old_files))
            {
                foreach ($old_files as $old_file)
                {
                    exec("rm -f '{$old_file}'");
                }
                $old_files2 = [];
                exec($get_old_cmd, $old_files2);
                if (count($old_files2) === 0)
                {
                    exec("mv '{$dir}'/FFOutput/* '{$dir}'");
                }
                else
                {
                    echo "\nMV FAIL\n";
                }

            }
            else
            {
                echo "\nNO new files\n";
            }

            $new_files2 = [];
            exec($get_new_cmd, $new_files2);
            if (count($new_files2) === 0)
            {
                $rmdir_cmd = "rmdir  '{$dir}'/FFOutput";
                echo "\n{$rmdir_cmd}\n";
                exec($rmdir_cmd);
            }
            else
            {
                echo "\nRMDIR FAIL\n";
            }
        }
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

    public function getVideosInfo()
    {
        $dir = $this->inputBox->tryGetString('dir');
        if (empty($dir))
        {
            $dir = '/mnt/f/tmp2/tmp';

        }
        $log_dir = "{$dir}/videos_info";
        if (!is_file($log_dir))
        {
            exec("mkdir -p -m 777 '{$log_dir}'");
        }
        $cmd = "find '{$dir}' -type f|grep -v '.png'|grep -v '.jpg'|grep -v '.gif'";
        exec($cmd, $ar);
        //var_dump($ar);
        $flag = date('Ymd-His_', time());
        foreach ($ar as $i => $filepath)
        {
            echo "{$i} {$filepath}\n";
            if (strstr($filepath, 'VIF_'))
            {
                continue;
            }
            exec("ffprobe -hide_banner  -v panic  -show_entries format=duration,size,bit_rate,filename -select_streams v:0 -show_entries stream=height,width -print_format json '{$filepath}'> '{$log_dir}/{$flag}-{$i}.json'");
        }
        echo "\n";
        $get_json_files_cmd = "find '{$log_dir}' -type f";
        exec($get_json_files_cmd, $json_files_ar);
        $bit_rate_min = 1689600;//1650*1024
        foreach ($json_files_ar as $i => $json_file)
        {
            echo "{$i}:{$json_file}\n";
            $info       = json_decode(file_get_contents($json_file), true);
            $width      = intval($info['streams'][0]['width'] ?? 0);
            $height     = intval($info['streams'][0]['height'] ?? 0);
            $video_file = $info['format']['filename'] ?? '';
            if (file_exists($video_file) && isset($info['format']['bit_rate']) && $width > 0 && $height > 0)
            {
                $video_file = $info['format']['filename'];
                //   echo json_encode($info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $file_info = pathinfo($video_file);
                $dirname   = $file_info['dirname'];
                $filename  = $file_info['filename'];
                $ext       = $file_info['extension'];
                $timeint   = intval(date("YmdHis", time()) . $i);
                $uniq      = "_uniq{$timeint}_";


                echo "{$video_file}  [{$width}X{$height}] \n";

                $flags = ["VIF_{$height}P"];
                if ($info['format']['bit_rate'] > $bit_rate_min)
                {
                    $flags[] = "H";
                }
                else
                {
                    $flags[] = "LOW";
                }
                if ($width > $height)
                {
                    $flags[] = "TV";
                }
                else
                {
                    $flags[] = "PHONE";
                }
                $flag         = join('_', $flags);
                $new_filename = "{$dirname}/{$filename} {$flag}_ {$uniq}.{$ext}";
                echo $new_filename;
                rename($video_file, $new_filename);
            }
            else
            {
                var_dump($info);
                var_dump(file_exists($video_file));
                echo "\n!!!!!!!!!!!!!!!!!ERROR !!!!!!!!!!!\n";
            }

            unlink($json_file);
            echo "\n\n";
        }

    }

    public function reFFmpeg()
    {
        die('xxx');
        $dir = $this->inputBox->tryGetString('dir');
        if (empty($dir))
        {
            $dir = '/mnt/f/tmp2/format _N_V_1__/wait';
        }
        Sys::app()->initPrinter();

        $ver_ctrl = new VersionControl();
        $ver_ctrl::init();

        $get_fail_play_videos_cmd = "find '{$dir}' -type f |grep FFOutput|grep -v reFFmpeg";
        exec($get_fail_play_videos_cmd, $fail_play_video_paths);
        foreach ($fail_play_video_paths as $i => $fail_play_video_path)
        {
            $file_info = pathinfo($fail_play_video_path);
            $dirname   = $file_info['dirname'];
            $filename  = $file_info['filename'];
            $ext       = $file_info['extension'];
            $filename  = $ver_ctrl->formatNameWord('', $filename, $filename);
            $filename  = preg_replace('/VIF_\d+P_H/isU', 'VIF', $filename);

            $new_filename = "{$dirname}/{$filename} reFFmpeg.{$ext}";
            echo "{$i}/{$fail_play_video_path}\n{$new_filename}\n";
            //ffmpeg -i  1.mp4 -map_metadata 0 -c copy 2.mp4
            exec("ffmpeg  -hide_banner -i '{$fail_play_video_path}' -map_metadata 0 -c copy '{$new_filename}'");
            unlink($fail_play_video_path);
        }
    }

    public function tmp()
    {
        $get_filenames_cmd = "find '/mnt/f/tmp2/format _N_V_1__/wait' -type f |grep  reFFmpeg|grep VIF_H_";
        exec($get_filenames_cmd, $filenames);
        foreach ($filenames as $i => $filename)
        {
            $new_filename = str_replace('VIF_H_', 'VIF_', $filename);
            echo "{$i}/{$filename}\n{$new_filename}\n\n";
            //rename($filename, $new_filename);
        }
    }

    public function formatDirVideoNames($dir)
    {


        $cmd = "find '{$dir}' -type f|grep -v '.png'|grep -v '.jpg'|grep -v '.gif'|grep -v FFOutput";
        //  $cmd = "find '{$dir}' -type f|grep -v '.png'|grep -v '.jpg'|grep -v '.gif'|grep -v FFOutput|grep -v 'VIF_'|grep -v '.json'";

        exec($cmd, $ar);
        //var_dump($ar);

        foreach ($ar as $i => $filepath)
        {
            $file_info = pathinfo($filepath);
            // var_dump($file_info);
            $ext         = $file_info['extension'];
            $filenameStr = $file_info['filename'];
            $filedir     = $file_info['dirname'];
            echo "src:{$i} {$filepath}\n";
            $fullFilePath = $filepath;
            if (0 && preg_match('/^-flag-.*-flag.*?/isU', $filenameStr))
            {

            }
            else
            {
                //VIF_1080P_H_TV_ _uniq20250317193249352025年  PANS重磅 颜值天花板大尺度 希希 酒店私拍全程粉嫩的鲍鱼在情趣渔网下显露很劲爆的一期 _ _N_V_1_

                $newfilenameStr = $filenameStr;
                if (strstr($filenameStr, '-flag-'))
                {

                }
                else
                {

                    $flag = '-flag-' . date('mdHis') . "-{$i}-" . rand(100, 999) . "-flag";

                    $newfilenameStr = preg_replace('/^([\-\s\d]+)/isU', '', $newfilenameStr);
                    $newfilenameStr = "{$flag} {$newfilenameStr}";

                }
                $newfilenameStr = trim(str_replace(['#', '?', '|', '\\', ' _ ', 'HK', '⚫️', '��'], '', $newfilenameStr));

                $newfilenameStr = preg_replace('/(\.\.mp4\.mp4)/isU', '.mp4', $newfilenameStr);

                $newfilenameStr = preg_replace('/(_LOW_|_H_)/isU', '', $newfilenameStr);
                $newfilenameStr = preg_replace('/(TV_|PHONE_)/isU', '', $newfilenameStr);
                $newfilenameStr = preg_replace('/(_uniq\d+)/isU', '', $newfilenameStr);
                $newfilenameStr = preg_replace('/(_N_[\w\d_]+\d+)/isU', '', $newfilenameStr);
                $newfilenameStr = preg_replace('/^(2?025\d+_)/isU', '', $newfilenameStr);
                $newfilenameStr = trim(str_replace(['_.mp4', '.��', '.ts', '.mp4'], '', $newfilenameStr));
                chmod($filedir, 0777);
                $newFullFilePath = "{$filedir}/{$newfilenameStr}.{$ext}";

                // var_dump($filenameStr);
                //  var_dump(preg_match('/^flag-\d+-\d+-.*?/isU', $newfilenameStr));
                if ($newFullFilePath !== $fullFilePath)
                {
                    echo "res:{$i} {$newFullFilePath}\n\n";
                    rename($fullFilePath, $newFullFilePath);
                }
            }

        }


    }


    public function make_task()
    {

        $dst_flag = $this->inputBox->tryGetString('dst_flag');
        $dir      = $this->inputBox->tryGetString('dir');

        if (empty($dir))
        {
            $dir = '/mnt/f/tmp2/format/wait/php';
        }
        chmod($dir, 0777);

        //$sqllite=new  \SQLite3("{$dir}/db.sqlite");
        //die;


        $this->formatDirVideoNames($dir);


        $isAsssign            = false;
        $dstFlagCommonMatches = [];
        preg_match('/^(265)_(\w+)H(\d+[MK]+)(\d+)FPS$/', $dst_flag, $dstFlagCommonMatches);
        if (count($dstFlagCommonMatches) === 0)
        {
            $dstFlagCommonMatches = [];
            preg_match('/^(265)_(\w+)_(\d+[MK]+)_(\d+)$/', $dst_flag, $dstFlagCommonMatches);
        }
        unset($dstFlagCommonMatches[0]);
        $dstFlagCommonMatches = array_values($dstFlagCommonMatches);

        $manualRate         = 0;
        $manualHeight       = 0;
        $manualFps          = 0;
        $MIN_RATE_K         = 1600;
        $MIN_FPS            = 24;
        $MIN_HEIGHT         = 720;
        $MAX_HEIGHT         = 720;
        $KEEP_SRC_VIDEO_KWS = ['黑丝', '天使', '星宫', '葵', '夏目', '二宫', '野野', '浦暖', 'ol', '本庄'];
        $EXPECT_TAG2CFG_KV  = [
            'NO_MOSAIC' => [
                'kws'    => 'tokyo,1pon,加勒比,carib,店长推荐,MKD,heyzo,hey,一本道,CWP,x-art,xart,ppv,kin8,MKBD,FC2,CWPBD,Blacked,',
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

        echo "\nINPUT:\n";
        echo json_encode(['height' => $manualHeight, 'rateK' => $manualRate, 'fps' => $manualFps], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);;
        echo "\n";


        Sys::app()->initPrinter();
        $printer  = Sys::app()->getPrinter();
        $ver_ctrl = new VersionControl();
        $ver_ctrl::init();

        $get_videos_cmd = "find '{$dir}' -type f |grep -v FFOutput|grep 'flag-'|grep -v flagFlag|grep -v jpg|grep -v jpeg";
        exec($get_videos_cmd, $video_paths);
        $kv = [];
        foreach ($video_paths as $video_path)
        {
            if (preg_match('/-flag-\d{10}-\d+-\d+-flag/isU', $video_path) > 0)
            {
                $val = intval(preg_replace('/(.*)\/-flag-(\d{10})-(\d+)-\d+-flag(.*)$/isU', '$2$3', $video_path));
                if (isset($kv[$val]))
                {
                    Sys::app()->getPrinter()->tabEcho("REPEAT_UNIQUE {$val} {$video_path}");
                    var_dump($kv[$val]);
                    die;
                }
                else
                {
                    $kv[$val] = $video_path;
                }
            }
            else
            {
                Sys::app()->getPrinter()->tabEcho("SKIP {$video_path}");
            }
        }
        $lower_video_path = strtolower($video_path);
        ksort($kv, SORT_NUMERIC);
        $video_paths = array_values($kv);
        $tmpStr      = json_encode($video_paths, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\nvideo paths:\n{$tmpStr}\n";
        $cnt = count($video_paths);

        $autoDelSrcFile = true;

        $willStr = json_encode($video_paths, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\nWill:\n{$willStr}\n";

        $ts       = time();
        $true_i   = 0;
        $true_cnt = count($video_paths);
        foreach ($video_paths as $i => $video_path)
        {
            $true_i++;
            echo "\n****************************************************************\n";
            //  echo "{$willStr}";
            echo "\ncurr mod: {$true_i}/{$true_cnt}   all mod:{$i}/{$cnt}  \n";
            $srcVideo = new VideoFile($video_path);
            $srcVideo->setFileVersionController($ver_ctrl);
            $srcVideo->setPrinter($printer);
            $srcVideo->initFilename();
            // var_dump($srcVideo->getHalfTrimFlagFullPath(), $srcVideo->getFullTrimFlagFullPath());die;
            $ext = strtolower($srcVideo->ext);
            if (in_array($ext, ['ts', 'mkv'], true))
            {
                $tmpMp4File = "{$srcVideo->dir}/{$srcVideo->baseName}.mp4";
                $this->convFuckType2Mp4($ext, $srcVideo->fullPath, $tmpMp4File);
                $flagRawFilename = $srcVideo->getHalfTrimFlagFullPath();
                rename($srcVideo->fullPath, $flagRawFilename);
                usleep(1000);
                $ext        = 'mp4';
                $video_path = $tmpMp4File;
                $srcVideo   = new VideoFile($tmpMp4File);
                $srcVideo->setFileVersionController($ver_ctrl);
                $srcVideo->setPrinter($printer);
                $srcVideo->initFilename();
                // var_dump($srcVideo->getHalfTrimFlagFullPath(), $srcVideo->getFullTrimFlagFullPath());//

            }
            $dirname       = $srcVideo->dir;
            $filename      = $srcVideo->baseName;
            $filenameLower = strtolower($filename);

            $srcVideo->setPrinter($printer)->setFileVersionController($ver_ctrl);

            // var_dump($video_info);
            $fps = 0;

            if ($srcVideo->initVideoInfo() === false)
            {
                echo "ERROR {$video_path}\n";
                continue;
            }

            echo "\nwidth:{$srcVideo->width} height:{$srcVideo->height} fps:{$srcVideo->fps} bit_rate:{$srcVideo->rateK} duration:{$srcVideo->durationSeconds}\n****************************************************************\n";
            if (empty($srcVideo->fps) || empty($srcVideo->width) || empty($srcVideo->height) || empty($srcVideo->rateK))
            {
                echo "ERROR {$video_path}\n";
                continue;
            }

            $ffCmd = new ffCmd($srcVideo);

            $isPhone = $srcVideo->isPhone;

            $isLowBitRate = $srcVideo->rateK < $MIN_RATE_K;

            $srcVideo->initFilename();
            $dirname  = $srcVideo->dir;
            $filename = $srcVideo->baseName;
            $ext      = strtolower($srcVideo->ext);

            if (!is_dir("{$dirname}/FFOutput"))
            {
                exec("mkdir -p -m 777 '{$dirname}/FFOutput'");
            }

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
                    if (strstr($filenameLower, $kw) || strstr($lower_video_path, $kw))
                    {
                        $tmpMatches[] = $kw;
                    }
                }
                foreach ($cfg['pregs'] as $pattern)
                {
                    if (preg_match($pattern, $filename) || preg_match($pattern, $lower_video_path))
                    {
                        $tmpMatches[] = $pattern;
                    }
                }
                if (count($tmpMatches) > 0)
                {
                    $expectFps    = $cfg['expect']['fps'];
                    $expectRateK  = $cfg['expect']['rate'];
                    $expectHeight = $cfg['expect']['height'];
                    echo "\nKW_EXEPCT:[$tag] h:{$expectHeight} rate:{$expectRateK} fps:{$expectFps}" . join(' | ', $tmpMatches) . "\n";
                    break;
                }
            }

            if ($manualHeight > 0 && $manualHeight > $expectHeight)
            {
                $expectHeight = $manualHeight;
            }


            if ($manualFps > 0 && $manualFps > $expectFps)
            {
                $expectFps = $manualFps;
            }


            if ($manualRate > 0 && $manualRate > $expectRateK)
            {
                $expectRateK = $manualRate;
            }


            if ($expectRateK > $srcVideo->rateK)
            {
                if (strstr($filename, 'ffCopy'))
                {
                    echo "\nHAS CONVED:{$filename}";
                    continue;
                }
                if ($srcVideo->fps < $MIN_FPS)
                {
                    $ffCmd->dstFlag = 'ffCopy';
                }
                else
                {
                    $ffCmd->dstMaxRateK = intval($srcVideo->rateK * 0.9);
                }
            }
            else
            {
                $ffCmd->dstMaxRateK = $expectRateK;
            }

            if ($ffCmd->dstFlag === 'ffCopy')
            {
                $asssignFlag = $ffCmd->dstFlag;
            }
            else
            {
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
                if ($isAsssign === true)
                {
                    //list($mp4, $hd, $rateStr, $fps) = $dstFlagMatches;

                    if ($expectHeight > $srcVideo->height)
                    {
                        $flags['height'] = $srcVideo->height;;
                    }
                }
                $asssignFlag = join('_', $flags);
            }

            //var_dump($filename);
            $filename = preg_replace('/^(-flag[\d\-]+flag)/isU', '$1Flag-', $filename);
            //  $tmp_ar=[];
            // preg_match('/^(-flag[\d\-]+flag)/isU',$filename,$tmp_ar);
            //  var_dump($tmp_ar);

            //var_dump($asssignFlag, $new_file_flag);

            $new_filename = "{$dirname}/{$filename} _NC_ {$asssignFlag} _FF_.mp4";

            $ffCmd->loadDstFile(new VideoFile($new_filename));
            $ffCmd->dst->setFileVersionController($ver_ctrl);
            $ffCmd->dst->setPrinter($printer);
            $ts1   = time();
            $date1 = date('Y-m-d H：i：s', $ts1);
            echo "[$date1] {$i}/{$cnt} \n{$video_path}\n->:\n{$new_filename}\n";

            // var_dump($new_filename);
            //$this->convAction($new_file_flag, $video_path, $new_filename, $video_max_bitrateK_set, $tmpMatches);
            echo json_encode($ffCmd->getDstInfo(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);


        }
    }

    public function conv()
    {

        $dst_flag = $this->inputBox->tryGetString('dst_flag');
        $dir      = $this->inputBox->tryGetString('dir');
        $mod_str  = $this->inputBox->tryGetString('mod');// 5,1
        /** @var  bool 自动处理 FPS */
        $autoFps = true;
        if ($this->inputBox->tryGetString('fps') === 'no')
        {
            $autoFps = false;
        }
        /** @var  bool 自动处理 码率 */
        $autoReate = true;
        if ($this->inputBox->tryGetString('rate') === 'no')
        {
            $autoReate = false;
        }
        if (empty($dir))
        {
            $dir = '/mnt/f/tmp2/format/wait/php';
        }
        chmod($dir, 0777);

        //$sqllite=new  \SQLite3("{$dir}/db.sqlite");
        //die;

        $mod    = 0;
        $modv   = 0;
        $tmp_ar = explode(',', $mod_str);
        if (count($tmp_ar) === 2)
        {
            $mod  = intval($tmp_ar[0]);
            $modv = intval($tmp_ar[1]);
        }
        if ($mod === 0 || $modv === 0)
        {
            $this->formatDirVideoNames($dir);
        }


        $isAsssign            = false;
        $dstFlagCommonMatches = [];
        preg_match('/^(265)_(\w+)H(\d+[MK]+)(\d+)FPS$/', $dst_flag, $dstFlagCommonMatches);
        if (count($dstFlagCommonMatches) === 0)
        {
            $dstFlagCommonMatches = [];
            preg_match('/^(265)_(\w+)_(\d+[MK]+)_(\d+)$/', $dst_flag, $dstFlagCommonMatches);
        }
        unset($dstFlagCommonMatches[0]);
        $dstFlagCommonMatches = array_values($dstFlagCommonMatches);

        $manualRate         = 0;
        $manualHeight       = 0;
        $manualFps          = 0;
        $MIN_RATE_K         = 1600;
        $MIN_FPS            = 24;
        $MIN_HEIGHT         = 720;
        $MAX_HEIGHT         = 720;
        $KEEP_SRC_VIDEO_KWS = ['黑丝', '天使', '星宫', '葵', '夏目', '二宫', '野野', '浦暖', 'ol', '本庄'];
        $EXPECT_TAG2CFG_KV  = [
            'NO_MOSAIC' => [
                'kws'    => 'tokyo,1pon,加勒比,carib,店长推荐,MKD,heyzo,hey,一本道,CWP,x-art,xart,ppv,kin8,MKBD,FC2,CWPBD,Blacked,',
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


        if (count($dstFlagCommonMatches) === 4)
        {
            $isAsssign    = true;
            $manualRate   = intval(str_replace(['K', 'M'], ['', '000'], $dstFlagCommonMatches[2]));
            $manualHeight = intval($dstFlagCommonMatches[1]);
            $manualFps    = intval($dstFlagCommonMatches[3]);
            if ($manualRate > $MIN_RATE_K)
            {
                $MIN_RATE_K = $manualRate;
            }

        }
        echo "\nINPUT:\n";
        echo json_encode(['height' => $manualHeight, 'rateK' => $manualRate, 'fps' => $manualFps], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);;
        echo "\n";


        Sys::app()->initPrinter();
        $printer  = Sys::app()->getPrinter();
        $ver_ctrl = new VersionControl();
        $ver_ctrl::init();

        $get_videos_cmd = "find '{$dir}' -type f |grep -v FFOutput|grep 'flag-'|grep -v flagFlag|grep -v jpg|grep -v jpeg";
        exec($get_videos_cmd, $video_paths);
        $kv = [];
        foreach ($video_paths as $video_path)
        {
            if (preg_match('/-flag-\d{10}-\d+-\d+-flag/isU', $video_path) > 0)
            {
                $val = intval(preg_replace('/(.*)\/-flag-(\d{10})-(\d+)-\d+-flag(.*)$/isU', '$2$3', $video_path));
                if (isset($kv[$val]))
                {
                    Sys::app()->getPrinter()->tabEcho("REPEAT_UNIQUE {$val} {$video_path}");
                    var_dump($kv[$val]);
                    die;
                }
                else
                {
                    $kv[$val] = $video_path;
                }
            }
            else
            {
                Sys::app()->getPrinter()->tabEcho("SKIP {$video_path}");
            }
        }
        $lower_video_path = strtolower($video_path);
        ksort($kv, SORT_NUMERIC);
        $video_paths = array_values($kv);
        $tmpStr      = json_encode($video_paths, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\nvideo paths:\n{$tmpStr}\n";
        $cnt = count($video_paths);

        $autoDelSrcFile = true;
        if ($mod > 1)
        {
            $wills = [];
            $skips = [];
            foreach ($video_paths as $i => $video_path)
            {
                if ($i % $mod === $modv)
                {
                    $wills[$i] = $video_path;
                }
                else
                {
                    $skips[$i] = $video_path;
                }
            }
            echo "\nSkip:\n";
            echo json_encode($skips, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $video_paths = $wills;

        }
        $willStr = json_encode($video_paths, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\nWill:\n{$willStr}\n";
        if ($mod > 1)
        {
            echo "\n";
            for ($i = 30; $i > 0; $i--)
            {
                echo "$i,";
                sleep(1);
            }
            echo "\n";
        }

        $ts       = time();
        $true_i   = 0;
        $true_cnt = count($video_paths);
        foreach ($video_paths as $i => $video_path)
        {
            $true_i++;
            echo "\n****************************************************************\n";
            //  echo "{$willStr}";
            echo "\ncurr mod: {$true_i}/{$true_cnt}   all mod:{$i}/{$cnt}  \n";
            $srcVideo = new VideoFile($video_path);
            $srcVideo->setFileVersionController($ver_ctrl);
            $srcVideo->setPrinter($printer);
            $srcVideo->initFilename();
            // var_dump($srcVideo->getHalfTrimFlagFullPath(), $srcVideo->getFullTrimFlagFullPath());die;
            $ext = strtolower($srcVideo->ext);
            if (in_array($ext, ['ts', 'mkv'], true))
            {
                $tmpMp4File = "{$srcVideo->dir}/{$srcVideo->baseName}.mp4";
                $this->convFuckType2Mp4($ext, $srcVideo->fullPath, $tmpMp4File);
                $flagRawFilename = $srcVideo->getHalfTrimFlagFullPath();
                rename($srcVideo->fullPath, $flagRawFilename);
                usleep(1000);
                $ext        = 'mp4';
                $video_path = $tmpMp4File;
                $srcVideo   = new VideoFile($tmpMp4File);
                $srcVideo->setFileVersionController($ver_ctrl);
                $srcVideo->setPrinter($printer);
                $srcVideo->initFilename();
                // var_dump($srcVideo->getHalfTrimFlagFullPath(), $srcVideo->getFullTrimFlagFullPath());//

            }
            $dirname       = $srcVideo->dir;
            $filename      = $srcVideo->baseName;
            $filenameLower = strtolower($filename);

            $srcVideo->setPrinter($printer)->setFileVersionController($ver_ctrl);

            // var_dump($video_info);
            $fps = 0;

            if ($srcVideo->initVideoInfo() === false)
            {
                echo "ERROR {$video_path}\n";
                continue;
            }

            echo "\nwidth:{$srcVideo->width} height:{$srcVideo->height} fps:{$srcVideo->fps} bit_rate:{$srcVideo->rateK} duration:{$srcVideo->durationSeconds}\n****************************************************************\n";
            if (empty($srcVideo->fps) || empty($srcVideo->width) || empty($srcVideo->height) || empty($srcVideo->rateK))
            {
                echo "ERROR {$video_path}\n";
                continue;
            }

            $ffCmd = new ffCmd($srcVideo);

            $isPhone = $srcVideo->isPhone;

            $isLowBitRate = $srcVideo->rateK < $MIN_RATE_K;

            $srcVideo->initFilename();
            $dirname  = $srcVideo->dir;
            $filename = $srcVideo->baseName;
            $ext      = strtolower($srcVideo->ext);

            if (!is_dir("{$dirname}/FFOutput"))
            {
                exec("mkdir -p -m 777 '{$dirname}/FFOutput'");
            }

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
                    if (strstr($filenameLower, $kw) || strstr($lower_video_path, $kw))
                    {
                        $tmpMatches[] = $kw;
                    }
                }
                foreach ($cfg['pregs'] as $pattern)
                {
                    if (preg_match($pattern, $filename) || preg_match($pattern, $lower_video_path))
                    {
                        $tmpMatches[] = $pattern;
                    }
                }
                if (count($tmpMatches) > 0)
                {
                    $expectFps    = $cfg['expect']['fps'];
                    $expectRateK  = $cfg['expect']['rate'];
                    $expectHeight = $cfg['expect']['height'];
                    echo "\nKW_EXEPCT:[$tag] h:{$expectHeight} rate:{$expectRateK} fps:{$expectFps}" . join(' | ', $tmpMatches) . "\n";
                    break;
                }
            }

            if ($manualHeight > 0 && $manualHeight > $expectHeight)
            {
                $expectHeight = $manualHeight;
            }


            if ($manualFps > 0 && $manualFps > $expectFps)
            {
                $expectFps = $manualFps;
            }


            if ($manualRate > 0 && $manualRate > $expectRateK)
            {
                $expectRateK = $manualRate;
            }


            if ($expectRateK > $srcVideo->rateK)
            {
                if (strstr($filename, 'ffCopy'))
                {
                    echo "\nHAS CONVED:{$filename}";
                    continue;
                }
                if ($srcVideo->fps < $MIN_FPS)
                {
                    $ffCmd->dstFlag = 'ffCopy';
                }
                else
                {
                    $ffCmd->dstMaxRateK = intval($srcVideo->rateK * 0.9);
                }
            }
            else
            {
                $ffCmd->dstMaxRateK = $expectRateK;
            }

            if ($ffCmd->dstFlag === 'ffCopy')
            {
                $asssignFlag = $ffCmd->dstFlag;
            }
            else
            {
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
                if ($isAsssign === true)
                {
                    //list($mp4, $hd, $rateStr, $fps) = $dstFlagMatches;

                    if ($expectHeight > $srcVideo->height)
                    {
                        $flags['height'] = $srcVideo->height;;
                    }
                }
                $asssignFlag = join('_', $flags);
            }

            //var_dump($filename);
            $filename = preg_replace('/^(-flag[\d\-]+flag)/isU', '$1Flag-', $filename);
            //  $tmp_ar=[];
            // preg_match('/^(-flag[\d\-]+flag)/isU',$filename,$tmp_ar);
            //  var_dump($tmp_ar);

            //var_dump($asssignFlag, $new_file_flag);

            $new_filename = "{$dirname}/{$filename} _NC_ {$asssignFlag} _FF_.mp4";

            $ffCmd->loadDstFile(new VideoFile($new_filename));
            $ffCmd->dst->setFileVersionController($ver_ctrl);
            $ffCmd->dst->setPrinter($printer);
            $ts1   = time();
            $date1 = date('Y-m-d H：i：s', $ts1);
            echo "[$date1] {$i}/{$cnt} \n{$video_path}\n->:\n{$new_filename}\n";

            // var_dump($new_filename);
            //$this->convAction($new_file_flag, $video_path, $new_filename, $video_max_bitrateK_set, $tmpMatches);
            echo json_encode($ffCmd->getDstInfo(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $ffCmd->delSrc = $autoDelSrcFile;

            foreach ($KEEP_SRC_VIDEO_KWS as $kw)
            {
                if (strstr($ffCmd->src->baseName, $kw))
                {
                    $ffCmd->delSrc = false;
                    break;
                }
            }
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
            $this->conv2Action($ffCmd);

            $ts2       = time();
            $date2     = date('Y-m-d H：i：s', $ts2);
            $cost_curr = $ts2 - $ts1;
            $cost_all  = $ts2 - $ts;
            echo "\nwidth:{$srcVideo->width} height:{$srcVideo->height} fps:{$srcVideo->fps} bit_rate:{$srcVideo->rateK} duration:{$srcVideo->durationSeconds}";
            echo "\n[$date2] curr mod: {$true_i}/{$true_cnt}   all mod:{$i}/{$cnt}  curr cost:{$cost_curr}  all cost:{$cost_all}\n";
        }
    }

    public function convFuckType2Mp4($type, $srcFile, $dstFile)
    {
        switch ($type)
        {

            case 'ts':
                $cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
  -map 0:v:0 -c:v:0 hevc_nvenc -vf \"fps=24\" \
  -map 0:a:0 -c:a aac -ac 2 -b:a 192k \
  -map 0:v:1? -c:v:1 copy -disposition:v:1 attached_pic \
  -map 0:d? -c:d copy \
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
            default:
                die("no_dst_flag [{$type}] !!!\n");
                break;
        }
        echo "\nconvFuckType2Mp4 CMD:\n{$cmd}\n";
        passthru($cmd);
    }

    public function conv2Action(ffcmd $ffcmd)
    {
        $srcFile = $ffcmd->src->fullPath;
        $dstFile = $ffcmd->dst->fullPath;

        if ($ffcmd->dstFlag === 'ffCopy')
        {
            $cmd = " ffmpeg -hide_banner -i '{$srcFile}' -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc  -c copy '{$dstFile}'";
        }
        else
        {
            $common_cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
-map 0:v:0 -c:v hevc_nvenc -crf 22 {maxrateStr} -bufsize 4000k \
{vfStr} -preset p7 \
-map 0:a? -c:a aac -ac 2 -b:a 192k \
-map 0:s? -c:s mov_text \
-map_metadata 0 -movflags use_metadata_tags \
'{$dstFile}'";
            $map        = ['{maxrateStr}' => '', '{vfStr}' => ''];
            if ($ffcmd->dstMaxRateK > 0)
            {
                $map['{maxrateStr}'] = "-maxrate {$ffcmd->dstMaxRateK}k";
            }
            $vfs = [];
            if ($ffcmd->dstMaxHeight > 0)
            {
                $vfs[] = "scale=-2:if(gte(ih\,{$ffcmd->dstMaxHeight})\,{$ffcmd->dstMaxHeight}\,ih)";
            }

            if ($ffcmd->dstFps > 0)
            {
                $vfs[] = "fps={$ffcmd->dstFps}";
            }
            if (count($vfs) > 0)
            {
                $tmp_str        = implode(",", $vfs);
                $map['{vfStr}'] = "-vf \"{$tmp_str}\"";
            }
            $cmd = str_replace(array_keys($map), array_values($map), $common_cmd);
        }

        echo "\nconv CMD:\n{$cmd}\n";
        passthru($cmd);


        $coverFile = '';
        $coverStr  = "";
        $file_info = pathinfo($dstFile);
        $dirname   = $file_info['dirname'];
        $filename  = $file_info['filename'];

        // 1. 获取视频时长
        $cmdDuration = "ffprobe -v error -show_entries format=duration -of csv=p=0 " . escapeshellarg($srcFile);
        $duration    = floatval(trim(shell_exec($cmdDuration)));
        $coverFile   = "{$dirname}/{$filename}.jpg";
        if ($duration <= 0)
        {
            echo("无法获取视频时长\n");
            return false;
        }

        // 2. 计算中点时间
        $mid = $duration / 2;

        // 3. 提取中点帧
        $cmdCover = sprintf('ffmpeg -hide_banner -ss %.3f -i %s -vframes 1 -q:v 2 %s -y', $mid, escapeshellarg($srcFile), escapeshellarg($coverFile));
        exec($cmdCover, $output, $ret);
        if ($ret !== 0)
        {
            echo("封面提取失败\n");
            @unlink($coverFile);
            usleep(1000);
        }


        if (file_exists($coverFile))
        {
            $srcFile          = $dstFile;
            $dstFile          = preg_replace('_NC_', '', $dstFile);
            $append_cover_cmd = "ffmpeg -hide_banner -i '{$srcFile}' -i '{$coverFile}' \
-map 0 -map 1:v -c copy -c:v:1 copy -disposition:v:1 attached_pic \
'{$dstFile}'";
            echo "\nappend cover CMD:\n{$append_cover_cmd}\n";
            passthru($append_cover_cmd);
            @unlink($coverFile);
            if (file_exists($dstFile))
            {
                @unlink($srcFile);

            }
            if ($ffcmd->delSrc)
            {
                $resFile = new VideoFile($dstFile);
                $resFile->setFileVersionController($ffcmd->src->fileVersionControl);
                $resFile->setPrinter($ffcmd->src->printer);
                $resFile->initFilename();
                if ($resFile->initVideoInfo())
                {
                    $trimPath = $resFile->getFullTrimFlagFullPath();
                    echo "\nfull trim path:{$trimPath}\n";
                    $tmpFile = new VideoFile($trimPath);
                    @rename($dstFile, "{$tmpFile->dir}/FFOutput/{$tmpFile->baseName}.{$tmpFile->ext}");
                    @unlink($ffcmd->src->fullPath);
                }
            }
        }
        //die;
    }

    public function convAction($type, $srcFile, $dstFile, $maxrateK = 0, $dstFlagMatches = [])
    {
        $cmd        = '';
        $maxrateStr = '';
        $settingMap = [
            'maxrateK'  => $maxrateK,
            'maxHeight' => 0,
            'fps'       => 0,
        ];
        if ($maxrateK > 0)
        {
            // $settingMap['{maxrate}'] = "-maxrate {$maxrateK}k";
        }


        switch ($type)
        {
            case '265_1600K':
                $cmd                    = " ffmpeg -hide_banner -i '{$srcFile}'  -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc -crf 22 -maxrate 1600k -bufsize 4000k -preset p7 -c:a copy '{$dstFile}'";
                $settingMap['maxrateK'] = 1600;
                break;
            case '265_1600K24FPS':
                $cmd                    = " ffmpeg -hide_banner -i '{$srcFile}'  -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc -crf 22 -maxrate 1600k -bufsize 4000k -vf \"fps=24\" -preset p7 -c:a copy '{$dstFile}'";
                $settingMap['maxrateK'] = 1600;
                $settingMap['fps']      = 24;
                break;
            case '265_24FPS':
                $cmd               = " ffmpeg -hide_banner -i '{$srcFile}'  -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc -crf 22 {$maxrateStr} -bufsize 4000k -vf \"fps=24\" -preset p7 -c:a copy '{$dstFile}'";
                $settingMap['fps'] = 24;
                break;
            case '265_2M':
                $cmd                    = " ffmpeg -hide_banner -i '{$srcFile}'  -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc -crf 22 -maxrate 2000k -bufsize 4000k -preset p7 -c:a copy '{$dstFile}'";
                $settingMap['maxrateK'] = 2000;

                break;
            case '265_3M':
                $cmd                    = " ffmpeg -hide_banner -i '{$srcFile}'  -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc -crf 22 -maxrate 3000k -bufsize 4000k -preset p7 -c:a copy '{$dstFile}'";
                $settingMap['maxrateK'] = 3000;

                break;
            case '265_720H1600K':
                $cmd                     = " ffmpeg -hide_banner -i '{$srcFile}'  -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc -crf 22 -maxrate 1600k -bufsize 4000k -vf \"scale=-2:'min(ih,720)'\" -preset p7 -c:a copy '{$dstFile}'";
                $settingMap['maxrateK']  = 1600;
                $settingMap['maxHeight'] = 720;
                break;
            case '265_720H1600K24FPS':
                $cmd                     = " ffmpeg -hide_banner -i '{$srcFile}'  -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc -crf 22 -maxrate 1600k -bufsize 4000k -vf \"scale=-2:'min(ih,720)',fps=24\" -preset p7 -c:a copy '{$dstFile}'";
                $settingMap['maxrateK']  = 1600;
                $settingMap['maxHeight'] = 720;
                $settingMap['fps']       = 24;

                break;
            case '265_720H2M24FPS':
                $cmd                     = " ffmpeg -hide_banner -i '{$srcFile}'  -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags  -c:v hevc_nvenc -crf 22 -maxrate 2000k -bufsize 4000k -vf \"scale=-2:'min(ih,720)',fps=24\" -preset p7 -c:a copy '{$dstFile}'";
                $settingMap['maxrateK']  = 2000;
                $settingMap['maxHeight'] = 720;
                $settingMap['fps']       = 24;
                break;
            case '265_720H2M30FPS':
                $cmd                     = " ffmpeg -hide_banner -i '{$srcFile}'  -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc -crf 22 -maxrate 2000k -bufsize 4000k -vf \"scale=-2:'min(ih,720)',fps=30\" -preset p7 -c:a copy '{$dstFile}'";
                $settingMap['maxrateK']  = 2000;
                $settingMap['maxHeight'] = 720;
                $settingMap['fps']       = 30;
                break;
            case '265_1080H1600K':
                $cmd                     = " ffmpeg -hide_banner -i '{$srcFile}'  -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc -crf 22 -maxrate 1600k -bufsize 4000k -vf \"scale=-2:'min(ih,1080)'\" -preset p7 -c:a copy '{$dstFile}'";
                $settingMap['maxrateK']  = 1600;
                $settingMap['maxHeight'] = 1080;
                break;
            case '265_1080H1600K24FPS':
                $cmd                     = " ffmpeg -hide_banner -i '{$srcFile}'  -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc -crf 22 -maxrate 1600k -bufsize 4000k -vf \"scale=-2:'min(ih,1080)',fps=24\" -preset p7 -c:a copy '{$dstFile}'";
                $settingMap['maxrateK']  = 1600;
                $settingMap['maxHeight'] = 1080;
                $settingMap['fps']       = 24;

                break;
            case '265_1080H2M24FPS':
                $cmd                     = " ffmpeg -hide_banner -i '{$srcFile}'  -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc -crf 22 -maxrate 2000k -bufsize 4000k -vf \"scale=-2:'min(ih,1080)',fps=24\" -preset p7 -c:a copy '{$dstFile}'";
                $settingMap['maxrateK']  = 2000;
                $settingMap['maxHeight'] = 1080;
                $settingMap['fps']       = 24;
                break;
            case '265_1080H2M30FPS':
                $cmd                     = " ffmpeg -hide_banner -i '{$srcFile}'  -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc -crf 22 -maxrate 2000k -bufsize 4000k -vf \"scale=-2:'min(ih,1080)',fps=30\" -preset p7 -c:a copy '{$dstFile}'";
                $settingMap['maxrateK']  = 2000;
                $settingMap['maxHeight'] = 1080;
                $settingMap['fps']       = 30;

            case '265_1080H3M30FPS':
                $cmd                     = " ffmpeg -hide_banner -i '{$srcFile}' -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc -crf 22 -maxrate 3000k -bufsize 6000k -vf \"scale=-2:'min(ih,1080)',fps=30\" -preset p7 -c:a copy '{$dstFile}'";
                $settingMap['maxrateK']  = 3000;
                $settingMap['maxHeight'] = 1080;
                $settingMap['fps']       = 30;
                break;
            case 'ffCopy':
                $cmd = " ffmpeg -hide_banner -i '{$srcFile}' -map 0:v -c:v copy -map 0:a:0 -c:a aac -ac 2 -b:a 192k -map 0:d? -c:d copy -map 0:s? -c:s copy    -map_metadata 0 -movflags use_metadata_tags -c:v hevc_nvenc  -c copy '{$dstFile}'";
                unset($settingMap['fps']);
                break;
            case 'ts':
                $cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
  -map 0:v:0 -c:v:0 hevc_nvenc -vf \"fps=24\" \
  -map 0:a:0 -c:a aac -ac 2 -b:a 192k \
  -map 0:v:1? -c:v:1 copy -disposition:v:1 attached_pic \
  -map 0:d? -c:d copy \
  -map 0:s? -c:s copy \
  -map_metadata 0 -movflags use_metadata_tags \
  '{$dstFile}'";
                break;
            case 'mkv':
                $cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
  -map 0:v -c:v hevc_nvenc -preset p4 -b:v 1500k \
  -map 0:a:0 -c:a aac -ac 2 -b:a 192k \
  -map 0:d? -c:d copy \
  -map 0:s? -c:s copy \
  -map_metadata 0 -movflags +faststart \
  '{$dstFile}'";
                break;
            default:
                if (count($dstFlagMatches) === 4)
                {
                    list($mp4, $hd, $rateStr, $fps) = $dstFlagMatches;
                    $settingMap['maxrateK']  = intval(str_replace(['K', 'M'], ['', '000'], $rateStr));
                    $settingMap['maxHeight'] = intval($hd);
                    $settingMap['fps']       = intval($fps);
                }
                else
                {
                    die("no_dst_flag [{$type}] !!!\n");
                }

                break;
        }
        $tmp_count = array_count_values($settingMap);

        if (!isset($tmp_count[0]) || $tmp_count[0] < 3)
        {


            $common_cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
-map 0:v -c:v hevc_nvenc -crf 22 {maxrateStr} -bufsize 4000k  {vfStr} -preset p7 \
-map 0:a? -c:a aac -ac 2 -b:a 192k \
-map 0:s? -c:s mov_text \
-map 0:d? -c:d copy \
-map_metadata 0 -movflags use_metadata_tags \
'{$dstFile}'";
            $common_cmd = "ffmpeg -hide_banner -i '{$srcFile}' \
-map 0:v:0 -c:v hevc_nvenc -crf 22 {maxrateStr} -bufsize 4000k \
{vfStr} -preset p7 \
-map 0:a? -c:a aac -ac 2 -b:a 192k \
-map 0:s? -c:s mov_text \
-map_metadata 0 -movflags use_metadata_tags \
'{$dstFile}'";
            $map        = ['{maxrateStr}' => '', '{vfStr}' => ''];
            if ($settingMap['maxrateK'] > 0)
            {
                $map['{maxrateStr}'] = "-maxrate {$settingMap['maxrateK']}k";
            }
            $vfs = [];
            if ($settingMap['maxHeight'] > 0)
            {
                $vfs[] = "scale=-2:if(gte(ih\,{$settingMap['maxHeight']})\,{$settingMap['maxHeight']}\,ih)";
            }
            if (!isset($settingMap['fps']))
            {
                $settingMap['fps'] = 0;
            }
            if ($settingMap['fps'] > 0)
            {
                $vfs[] = "fps={$settingMap['fps']}";
            }
            if (count($vfs) > 0)
            {
                $tmp_str        = implode(",", $vfs);
                $map['{vfStr}'] = "-vf \"{$tmp_str}\"";
            }

            $cmd = str_replace(array_keys($map), array_values($map), $common_cmd);

        }

        echo "\nconv CMD:\n{$cmd}\n";
        passthru($cmd);


        $coverFile = '';
        $coverStr  = "";
        $file_info = pathinfo($dstFile);
        $dirname   = $file_info['dirname'];
        $filename  = $file_info['filename'];

        // 1. 获取视频时长
        $cmdDuration = "ffprobe -v error -show_entries format=duration -of csv=p=0 " . escapeshellarg($srcFile);
        $duration    = floatval(trim(shell_exec($cmdDuration)));
        $coverFile   = "{$dirname}/{$filename}.jpg";
        if ($duration <= 0)
        {
            echo("无法获取视频时长\n");
            return false;
        }

        // 2. 计算中点时间
        $mid = $duration / 2;

        // 3. 提取中点帧
        $cmdCover = sprintf('ffmpeg -hide_banner -ss %.3f -i %s -vframes 1 -q:v 2 %s -y', $mid, escapeshellarg($srcFile), escapeshellarg($coverFile));
        exec($cmdCover, $output, $ret);
        if ($ret !== 0)
        {
            echo("封面提取失败\n");
            @unlink($coverFile);
            usleep(1000);
        }


        if (file_exists($coverFile))
        {
            $srcFile          = $dstFile;
            $dstFile          = preg_replace('_NC_', '', $dstFile);
            $append_cover_cmd = "ffmpeg -hide_banner -i '{$srcFile}' -i '{$coverFile}' \
-map 0 -map 1:v -c copy -c:v:1 copy -disposition:v:1 attached_pic \
'{$dstFile}'";
            echo "\nappend cover CMD:\n{$append_cover_cmd}\n";
            passthru($append_cover_cmd);
            @unlink($coverFile);
            if (file_exists($dstFile))
            {
                @unlink($srcFile);
            }

        }
        //die;
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
        self::$rubbishWords     = explode(',', '[Thz.la],  ,『,』,【,】,【】,[,],〖,〗,《,》,@,顶级無碼,sex8.cc,guochan2048.com,Night24.com,2048社区,-big2048.com,SEX8.CC,Weagogo,kpxvs.com,miohot0428,jav20s8.com,Woxav.Com,fun2048.com,最新流出,最新,钻石级,❤️,推荐,会所尊享,❤,推荐,蜜桃,传媒,特辑,新作,SEX8.CC,爱剪辑,我的视频,无码破解,破解,独家爆料,#,~,「,」,[,],(,),（,）,Uncensored,uncensored, MP4 ,MP4-,MP4_,BVPP, _final_ ,_name_ver_2023_02_02,_N_V__ _N_V__,⚫️,_N_V_1_,✅,\\,\/,/,|,?,#,+');
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

    public function initVideoInfo()
    {
        $tmp     = [];
        $tmp_cmd = "ffprobe -hide_banner  -v panic  -show_entries format=duration,size,bit_rate,filename -select_streams v:0 -show_entries stream=height,width,nb_frames,avg_frame_rate,r_frame_rate  -print_format json '{$this->fullPath}'";
        $this->printer->tabEcho("info common CMD: \n$tmp_cmd\n");
        exec($tmp_cmd, $tmp);
        $video_info            = json_decode(join('', $tmp), true);
        $this->width           = intval($video_info['streams'][0]['width'] ?? 0);
        $this->height          = intval($video_info['streams'][0]['height'] ?? 0);
        $video_rate            = intval($video_info['format']['bit_rate'] ?? 0);
        $this->durationSeconds = 0;
        // var_dump($video_info);
        $this->fps = 0;
        if (isset($video_info['streams'][0]['nb_frames']) && isset($video_info['format']['duration']))
        {
            $this->durationSeconds = intval($video_info['format']['duration']);
            $this->fps             = intval($video_info['streams'][0]['nb_frames'] / $this->durationSeconds);
            // var_dump($fps);
        }
        else
        {
            $tmp_cmd = "ffprobe -hide_banner -v error \
  -v panic\
  -select_streams v:0 \
  -count_frames \
  -show_entries stream=codec_name,avg_frame_rate,r_frame_rate,width,height,nb_read_frames \
  -show_entries format=duration,bit_rate,size \
  -print_format json '{$this->fullPath}'";
            $this->printer->tabEcho("info special CMD: \n$tmp_cmd\n");
            $tmp = [];
            exec($tmp_cmd, $tmp);
            $video_info = json_decode(join('', $tmp), true);
            if (isset($video_info['streams'][0]['nb_read_frames']) && isset($video_info['format']['duration']))
            {
                $this->fps = intval($video_info['streams'][0]['nb_read_frames'] / $video_info['format']['duration']);
            }
        }
        $this->rateK = intval($video_rate / 1024);
        echo "\nwidth:{$this->width} height:{$this->height} fps:{$this->fps} bit_rate:{$this->rateK} duration:{$this->durationSeconds}\n****************************************************************\n";
        if (empty($this->fps) || empty($this->width) || empty($this->height) || empty($video_rate) || empty($this->rateK))
        {
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