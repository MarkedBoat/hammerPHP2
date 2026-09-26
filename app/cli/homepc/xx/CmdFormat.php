<?php


namespace cli\homepc\xx;

use cli\homepc\ScanedDir;
use cli\homepc\VersionControl;
use hammer\cli\CmdBase;
use hammer\param\DataBox;
use models\common\Def;
use hammer\param\Param;
use hammer\sys\Sys;
use models\ext\tool\CLIFormatter;
use models\ext\tool\CLIStrFormatter;
use models\ext\tool\Printer;
use models\ext\VicWord\Lib\VicWord;
use modules\dev\v1\api\KL_PC\xx\op\model\FileVideoInfo;
use modules\dev\v1\api\KL_PC\xx\op\model\LsCmdFileinfo;
use modules\dev\v1\api\KL_PC\xx\op\model\TaskSettingBox;
use modules\dev\v1\dao\video\FfmpegTask;

ini_set('memory_limit', '3072M');


class CmdFormat extends CmdBase
{


    /**
     * @var Printer
     */
    protected $printer = false;

    protected $rootDir = '';

    public function init()
    {
        parent::init();
        $this->printer = new  Printer();
    }

    public function test()
    {
        die('xxxx');
    }


    /**
     * cd /mnt/f/doc/bfcode/porter/app/; ./app  homepc/file formatName2 --env=dev0  --root_dir='/mnt/f/tmp2' --preview=no1
     * cd /mnt/f/doc/bfcode/porter/app/; ./app  homepc/file formatName2 --env=dev0  --root_dir='/mnt/f/tmp2/tmp' --preview=no
     * @throws \Exception
     */
    public function v1()
    {
        $printerOutState = true;
        Sys::app()->initPrinter();
        Sys::app()->getPrinter()->setBaseTabNumber(0)->newTabEcho('init', '初始化根目录');
        $this->rootDir = $this->inputBox->tryGetString('root_dir') ? $this->inputBox->tryGetString('root_dir') : '/mnt/f/tmp2/tmp';
        if (!is_dir($this->rootDir))
        {
            die("\nnot dir:{$this->rootDir}\n");
        }

        $isDebug = $this->inputBox->tryGetString('is_debug') === 'yes';
        if ($isDebug === false)
        {
            $isDebug = $this->inputBox->tryGetString('isDebug') === 'yes';
        }
        $isLsAll        = $this->inputBox->tryGetString('isLsAll') === 'yes';
        $isShowStrMatch = $this->inputBox->tryGetString('isShowStrMatch') === 'yes';
        $isPreview      = $this->inputBox->tryGetString('isPreview') === 'yes';

        /**
         * 去除文件名/目录名中紧邻 "/" 的空格，并批量重命名
         *
         * 用法：
         *   php trim_rename.php /path/to/dir            # 实际执行
         *   php trim_rename.php /path/to/dir --dry-run  # 只预览，不改动
         */

        $dry = false;

        $root = $this->rootDir;


        // 1) 用 find 取出所有条目（文件 + 目录）的绝对路径
        //    -depth：先输出子项再输出父项，保证重命名顺序正确（先改子，后改父）
        //    -print0：以 \0 分隔，兼容文件名里的空格、换行等特殊字符
        $cmd = 'find ' . escapeshellarg($root) . ' -depth -print0 2>/dev/null';
        $fp  = popen($cmd, 'r');
        if (!$fp)
        {
            fwrite(STDERR, "执行 find 失败\n");
            exit(1);
        }
        $raw = stream_get_contents($fp);
        pclose($fp);

        $paths = array_filter(explode("\0", $raw), 'strlen');

        if ($isLsAll)
        {
            var_export($paths);
        }

        $count = 0;
        $skip  = 0;

        // 2) 逐个处理
        foreach ($paths as $path)
        {
            if ($path === $root)
            {
                continue; // 根目录自身不动
            }

            $dir     = dirname($path);
            $base    = basename($path);
            $newBase = trim($base, " \t");   // 去掉文件名首尾空格（也就是 / 前后的空格）

            // 没有首尾空格，跳过
            if ($newBase === $base || $newBase === '')
            {
                continue;
            }

            // 注意：这里只改 basename，父目录部分保持原样，
            // 父目录自身的空格会在它自己那一轮被处理（-depth 保证父在子之后处理）
            $newPath = rtrim($dir, '/') . '/' . $newBase;

            if (file_exists($newPath))
            {
                fwrite(STDERR, "跳过（目标已存在）: {$path}\n");
                $skip++;
                continue;
            }

            if ($dry)
            {
                echo "[dry-run] {$path}\n       => {$newPath}\n";
            }
            else
            {
                if (@rename($path, $newPath))
                {
                    echo "重命名: {$path}\n     => {$newPath}\n";
                }
                else
                {
                    fwrite(STDERR, "失败: {$path}\n");
                    $skip++;
                    continue;
                }
            }
            $count++;
        }

        echo "\n完成：处理 {$count} 项";
        if ($skip)
        {
            echo "，跳过 {$skip} 项";
        }


        //  die;


        $cmdTask           = new CmdTask();
        $cmdTask->inputBox = new DataBox(['dir' => $this->rootDir,]);
        $cmdTask->mark();

        $taskM                 = new FfmpegTask();
        $id36toLsCmdFileinfoKV = $taskM->getId36KV($this->rootDir);


        $rubbishWords = explode(',', '[Thz.la],  ,『,』,【,】,【】,[,],〖,〗,《,》,@,顶级無碼,sex8.cc,guochan2048.com,Night24.com,2048社区,-big2048.com,SEX8.CC,Weagogo,kpxvs.com,miohot0428,jav20s8.com,Woxav.Com,fun2048.com,最新流出,最新,钻石级,❤️,推荐,会所尊享,❤,推荐,蜜桃,传媒,特辑,新作,SEX8.CC,爱剪辑,我的视频,无码破解,破解,独家爆料,#,~,「,」,[,],(,),（,）,Uncensored,uncensored, MP4 ,MP4-,MP4_,BVPP, _final_ ,_name_ver_2023_02_02,_N_V__ _N_V__,⚫️,_N_V_1_,✅,\\,\/,/,|,?,#,+,付費福利,超精品,重磅,独家更新,約炮大神,高价定制,自译征用,精品福利,精品,：,中文字幕,自译征用,新模型,AI增强,video,中配中字,独家泄密,珍藏级,无水原档,91原始精英博主,入駐推特,高端泄密流出');

        $rubishWordsFilename = __APP_DIR__ . '/config/file/xx_video/rubish_words.txt';
        if (file_exists($rubishWordsFilename))
        {
            $this->printer->tabEcho("load {$rubishWordsFilename}");

            $rubbishWords = explode("\n", file_get_contents($rubishWordsFilename));


        }
        else
        {
            $this->printer->tabEcho("not  {$rubishWordsFilename}");

        }
        $tmpIndex = array_search('/', $rubbishWords);
        if ($tmpIndex !== false)
        {
            unset($rubbishWords[$tmpIndex]);
        }
        $rubbishWordEmpty = array_fill(0, count($rubbishWords), ' ');
        if ($isShowStrMatch)
        {
            $this->printer->newTabEcho('rubishWords');
            $this->printer->tabEcho($rubbishWords);
            $this->printer->endTabEcho('rubishWords');
        }
        $i              = 0;
        $cnt            = count($id36toLsCmdFileinfoKV);
        $dir2id36toInfo = [];
        foreach ($id36toLsCmdFileinfoKV as $id36 => $lsCmdFileinfo)
        {
            if (!isset($dir2id36toInfo[$lsCmdFileinfo->albumNameDir]))
            {
                $dir2id36toInfo[$lsCmdFileinfo->albumNameDir] = [];
            }
            $dir2id36toInfo[$lsCmdFileinfo->albumNameDir][$id36] = $lsCmdFileinfo;
        }

        if ($isShowStrMatch)
        {
            $this->printer->tabEcho("dir => id36 => lsCmdFileinfo  \$dir2id36toInfo");
            $this->printer->tabEcho($dir2id36toInfo);
        }

        $oldDir2newDir2list = [];
        $this->printer->newTabEcho('oldDir2newDir2list', '将   dir => id36 => lsCmdFileinfo 处理成  old dir =>  new dir => files');
        foreach ($dir2id36toInfo as $albumDir => $id36toLsCmdFileinfoKV)
        {
            $this->printer->newTabEcho('oldDir', $albumDir);
            if (!isset($oldDir2newDir2list[$albumDir]))
            {
                $oldDir2newDir2list[$albumDir] = [];
            }
            $dirnameVideosCnt = count($id36toLsCmdFileinfoKV);
            $this->printer->tabEcho("old Dir:[{$albumDir}] VideosCnt:[{$dirnameVideosCnt}]");
            if ($dirnameVideosCnt === 1)
            {
                $this->printer->tabEcho('目录下只有一个video,直接提到根目录');
                if (!isset($oldDir2newDir2list[$albumDir]['']))
                {
                    $oldDir2newDir2list[$albumDir][''] = [];
                }
                foreach ($id36toLsCmdFileinfoKV as $id36 => $lsCmdFileinfo)
                {

                    $i++;
                    $this->printer->tabEcho("{$i}/{$cnt} {$lsCmdFileinfo->relativePath}");
                    $relativeFilename = str_replace("^{$id36}^.{$lsCmdFileinfo->ext}", '', $lsCmdFileinfo->relativePath);
                    //    $this->printer->tabEcho("$relativeFilename");
                    //$this->printer->
                    $trimedRelativeFilename = str_replace($rubbishWords, $rubbishWordEmpty, $relativeFilename);
                    $trimedRelativeFilename = str_replace($rubbishWords, $rubbishWordEmpty, $trimedRelativeFilename);
                    $trimedRelativeFilename = str_replace($rubbishWords, $rubbishWordEmpty, $trimedRelativeFilename);
                    $trimedRelativeFilename = str_replace($rubbishWords, $rubbishWordEmpty, $trimedRelativeFilename);
                    $trimedRelativeFilename = str_replace($rubbishWords, $rubbishWordEmpty, $trimedRelativeFilename);
                    $trimedRelativeFilename = str_replace($rubbishWords, $rubbishWordEmpty, $trimedRelativeFilename);
                    $trimedRelativeFilename = str_replace(['_'], [' '], $trimedRelativeFilename);
                    $trimedRelativeFilename = str_replace([' / ', ' /', '/ '], ['/', '/', '/'], $trimedRelativeFilename);
                    $trimedRelativeFilename = preg_replace('/[a-zA-Z0-9_.-]{30,}/m', '', $trimedRelativeFilename);

                    // $this->printer->tabEcho("-- \n{$trimedRelativeFilename}\n");

                    $paths = explode('/', $trimedRelativeFilename);


                    $pathsCnt  = count($paths);
                    $fileTitle = $paths[$pathsCnt - 1];
                    $allowDel  = false;
                    //找到一个有效秒数字数在16以上的,那上级的描述就可以丢了
                    for ($i = 0; $i < $pathsCnt - 1; $i++)
                    {
                        $j = $pathsCnt - $i - 1;
                        if ($allowDel === false)
                        {
                            $allowDel = $this->getDescFilename($fileTitle) > 16;
                        }
                        else
                        {
                            $paths[$j] = '';
                        }

                    }

                    $this->printer->setOutputState($isShowStrMatch);

                    //$fileTitleLength = $this->getDescFilename($fileTitle);

                    $titleAr = array_unique(array_filter(array_map(function ($s) { return trim($s); }, explode(' ', join(' ', $paths))), function ($s) { return $s !== ''; }));

                    $titleAr     = array_values($titleAr);
                    $titleArMaxI = count($titleAr) - 1;

                    foreach ($titleAr as $tmp_i => $title_i)
                    {
                        $title_i = strtolower($title_i);
                        foreach ($titleAr as $tmp_j => $title_j)
                        {
                            if ($tmp_i === $tmp_j)
                            {
                                continue;
                            }

                            $this->printer->tabEcho("check title:{$tmp_i}:{$title_i}  title:{$tmp_j}:{$title_j} " . strstr($title_j, $title_i));
                            $title_j = strtolower($title_j);
                            if (strlen(strstr($title_j, $title_i)) > 2)
                            {
                                //$titleAr[$tmp_i] = "#{$title_i}#";
                                //unset($titleAr[$tmp_i]);
                                $titleAr[$tmp_i] = '';
                            }
                        }
                    }
                    for ($i = $titleArMaxI; $i >= 0; $i--)
                    {
                        $this->printer->tabEcho($titleAr[$i]);
                        $this->printer->newTabEcho('strstr', "{$i}: [{$titleAr[$i]}]");
                        $title_i = strtolower($titleAr[$i]);
                        for ($j = 0; $j <= $titleArMaxI; $j++)
                        {
                            if ($i === $j)
                            {
                                continue;
                            }
                            $this->printer->tabEcho("{$j}_{$titleAr[$j]}  -> [" . strstr($titleAr[$j], $titleAr[$i]) . ']' . (strlen(strstr($titleAr[$j], $titleAr[$i])) > 2 ? 'yes' : 'no'));
                            $title_j = strtolower($titleAr[$j]);
                            if (strlen(strstr($title_j, $title_i)) > 2)
                            {
                                $this->printer->tabEcho("unset {$i}_{$titleAr[$i]}");
                                $titleAr[$i] = '';
                                // unset($titleAr[$i]);
                                break;
                            }
                        }
                        $this->printer->endTabEcho('strstr');
                    }


                    //  $pathAr = array_unique(array_filter(array_map(function ($s) { return trim($s); }, explode(' ', $trimedRelativeFilename)), function ($s) { return $s !== ''; }));
                    if ($isDebug)
                    {
                        //    $this->printer->tabEcho($pathAr);
                    }
                    $uniqPath = join(' ', $titleAr) . "^{$id36}^." . $lsCmdFileinfo->ext;
                    //  $uniqPath = str_replace([' / ', ' /', '/ '], ['/', '/', '/'], $uniqPath);

                    $this->printer->setOutputState($printerOutState);

                    $this->printer->tabEcho("-- single video \n");
                    $this->printer->tabEcho("-- new:{$uniqPath}\n");
                    $oldDir2newDir2list[$albumDir][''][] = ['old' => $lsCmdFileinfo->fullFilename, 'new' => $uniqPath];

                }
            }
            else
            {

                $trimedAlbumDir = str_replace($rubbishWords, $rubbishWordEmpty, $albumDir);
                $trimedAlbumDir = str_replace($rubbishWords, $rubbishWordEmpty, $trimedAlbumDir);
                $trimedAlbumDir = str_replace($rubbishWords, $rubbishWordEmpty, $trimedAlbumDir);
                $trimedAlbumDir = str_replace($rubbishWords, $rubbishWordEmpty, $trimedAlbumDir);
                $trimedAlbumDir = str_replace(['_'], [' '], $trimedAlbumDir);
                $trimedAlbumDir = str_replace([' / ', ' /', '/ '], ['/', '/', '/'], $trimedAlbumDir);


                $this->printer->setOutputState($isShowStrMatch);

                $dirAr     = array_unique(array_filter(array_map(function ($s) { return trim($s); }, explode(' ', $trimedAlbumDir)), function ($s) { return $s !== ''; }));
                $dirAr     = array_values($dirAr);
                $dirArMaxI = count($dirAr) - 1;
                foreach ($dirAr as $tmp_i => $dir_i)
                {
                    $dir_i = strtolower($dir_i);
                    foreach ($dirAr as $tmp_j => $dir_j)
                    {
                        if ($tmp_i === $tmp_j)
                        {
                            continue;
                        }
                        $dir_j = strtolower($dir_j);
                        if (strlen(strstr($dir_j, $dir_i)) > 0)
                        {
                            // $dirAr[$tmp_i] = "#{$dir_i}#";
                            #unset($dirAr[$tmp_i]);
                            $dirAr[$tmp_i] = '';
                        }
                    }
                }
                for ($i = $dirArMaxI; $i >= 0; $i--)
                {
                    $this->printer->tabEcho($dirAr[$i]);
                    $dir_i = strtolower($dirAr[$i]);
                    for ($j = 0; $j <= $dirArMaxI; $j++)
                    {
                        if ($i === $j)
                        {
                            continue;
                        }
                        //  $this->printer->tabEcho("{$j}_{$dirAr[$j]}  -> [".strstr($dirAr[$j], $dirAr[$i]).']'.(strlen(strstr($dirAr[$j], $dirAr[$i]))>2?'yes':'no') );
                        $dir_j = strtolower($dirAr[$j]);
                        if (strlen(strstr($dir_j, $dir_i)) > 2)
                        {
                            $this->printer->tabEcho("unset {$i}_{$dirAr[$i]}");
                            $dirAr[$i] = '';
                            // unset($dirAr[$i]);
                            break;
                        }
                    }
                    $this->printer->endTabEcho('strstr');
                }

                $trimedAlbumDir = join('', $dirAr);
                if (!empty($trimedAlbumDir))
                {
                    $trimedAlbumDir .= '/';
                }
                if (!isset($oldDir2newDir2list[$albumDir][$trimedAlbumDir]))
                {
                    $oldDir2newDir2list[$albumDir][$trimedAlbumDir] = [];
                }
                foreach ($id36toLsCmdFileinfoKV as $id36 => $lsCmdFileinfo)
                {
                    $i++;
                    $this->printer->tabEcho("{$i}/{$cnt} {$lsCmdFileinfo->relativePath}");
                    $relativeFilename = str_replace("^{$id36}^.{$lsCmdFileinfo->ext}", '', $lsCmdFileinfo->relativePath);
                    //    $this->printer->tabEcho("$relativeFilename");
                    $trimedTitle = str_replace($rubbishWords, $rubbishWordEmpty, $lsCmdFileinfo->title);
                    // $trimedTitle = str_replace($rubbishWords, $rubbishWordEmpty, $trimedTitle);
                    $trimedTitle = str_replace(['_'], [' '], $trimedTitle);
                    $trimedTitle = str_replace([' / ', ' /', '/ '], ['/', '/', '/'], $trimedTitle);
                    // $this->printer->tabEcho("-- \n{$trimedRelativeFilename}\n");
                    $this->printer->tabEcho("-- \n{$trimedTitle}\n");
                    $this->printer->setOutputState($isShowStrMatch);


                    $titleAr = array_unique(array_filter(array_map(function ($s) { return trim($s); }, explode(' ', $trimedTitle)), function ($s) { return $s !== ''; }));

                    $titleAr     = array_values($titleAr);
                    $titleArMaxI = count($titleAr) - 1;
                    for ($i = $titleArMaxI; $i >= 0; $i--)
                    {
                        $this->printer->tabEcho($titleAr[$i]);
                        $this->printer->newTabEcho('strstr', "{$i}: [{$titleAr[$i]}]");
                        $title_i = strtolower($titleAr[$i]);
                        for ($j = 0; $j <= $titleArMaxI; $j++)
                        {
                            if ($i === $j)
                            {
                                continue;
                            }
                            $title_j = strtolower($titleAr[$j]);
                            //  $this->printer->tabEcho("{$j}_{$titleAr[$j]}  -> [".strstr($titleAr[$j], $titleAr[$i]).']'.(strlen(strstr($titleAr[$j], $titleAr[$i]))>2?'yes':'no') );
                            if (strlen(strstr($title_j, $title_i)) > 2)
                            {
                                $this->printer->tabEcho("unset {$i}_{$titleAr[$i]}");
                                $titleAr[$i] = '';
                                // unset($titleAr[$i]);
                                break;
                            }
                        }
                        $this->printer->endTabEcho('strstr');
                    }
                    foreach ($titleAr as $tmp_i => $title_i)
                    {
                        $title_i = strtolower($title_i);
                        foreach ($titleAr as $tmp_j => $title_j)
                        {
                            if ($tmp_i === $tmp_j)
                            {
                                continue;
                            }
                            $title_j = strtolower($title_j);
                            // $this->printer->tabEcho("check title:{$tmp_i}:{$title_i}  title:{$tmp_j}:{$title_j} ".strstr($title_j,$title_i));
                            if (strlen(strstr($title_j, $title_i)) > 2)
                            {
                                //$titleAr[$tmp_i] = "#{$title_i}#";
                                //unset($titleAr[$tmp_i]);
                                $titleAr[$tmp_i] = '';
                            }
                        }
                        foreach ($dirAr as $tmp_k => $dir_k)
                        {
                            $dir_k = strtolower($dir_k);
                            // $this->printer->tabEcho("check dir:{$tmp_k}:{$dir_k}  title:{$tmp_i}:{$title_i} ".strstr($dir_k,$title_i));
                            if (strlen(strstr($dir_k, $title_i)) > 2)
                            {
                                //$titleAr[$tmp_i] = "#{$title_i}#";
                                //unset($titleAr[$tmp_i]);
                                $titleAr[$tmp_i] = '';
                            }
                        }
                    }


                    //  $pathAr = array_unique(array_filter(array_map(function ($s) { return trim($s); }, explode(' ', $trimedRelativeFilename)), function ($s) { return $s !== ''; }));
                    if ($isDebug)
                    {
                        // $this->printer->tabEcho($pathAr);
                    }
                    $uniqPathStr = join(' ', $titleAr);
                    $uniqPathStr = trim($uniqPathStr, ' ');
                    $uniqPathStr = trim($uniqPathStr, '_');
                    $uniqPathStr = trim($uniqPathStr, '-');
                    $uniqPathStr = trim($uniqPathStr, ' ');


                    $uniqPath = $trimedAlbumDir . $uniqPathStr . '.' . $lsCmdFileinfo->ext;
                    //  $uniqPath = str_replace([' / ', ' /', '/ '], ['/', '/', '/'], $uniqPath);

                    $this->printer->setOutputState($printerOutState);

                    $this->printer->tabEcho("--videos \n");

                    // $this->printer->tabEcho("{$trimedRelativeFilename}");
                    $this->printer->tabEcho("-- new:{$uniqPath}\n");
                    $oldDir2newDir2list[$albumDir][$trimedAlbumDir][] = ['old' => $lsCmdFileinfo->fullFilename, 'new' => $uniqPath];

                }
            }
            $this->printer->endTabEcho('oldDir');
        }
        $this->printer->endTabEcho('oldDir2newDir2list');

        $this->printer->tabEcho("\n注意检查 oldDir2newDir2list   ");
        $this->printer->tabEcho("\n注意检查 oldDir2newDir2list   ");
        $this->printer->tabEcho("\n注意检查 oldDir2newDir2list   ");
        $this->printer->tabEcho("\n注意检查 oldDir2newDir2list   ");
        $this->printer->tabEcho("\n注意检查 oldDir2newDir2list   ");
        $this->printer->tabEcho("\n注意检查 oldDir2newDir2list   ");
        $this->printer->tabEcho("\n注意检查 oldDir2newDir2list   ");
        $this->printer->tabEcho("\n注意检查 oldDir2newDir2list   ");
        $this->printer->tabEcho("\n注意检查 oldDir2newDir2list   ");
        $this->printer->tabEcho("\n注意检查 oldDir2newDir2list   ");
        $this->printer->tabEcho("\n注意检查 oldDir2newDir2list   ");
        $this->printer->tabEcho(CLIStrFormatter::info("\n注意检查 oldDir2newDir2list   "));

        $this->printer->newTabEcho('show_oldDir2newDir2list', '\$oldDir2newDir2list  展示: old dir =>  new dir => listIndex => [oldFullpath,newRelativePath]');
        $this->printer->tabEcho($oldDir2newDir2list);
        $this->printer->endTabEcho('show_oldDir2newDir2list');

        if ($isPreview)
        {
            // $this->printer->tabEcho(CLIStrFormatter::info("\n只是预览,下面处理就不进行了  "));
            //  return false;
        }
        foreach ($oldDir2newDir2list as $oldDir => $newDir2list)
        {
            $this->printer->newTabEcho('old_dir', "OLD DIR:[{$oldDir}]");

            $newDirCnt = count($newDir2list);
            $this->printer->tabEcho(" 理论上，一个old dir 下面只有一个新dir , new dir count: {$newDirCnt}\n");
            if (count($newDir2list) !== 1)
            {
                $this->printer->tabEcho(CLIStrFormatter::error("ERROR  超过了\n"));
            }
            else
            {
                $this->printer->tabEcho("OK {$oldDir} \n");
                foreach ($newDir2list as $newDir => $list)
                {
                    $this->printer->newTabEcho('new_dir', "NEW DIR:[{$newDir}]");
                    if ($newDir === '')
                    {
                        $newDirFullPath = $this->rootDir;
                    }
                    else
                    {
                        $newDirFullPath = $this->rootDir . '/' . $newDir;
                    }
                    if (!file_exists($newDirFullPath))
                    {
                        mkdir($newDirFullPath, 0777, true);
                        usleep(500);
                    }
                    if (!file_exists($newDirFullPath))
                    {
                        $this->printer->tabEcho(CLIStrFormatter::error("ERROR  创建新目录失败 {$newDirFullPath}\n"));
                        continue;
                    }

                    $this->printer->tabEcho("NEW DIR fullPath [{$newDirFullPath}]");

                    $listCnt = count($list);
                    $this->printer->tabEcho("old dir [{$oldDir}]\n->\nnew dir:[{$newDir}] count: [{$listCnt}]\n");

                    if (false && $newDir === '')
                    {
                        $this->printer->tabEcho(" 理论上， 无 album 的new  dir 下面只有一个视频文件 , video count: {$listCnt}\n");

                        if ($listCnt !== 1)
                        {
                            $this->printer->tabEcho(CLIStrFormatter::error("ERROR , 空album下的视频数量超了\n"));
                            $this->printer->tabEcho($list);
                        }
                        else
                        {
                            $this->printer->tabEcho("list count/1 OK ");

                            $this->printer->tabEcho("ONE list:{$list[0]['old']}\n->\n{$newDirFullPath}/{$list[0]['new']} \n");
                            if ($isPreview)
                            {
                                $this->printer->tabEcho("PREVIEW,并没有真 mv");
                            }
                            else
                            {
                                rename($list[0]['old'], $newDirFullPath . '/' . $list[0]['new']);
                            }
                        }
                    }
                    else
                    {
                        $this->printer->newTabEcho('fetch_list', '开始梳理list');
                        $this->printer->tabEcho("list count OK \n");
                        $tmp_i = 0;
                        foreach ($list as $item)
                        {

                            $tmp_i++;
                            $old = $item['old'];
                            $new = "{$this->rootDir}/{$item['new']}";
                            $this->printer->newTabEcho('list_item', "{$tmp_i}/{$listCnt}");
                            $this->printer->tabEcho("{$old}\n->\n{$new}");

                            if ($new !== $old)
                            {
                                if ($isPreview)
                                {
                                    $this->printer->tabEcho("PREVIEW,并没有真 mv");
                                }
                                else
                                {
                                    rename($item['old'], $this->rootDir . '/' . $item['new']);
                                }
                            }
                            else
                            {
                                $this->printer->tabEcho("NOOP,文件名相同");
                            }
                        }
                    }
                }
            }
        }

        // $this->printer->tabEcho($oldDir2newDir2list);

        die;
    }


    /**
     * 去除英文数词 特殊符号
     * @param $fileTitle
     * @return string
     * @throws \Exception
     */
    public function getDescFilename($fileTitle)
    {
        return preg_replace('/[\x21-\x7e-A-Za-z0-9]/i', '', $fileTitle);
    }


    public function tmp2src()
    {
        $srcRootDir = $this->inputBox->getNotEmptyString('src');
        $dstRootDir = $this->inputBox->getNotEmptyString('dst');


        $taskM = new FfmpegTask();

        $id36toLsCmdFileinfoKV = $taskM->getId36KV($srcRootDir);

        $cnt            = count($id36toLsCmdFileinfoKV);
        $dir2id36toInfo = [];
        /**
         * @var LsCmdFileinfo $lsCmdFileinfo `
         */
        foreach ($id36toLsCmdFileinfoKV as $id36 => $lsCmdFileinfo)
        {
            if (!isset($dir2id36toInfo[$lsCmdFileinfo->albumNameDir]))
            {
                $dir2id36toInfo[$lsCmdFileinfo->albumNameDir] = [];
            }
            $dir2id36toInfo[$lsCmdFileinfo->albumNameDir][$id36] = $lsCmdFileinfo;
        }


        foreach ($dir2id36toInfo as $albumDir => $id36toInfoKV)
        {
            if ($albumDir === '')
            {
                /**
                 * @var LsCmdFileinfo $lsCmdFileinfo `
                 */
                foreach ($id36toInfoKV as $id36 => $lsCmdFileinfo)
                {
                    $dstFilename = "{$dstRootDir}/{$lsCmdFileinfo->relativePath}";
                    $this->printer->tabEcho("{$lsCmdFileinfo->fullFilename}\n->\n{$dstFilename}\n");
                    rename($lsCmdFileinfo->fullFilename, $dstFilename);
                }

            }
            else
            {
                $srcDir = "{$srcRootDir}/{$albumDir}";
                $toDir  = "{$dstRootDir}/{$albumDir}";
                if (!file_exists($toDir))
                {
                    mkdir($toDir, 0777, true);
                    usleep(500);
                }
                if (!file_exists($toDir))
                {
                    $this->printer->tabEcho(CLIStrFormatter::error("ERROR  创建新目录失败 {$toDir}\n"));
                    continue;
                }
                /**
                 * @var LsCmdFileinfo $lsCmdFileinfo `
                 */
                foreach ($id36toInfoKV as $id36 => $lsCmdFileinfo)
                {
                    $dstFilename = "{$dstRootDir}/{$lsCmdFileinfo->relativePath}";
                    $this->printer->tabEcho("{$lsCmdFileinfo->fullFilename}\n->\n{$dstFilename}\n");
                    rename($lsCmdFileinfo->fullFilename, $dstFilename);
                }
                usleep(5000);
                if (count(scandir($srcDir)) === 2)
                {
                    rmdir($srcDir);
                }
            }
        }


    }


    public function test2()
    {
        $m       = new LsCmdFileinfo();
        $rootDir = "/mnt/f/tmp2/format/wait/src";
        $m->findTaskedFile($rootDir, '336');
    }
}

