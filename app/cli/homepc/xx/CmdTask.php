<?php


namespace cli\homepc\xx;

use hammer\cli\CmdBase;
use models\common\Def;
use hammer\param\Param;
use hammer\sys\Sys;
use models\ext\tool\CLIFormatter;
use models\ext\tool\CLIStrFormatter;
use models\ext\tool\Printer;
use models\ext\VicWord\Lib\VicWord;
use modules\dev\v1\api\KL_PC\xx\op\model\FileVideoInfo;
use modules\dev\v1\api\KL_PC\xx\op\model\LsCmdFileinfo;
use modules\dev\v1\api\KL_PC\xx\op\model\TaskExpect;
use modules\dev\v1\api\KL_PC\xx\op\model\TaskSettingBox;
use modules\dev\v1\api\KL_PC\xx\op\model\XxCommon;
use modules\dev\v1\dao\video\FfmpegTask;

ini_set('memory_limit', '3072M');


class CmdTask extends CmdBase
{


    /**
     * @var Printer
     */
    protected $printer = false;

    public function init()
    {
        parent::init();
        $this->printer = new  Printer();
    }

    public function test()
    {
        die('xxxx');
    }


    public function mark()
    {

        Sys::app()->initPrinter();
        $printer = $this->printer;
        $this->printer->setOutputState(true);

        $srcRootDir = $this->inputBox->tryGetString('dir');

        if (empty($srcRootDir))
        {
            $srcRootDir = '/mnt/f/tmp2/format/wait/src';
        }
        chmod($srcRootDir, 0777);


        $getDirsCmd = "find '{$srcRootDir}' -maxdepth 2 -type d|grep -v FFOutput_res|grep -v FFOutput_tmp";
        $getDirsCmd = "find '{$srcRootDir}' -maxdepth 2 -type d|grep -v FFOutput";

        //$getDirsCmd        = "find '{$srcRootDir}' -maxdepth 2 -type d";
        $clearEmptyDirsFun = function ($srcRootDir) use ($getDirsCmd)
        {
            echo "\n{$getDirsCmd}\n";
            exec($getDirsCmd, $dirs);
            foreach ($dirs as $dir)
            {
                if ($dir === $srcRootDir || $dir === "{$srcRootDir}/FFOutput_res" || $dir === "{$srcRootDir}/FFOutput_tmp" || $dir === "{$srcRootDir}/FFOutput")
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
            $clearEmptyDirsFun($srcRootDir);
        }

        $this->printer->setOutputState(false);

        $videoInfoKV = (new XxCommon())->setPrinter($printer)->getVideos($srcRootDir);
        $this->printer->setOutputState(true);
        $taskM = new FfmpegTask();
        $this->printer->newTabEcho('try_insert_new', 'try_insert_new 先记录文件');
        foreach ($videoInfoKV as $videoFUllPath => $info)
        {
            $taskM = new FfmpegTask();
            $taskM->setLogState(true);

            $isMarked = false;
            $existM   = false;
            if (preg_match('/(\^[A-Za-z0-9]+\^)\./', $videoFUllPath, $keyMatch))
            {
                $this->printer->tabEcho("{$videoFUllPath}");
                $id36 = trim($keyMatch[1], '^');
                $id   = base_convert($id36, 36, 10);
                $this->printer->tabEcho("id36:{$id36}  id10:{$id}");

                $existM = $taskM->findByPk($id);
                // $existM = $taskM->findByAttributes(['album_dir' => $info['albumNameDir'], 'video_title' => $info['filenameText'], 'video_ext' => $info['ext'],]);
                if ($existM === false)
                {
                    $this->printer->tabEcho(CLIStrFormatter::error("[{$id36}]  not exist,{$videoFUllPath}"));
                    continue;
                }
                else
                {
                    if (strval($existM->raw_filename) === '')
                    {
                        $existM->raw_filename = "{$existM->album_dir}/{$existM->video_title}";
                        $existM->save();
                    }
                }
            }


            //$this->printer->tabEcho($taskM->getLogs());
            if ($existM === false)
            {
                $taskM->album_dir      = $info['albumNameDir'];
                $taskM->video_title    = $info['filenameText'];
                $taskM->video_ext      = $info['ext'];
                $taskM->root_dir       = $srcRootDir;
                $taskM->src_video_info = '{}';
                $taskM->dst_video_info = '{}';
                $taskM->task_info      = '{}';
                $taskM->is_src_exist   = Def::staYes;
                $taskM->raw_filename   = "{$info['albumNameDir']}/{$info['filenameText']}";
                $taskM->save();
                $id36        = base_convert($taskM->id, 10, 36);
                $taskM->id36 = "{$id36}";

                if (!strstr($info['filenameText'], "^$id36^"))
                {
                    rename($videoFUllPath, "{$info['dirname']}/{$info['filenameText']}^{$id36}^.{$info['ext']}");
                    $this->printer->tabEcho("[{$taskM->id}] rename id36");
                    $taskM->video_title = "{$info['filenameText']}^{$id36}^";
                }
                $taskM->save();
                $this->printer->tabEcho("[{$taskM->id}] insert_new");

            }
            else
            {
                $this->printer->tabEcho("[{$existM->id}]  exist_and_check is_error:{$existM->is_err} is_ok:{$existM->is_ok}  is_pre:{$existM->is_pre} is_exist:{$existM->is_src_exist} ext:{$info['ext']}/{$existM->video_ext} {$existM->create_date}/{$taskM->update_date} run_lc:{$existM->run_lc}\n\t{$videoFUllPath}");
                $existM->is_pre = Def::staNot;

                $id36         = base_convert($existM->id, 10, 36);
                $existM->id36 = "{$id36}";
                if (!strstr($info['filenameText'], "^$id36^"))
                {
                    rename($videoFUllPath, "{$info['dirname']}/{$info['filenameText']}^{$id36}^.{$info['ext']}");
                    $this->printer->tabEcho("[{$taskM->id}] old task rename id36");
                    $taskM->video_title = "{$info['filenameText']}^{$id36}^";
                }
                if (strval($existM->raw_filename) === '')
                {
                    $existM->raw_filename = "{$existM->album_dir}/{$existM->video_title}";
                }
                $existM->save();

            }
            $taskM->setLogState(false);
        }
        $this->printer->endTabEcho('try_insert_new', 'try_insert_new');

    }


    //剧照
    public function stills()
    {

        Sys::app()->initPrinter();
        $printer = $this->printer;
        $this->printer->setOutputState(true);

        $rootDir    = $this->inputBox->tryGetString('dir');
        $forceRenew = $this->inputBox->tryGetString('force_renew') === 'yes';
        $id36s      = array_filter(array_map('trim', explode(',', $this->inputBox->tryGetString('id36'))), function ($id36)
        {
            return $id36;
        });

        if (empty($rootDir))
        {
            $rootDir = '/mnt/f/tmp2/format/wait';
        }

        $srcRootDir = "{$rootDir}/src";
        //  $rootDir = "/mnt/f/tmp2/format/wait";


        $taskM         = new FfmpegTask();
        $video_info_kv = $taskM->getId36KV($srcRootDir, []);
        // 截图时间点（开始时间，单位：秒）
        $startTsList = array_column($taskM->getConnection()->setText("select count(sec) as times,sec from (SELECT  task_info->>'$.manual.ss' as sec FROM kl.ffmpeg_tasks where is_ok=1 and json_extract(task_info,'$.manual.ss') is not null  ) as tmp group by sec having times>2;
")->queryAll(), 'sec');
        // 结尾偏移量（单位：秒），实际截图时间 = 视频总时长 - 该值
        $tailTsList = array_column($taskM->getConnection()->setText("select count(sec) as times,sec from (SELECT  task_info->>'$.manual.tailAdStart' as sec FROM kl.ffmpeg_tasks where is_ok=1 and json_extract(task_info,'$.manual.tailAdStart') is not null  ) as tmp group by sec having times>2;
")->queryAll(), 'sec');
        $tmp        = [];
        if (count($id36s) > 0)
        {
            foreach ($id36s as $id36)
            {
                $tmp[$id36] = $video_info_kv[$id36];
            }
            $video_info_kv = $tmp;
        }
        $startTsList = array_filter(array_map('intval', $startTsList), function ($value) { return $value > 0; });
        $tailTsList  = array_filter(array_map('intval', $tailTsList), function ($value) { return $value > 0; });
        var_export($video_info_kv);
        var_export($startTsList);
        var_export($tailTsList);


        $imgRootDir = "{$rootDir}/FFOutput_stills/";

        // ============ 配置 ============
        define('SCREENSHOT_ROOT', $imgRootDir);                 // 截图输出根目录
        define('OUTPUT_HEIGHT', 480);
        define('CROP_LIMIT', 24);
        define('CROP_ROUND', 16);
        define('CROP_DETECT_FRAMES', 5);
        define('FFMPEG_THREADS', 0);
        define('ENABLE_CUDA', true);
        define('CHECK_LEVEL', 3);  // 1=不检查，2=检查目录，3=检查文件

        $GLOBALS['_start_time'] = microtime(true);
        $last_time              = microtime(true);


        function logMsg($msg, $newline = true)
        {
            static $last_time = null;  // 静态变量，保留上次调用时间
            if ($last_time === null)
            {
                $last_time = microtime(true);
            }
            $now      = microtime(true);
            $elapsed  = $now - $GLOBALS['_start_time'];
            $elapsed2 = $now - $last_time;

            $dateTime    = date('Y-m-d H:i:s', (int)$now);
            $elapsedStr  = number_format($elapsed, 3, '.', '');
            $elapsedStr2 = number_format($elapsed2, 3, '.', '');

            $prefix    = "[{$dateTime}] [耗时: {$elapsedStr2}/{$elapsedStr}s] ";
            $last_time = $now;
            echo $prefix . $msg . ($newline ? "\n" : "");
        }

        // ============ 统计初始化 ============
        $totalVideos             = count($video_info_kv);
        $processedVideos         = 0;
        $skippedVideosDir        = 0;
        $totalPlannedScreenshots = $totalVideos * (count($startTsList) + count($tailTsList));
        $capturedScreenshots     = 0;
        $skippedScreenshotsExist = 0;
        $invalidTimePoints       = 0;

        // ============ 辅助函数 ============
        function getCropParams($videoPath, $timePoint)
        {
            $cmd = sprintf('ffmpeg -hide_banner -ss %d -i %s -vf "cropdetect=limit=%d:round=%d" -frames:v %d -f null - 2>&1', $timePoint, escapeshellarg($videoPath), CROP_LIMIT, CROP_ROUND, CROP_DETECT_FRAMES);
            exec($cmd, $output, $ret);
            if ($ret !== 0)
                return null;
            foreach ($output as $line)
            {
                if (preg_match('/crop=(\d+:\d+:\d+:\d+)/', $line, $matches))
                {
                    return $matches[1];
                }
            }
            return null;
        }

        // ============ 主逻辑 ============
        exec('ffmpeg -version 2>&1', $ffmpegCheck, $ret);
        if ($ret !== 0)
        {
            logMsg("错误：未找到 FFmpeg 命令。");
            exit(1);
        }
        logMsg("FFmpeg 检查通过，开始处理...");

        $videoIndex = 0;
        // ============ 主逻辑（修改部分） ============
        $videoIndex = 0;
        foreach ($video_info_kv as $key => $infoObj)
        {
            $videoIndex++;
            $fullPath = $infoObj->fullFilename;
            logMsg(CLIStrFormatter::getStr("\n\n========== [{$videoIndex}/{$totalVideos}] 处理视频 Key: {$key} ==========\n\n", CLIStrFormatter::white, CLIStrFormatter::green));

            if (!file_exists($fullPath))
            {
                logMsg("警告：视频文件不存在，跳过该视频。");
                continue;
            }

            $outputDir = SCREENSHOT_ROOT . $key . '/';

            // ---- 检查级别2：目录存在则跳过整个视频 ----
            if (CHECK_LEVEL == 2 && is_dir($outputDir))
            {
                logMsg("目录已存在，跳过该视频（级别2）。");
                $skippedVideosDir++;
                continue;
            }

            // 创建目录
            if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true))
            {
                logMsg("错误：无法创建目录，跳过该视频。");
                continue;
            }

            // 获取视频总时长
            $durationCmd    = "ffprobe -hide_banner -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '{$fullPath}'";
            $durationOutput = [];
            exec($durationCmd, $durationOutput, $ret);
            if ($ret !== 0 || empty($durationOutput) || ($totalDuration = (float)$durationOutput[0]) <= 0)
            {
                logMsg("警告：无法获取视频时长，跳过。");
                continue;
            }
            $totalDuration = (float)$durationOutput[0];

            logMsg(CLIStrFormatter::getStr("\n时长：{$totalDuration}s 处理视频 Key: {$key} {$fullPath}\n{$durationCmd}\n ", CLIStrFormatter::white, CLIStrFormatter::green));


            // ---------- 构建计划列表 ----------
            $plannedItems = [];  // 每个元素：['type'=>'start','display'=>10,'timePoint'=>10]
            foreach ($startTsList as $startTs)
            {
                $plannedItems[] = ['type' => 'start', 'display' => $startTs, 'timePoint' => $startTs];
            }
            foreach ($tailTsList as $tailOffset)
            {
                $timePoint      = (int)($totalDuration - $tailOffset);
                $plannedItems[] = ['type' => 'tail', 'display' => $tailOffset, 'timePoint' => $timePoint];
            }

            // ---------- 扫描已存在的截图（仅当 CHECK_LEVEL == 3） ----------
            $existingKeys = [];  // 存储已存在的 "type_display" 键
            if (CHECK_LEVEL == 3)
            {
                $scannedFiles = glob($outputDir . '{start,tail}_*.jpg', GLOB_BRACE);
                foreach ($scannedFiles as $file)
                {
                    if (preg_match('/(start|tail)_(\d+)\.jpg$/', basename($file), $matches))
                    {
                        $type                                 = $matches[1];
                        $display                              = (int)$matches[2];
                        $existingKeys[$type . '_' . $display] = true;
                    }
                }
            }

            // ---------- 统计与待截图列表 ----------
            $videoPlanned      = count($plannedItems);
            $videoCaptured     = 0;
            $videoSkippedExist = 0;
            $videoInvalid      = 0;
            $toCapture         = [];  // 需要截图的计划项

            foreach ($plannedItems as $item)
            {
                $type      = $item['type'];
                $display   = $item['display'];
                $timePoint = $item['timePoint'];

                // 检查时间点是否有效
                if ($timePoint < 0 || $timePoint >= $totalDuration)
                {
                    logMsg("  跳过 {$type}（偏移值 {$display}s）：时间点 {$timePoint}s 超出范围。");
                    $videoInvalid++;
                    $invalidTimePoints++;
                    continue;
                }

                // 检查文件是否已存在（仅级别3）
                if (CHECK_LEVEL == 3 && isset($existingKeys[$type . '_' . $display]))
                {
                    logMsg("  文件已存在，跳过：{$type}_{$display}.jpg");
                    $videoSkippedExist++;
                    $skippedScreenshotsExist++;
                    continue;
                }

                // 需要截图
                $toCapture[] = $item;
            }

            logMsg("计划截图 {$videoPlanned} 张，已存在 {$videoSkippedExist} 张，无效 {$videoInvalid} 张，待截图 " . count($toCapture) . " 张");

            // ---------- 执行截图（只对待截图列表） ----------

            $takeScreenshot = function ($timePoint, $suffix, $displayValue) use (
                $fullPath, $outputDir, $totalDuration, &$videoCaptured, &$videoSkippedExist, &$videoInvalid, &$capturedScreenshots, &$skippedScreenshotsExist, &$invalidTimePoints
            )
            {
                logMsg(CLIStrFormatter::getStr("  截图 {$suffix} 偏移值 {$displayValue}s（时间点 {$timePoint}s）", CLIStrFormatter::blue, CLIStrFormatter::white));

                // 有效性检查（使用已修正的 $totalDuration）
                if ($timePoint < 0 || $timePoint >= $totalDuration)
                {
                    logMsg("  跳过 {$suffix}（偏移值 {$displayValue}s）：时间点 {$timePoint}s 超出范围。");
                    $videoInvalid++;
                    $invalidTimePoints++;
                    return;
                }

                // 如果时间点非常接近末尾，回退 0.5 秒避免无帧
                if ($totalDuration - $timePoint < 1.0)
                {
                    $timePoint = max(0, $totalDuration - 0.5);
                    logMsg("  时间点接近末尾，调整为 {$timePoint}s");
                }

                $outputFile = $outputDir . $suffix . '_' . (int)$displayValue . '.jpg';

                // 检查文件是否已存在（级别3）
                if (CHECK_LEVEL == 3 && file_exists($outputFile))
                {
                    logMsg("  文件已存在，跳过：{$outputFile}");
                    $videoSkippedExist++;
                    $skippedScreenshotsExist++;
                    return;
                }

                // ----- 黑边检测与滤镜链 -----
                $cropParams  = getCropParams($fullPath, $timePoint);
                $filterParts = [];
                if ($cropParams)
                {
                    $parts = explode(':', $cropParams);
                    if (count($parts) == 4 && $parts[0] > 0 && $parts[1] > 0)
                    {
                        $filterParts[] = "crop={$cropParams}";
                        logMsg("  {$suffix} 偏移值 {$displayValue}s（时间点 {$timePoint}s）裁剪：crop={$cropParams}");
                    }
                    else
                    {
                        logMsg("  {$suffix} 偏移值 {$displayValue}s（时间点 {$timePoint}s）裁剪参数无效，跳过裁剪。");
                    }
                }
                else
                {
                    logMsg("  {$suffix} 偏移值 {$displayValue}s（时间点 {$timePoint}s）未检测到黑边，仅缩放。");
                }
                $filterParts[] = "scale=-1:" . OUTPUT_HEIGHT;
                $filterParts[] = "format=yuvj420p";
                $filter        = implode(',', $filterParts);

                // 线程参数
                $threads = FFMPEG_THREADS > 0 ? "-threads " . FFMPEG_THREADS : "";

                // ----- 定义尝试方案（按优先级）-----
                $attempts = [];

                // 方案1：快速跳转（-ss 在前）+ CUDA 硬件加速
                if (ENABLE_CUDA)
                {
                    $attempts[] = [
                        'cmd'  => sprintf('ffmpeg -hwaccel cuda -ss %F -i %s %s -vf "%s" -vframes 1 -pix_fmt yuvj420p -an -y %s 2>&1', $timePoint, escapeshellarg($fullPath), $threads, $filter, escapeshellarg($outputFile)),
                        'desc' => '快速跳转 + CUDA'
                    ];
                }

                // 方案2：精确跳转（-ss 在后）+ CPU（无硬件加速）
                $attempts[] = [
                    'cmd'  => sprintf('ffmpeg -i %s %s -ss %F -vf "%s" -vframes 1 -pix_fmt yuvj420p -an -y %s 2>&1', escapeshellarg($fullPath), $threads, $timePoint, $filter, escapeshellarg($outputFile)),
                    'desc' => '精确跳转 + CPU'
                ];

                // 方案3：快速跳转 + CPU（作为备选）
                $attempts[] = [
                    'cmd'  => sprintf('ffmpeg -ss %F -i %s %s -vf "%s" -vframes 1 -pix_fmt yuvj420p -an -y %s 2>&1', $timePoint, escapeshellarg($fullPath), $threads, $filter, escapeshellarg($outputFile)),
                    'desc' => '快速跳转 + CPU'
                ];

                // 方案4：偏移 ±0.5 秒 + 精确跳转 + CPU（处理关键帧偏移）
                $offsets = [0.5, -0.5];
                foreach ($offsets as $offset)
                {
                    $adjusted = $timePoint + $offset;
                    if ($adjusted < 0 || $adjusted >= $totalDuration)
                        continue;
                    $attempts[] = [
                        'cmd'  => sprintf('ffmpeg -i %s %s -ss %F -vf "%s" -vframes 1 -pix_fmt yuvj420p -an -y %s 2>&1', escapeshellarg($fullPath), $threads, $adjusted, $filter, escapeshellarg($outputFile)),
                        'desc' => "精确跳转 + CPU (偏移 {$offset}s)"
                    ];
                }

                // ----- 执行尝试 -----
                $success = false;
                foreach ($attempts as $index => $attempt)
                {
                    logMsg("  尝试方案 " . ($index + 1) . "：{$attempt['desc']} ...");
                    exec($attempt['cmd'], $output, $ret);
                    if ($ret === 0 && file_exists($outputFile) && filesize($outputFile) > 0)
                    {
                        logMsg("  ✅ 截图成功：{$outputFile} (使用 {$attempt['desc']})");
                        $videoCaptured++;
                        $capturedScreenshots++;
                        $success = true;
                        break;
                    }
                    else
                    {
                        // 删除可能产生的空文件
                        if (file_exists($outputFile))
                        {
                            unlink($outputFile);
                        }
                        $errMsg = implode("\n", array_slice($output, -5));
                        logMsg("  方案失败：{$errMsg}");
                    }
                }

                if (!$success)
                {
                    logMsg(CLIStrFormatter::error("  ❌ 截图完全失败（时间点 {$timePoint}s），所有方案均无效。"));
                }
            };

            // 只处理待截图列表
            if (!empty($toCapture))
            {
                logMsg("开始截图...");
                foreach ($toCapture as $item)
                {
                    $takeScreenshot($item['timePoint'], $item['type'], $item['display']);
                }
            }
            else
            {
                logMsg("无需截图，所有文件已存在或无效。");
            }

            logMsg("视频 [{$key}] 统计：计划 {$videoPlanned} 张，成功 {$videoCaptured}，文件跳过 {$videoSkippedExist}，无效时间点 {$videoInvalid}");
            $processedVideos++;

            // 总体进度
            $totalSkippedExist = $skippedScreenshotsExist;
            $totalCaptured     = $capturedScreenshots;
            $totalInvalid      = $invalidTimePoints;
            logMsg(CLIStrFormatter::getStr("\nkey:{$key}  duration:{$totalDuration} 累计进度：已处理视频 {$processedVideos}/{$totalVideos}（跳过视频目录 {$skippedVideosDir}） | 截图：成功 {$totalCaptured}，文件跳过 {$totalSkippedExist}，无效时间点 {$totalInvalid}", CLIStrFormatter::yellow, CLIStrFormatter::blue));
        }

        // ============ 最终统计 ============
        logMsg("\n================== 处理完成 ==================");
        logMsg("视频总数：{$totalVideos}");
        logMsg("实际处理视频数：{$processedVideos}");
        logMsg("因目录存在跳过的视频数：{$skippedVideosDir}");
        logMsg("计划截图总数：{$totalPlannedScreenshots}");
        logMsg("成功截图数：{$capturedScreenshots}");
        logMsg("因文件存在跳过截图数：{$skippedScreenshotsExist}");
        logMsg("无效时间点跳过数：{$invalidTimePoints}");

        $totalElapsed = microtime(true) - $GLOBALS['_start_time'];
        logMsg("总耗时：" . number_format($totalElapsed, 3) . " 秒");
        logMsg("==============================================");
    }

    public function getAutoExpect(LsCmdFileinfo $lsCmdFileinfo, &$msg = [])
    {
        $this->mark();
        Sys::app()->initPrinter();
        $printer = $this->printer;
        $this->printer->setOutputState(true);


        $this->printer->setOutputState(true);
        $this->printer->newTabEcho('try_insert_new', 'try_insert_new 先记录文件');

        $this->printer->endTabEcho('try_insert_new', 'try_insert_new');


        // var_dump($ids,$taskMs);
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

        //关闭自动匹配分辨率
        $EXPECT_TAG2CFG_KV = [];

        echo "\n";


        $this->printer->newTabEcho('make dst config', "make task config  准备获取videos info\n");

        $videoInfoObj = new FileVideoInfo($lsCmdFileinfo->fullFilename);

        $videoInfoObj->setPrinter($printer);


        $msgs = ['ERROR initVideo失败'];
        if ($videoInfoObj->initVideoInfo($msgs) === false)
        {
            echo CLIStrFormatter::error($msgs);
            $this->printer->tabEcho('error  ,skip');
            return false;
        }


        $this->printer->tabEcho("\nSRC:width:{$videoInfoObj->width} height:{$videoInfoObj->height} fps:{$videoInfoObj->fps} bit_rate:{$videoInfoObj->rateK} duration:{$videoInfoObj->durationSeconds}");
        if (empty($videoInfoObj->fps) || empty($videoInfoObj->width) || empty($videoInfoObj->height) || empty($videoInfoObj->rateK))
        {
            $this->printer->tabEcho(CLIStrFormatter::error("ERROR video_info_lost\n"));

            return false;
        }

        $taskSettingBox = new TaskSettingBox($videoInfoObj);

        $isPhone = $videoInfoObj->isPhone;


        foreach ($KEEP_SRC_VIDEO_KWS as $kw)
        {
            if (strstr($taskSettingBox->src->baseName, $kw))
            {
                $taskSettingBox->delSrc = false;
                break;
            }
        }


        $this->printer->newTabEcho('auto_dst_config', 'auto dst config');
        /**   $expectFps int 预期fps */
        $expectFps = $MIN_FPS;
        /**   $MIN_RATE_K int 预期rate k */
        $expectRateK = $MIN_RATE_K;
        /**   $MIN_HEIGHT int 预期高度 */
        $expectHeight = $MIN_HEIGHT;


        $flags = ['copy' => true];
        if ($expectRateK > $videoInfoObj->rateK)
        {
            if ($videoInfoObj->fps < $MIN_FPS)
            {
                $taskSettingBox->dstFlag = 'ffCopy';
                $taskSettingBox->dstFlag = 'low2';

                $fpss = [12, 15, 18, 24, 30];
                if (in_array($videoInfoObj->fps, $fpss))
                {
                    $taskSettingBox->dstFps = $videoInfoObj->fps;
                }
                else
                {
                    foreach ($fpss as $fps)
                    {
                        if ($videoInfoObj->fps < $fps)
                        {
                            break;
                        }
                        $taskSettingBox->dstFps = $fps;
                    }

                }
                if ($videoInfoObj->rateK < 600)
                {
                    $taskSettingBox->dstMaxRateK = intval($videoInfoObj->rateK * 0.8);
                }
                else if ($videoInfoObj->rateK < 800)
                {
                    $taskSettingBox->dstMaxRateK = intval($videoInfoObj->rateK * 0.8);

                }
                else if ($videoInfoObj->rateK < 1000)
                {
                    $taskSettingBox->dstMaxRateK = intval($videoInfoObj->rateK * 0.85);

                }
                else if ($videoInfoObj->rateK < 1200)
                {
                    $taskSettingBox->dstMaxRateK = intval($videoInfoObj->rateK * 0.85);

                }
                else if ($videoInfoObj->rateK < 1400)
                {
                    $taskSettingBox->dstMaxRateK = intval($videoInfoObj->rateK * 0.85);

                }
                else
                {
                    $taskSettingBox->dstMaxRateK = intval($videoInfoObj->rateK * 0.9);

                }
                $taskSettingBox->dstMaxHeight = $videoInfoObj->height;
                $this->printer->tabEcho("rate  video < DEFAULT && fps  video < MIN   {$videoInfoObj->rateK}  < {$expectRateK} / {$videoInfoObj->fps} < {$MIN_FPS} ,so dst flag is [ low2 ]");

                $flags = [
                    'type'     => $taskSettingBox->dstCode,
                    'height'   => $taskSettingBox->dstMaxHeight,
                    'maxRateK' => "{$taskSettingBox->dstMaxRateK}K",
                    'fps'      => $taskSettingBox->dstFps,
                ];

            }
            else
            {
                $taskSettingBox->dstMaxRateK = intval($videoInfoObj->rateK * 0.9);
                $this->printer->tabEcho("rate  video < DEFAULT && fps  video > MIN  {$videoInfoObj->rateK}  < {$expectRateK}  / {$videoInfoObj->fps} > {$MIN_FPS} ,so dst rate is [ 9/10 *video.rate ]");

            }
        }
        else
        {
            $taskSettingBox->dstMaxRateK = $expectRateK;
            $this->printer->tabEcho("rate  video > DEFAULT   {$videoInfoObj->rateK}  > {$expectRateK} ,so dst rate is [ DEFAULT ]");

        }


        if ($taskSettingBox->dstFlag === 'ffCopy')
        {

        }
        else if ($taskSettingBox->dstFlag === 'low2')
        {
            $flags['copy'] = false;
        }
        else
        {
            $flags = ['copy' => false];
            if ($isPhone)
            {
                if ($videoInfoObj->height >= $MAX_HEIGHT)
                {
                    $taskSettingBox->dstMaxHeight = $MAX_HEIGHT;
                }
                else
                {
                    if ($videoInfoObj->height >= $MIN_HEIGHT)
                    {
                        $taskSettingBox->dstMaxHeight = $MIN_HEIGHT;
                    }
                }
            }
            else
            {
                if ($videoInfoObj->height >= $expectHeight)
                {
                    $taskSettingBox->dstMaxHeight = $expectHeight;
                }
                else if ($videoInfoObj->height >= $MIN_HEIGHT)
                {
                    $taskSettingBox->dstMaxHeight = $MIN_HEIGHT;
                }
            }

            if ($videoInfoObj->fps >= $expectFps)
            {
                $taskSettingBox->dstFps = $expectFps;
            }
            else
            {
                if ($videoInfoObj->fps > 24 && $videoInfoObj->fps < 30)
                {
                    $taskSettingBox->dstFps = 24;
                }
                else
                {
                    $taskSettingBox->dstFps = $videoInfoObj->fps;
                }
            }

            $flags = [
                'type'     => $taskSettingBox->dstCode,
                'height'   => $taskSettingBox->dstMaxHeight,
                'maxRateK' => "{$taskSettingBox->dstMaxRateK}K",
                'fps'      => $taskSettingBox->dstFps,
            ];

            if ($expectHeight > $videoInfoObj->height)
            {
                $flags['height'] = $videoInfoObj->height;;
            }

        }


        $taskInfo = [];


        $taskInfo['expect']    = $flags;
        $taskInfo['hasExpect'] = true;


        $ts1   = time();
        $date1 = date('Y-m-d H：i：s', $ts1);

        $this->printer->tabEcho('DST:' . json_encode($taskSettingBox->getDstInfo(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->printer->endTabEcho('make_single_video_task', 'make task end');
        return $taskInfo;


    }

    public function make()
    {
        $this->mark();
        Sys::app()->initPrinter();
        $printer = $this->printer;
        $this->printer->setOutputState(true);

        $dst_flag   = $this->inputBox->tryGetString('dst_flag');
        $srcRootDir = $this->inputBox->tryGetString('dir');
        $forceRenew = $this->inputBox->tryGetString('force_renew') === 'yes';

        if (empty($srcRootDir))
        {
            $srcRootDir = '/mnt/f/tmp2/format/wait/src';
        }
        chmod($srcRootDir, 0777);

        $getDirsCmd = "find '{$srcRootDir}' -maxdepth 2 -type d|grep -v FFOutput_res|grep -v FFOutput_tmp";
        $getDirsCmd = "find '{$srcRootDir}' -maxdepth 2 -type d|grep -v FFOutput";

        //$getDirsCmd        = "find '{$srcRootDir}' -maxdepth 2 -type d";
        $clearEmptyDirsFun = function ($srcRootDir) use ($getDirsCmd)
        {
            echo "\n{$getDirsCmd}\n";
            exec($getDirsCmd, $dirs);
            foreach ($dirs as $dir)
            {
                if ($dir === $srcRootDir || $dir === "{$srcRootDir}/FFOutput_res" || $dir === "{$srcRootDir}/FFOutput_tmp" || $dir === "{$srcRootDir}/FFOutput")
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
            $clearEmptyDirsFun($srcRootDir);
        }

        $this->printer->setOutputState(false);

        $this->printer->setOutputState(true);
        $taskM = new FfmpegTask();
        $this->printer->newTabEcho('try_insert_new', 'try_insert_new 先记录文件');

        $this->printer->endTabEcho('try_insert_new', 'try_insert_new');

        $id36toFileinfoKV = $taskM->getId36KV($srcRootDir, []);


        $taskMs = $taskM->findAllByAttributes(['is_ok' => Def::staNot, 'JSON_LENGTH(src_video_info) = 0']);
        //$taskMs = $taskM->findAllByAttributes(['is_ok' => Def::staNot, 'video_title like "%CWPBD%"']);
        $taskMs = $taskM->findAllByAttributes(['is_ok' => Def::staNot,]);
        $ids    = array_values(array_map(function ($m) { return $m->id; }, $id36toFileinfoKV));
        $taskMs = $taskM->findAllByAttributes(['id' => $ids]);
        // var_dump($ids,$taskMs);
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

        //关闭自动匹配分辨率
        $EXPECT_TAG2CFG_KV = [];

        echo "\n";


        $ts       = time();
        $true_i   = 0;
        $true_cnt = count($taskMs);
        $this->printer->newTabEcho('make dst config', "make task config ,task cnt:{$true_cnt} 准备获取videos info\n");

        foreach ($taskMs as $i => $taskM)
        {
            $taskM->initData();

            //   $taskM->getSrcVideoInfo($srcRootDir);


            $true_i++;

            if (!isset($id36toFileinfoKV[$taskM->id36]))
            {
                $this->printer->tabEcho("\n\n\n\ncurr: {$true_i}/{$true_cnt}    ID:{$taskM->id}  #SRC NOT EXIST#   ERR:{$taskM->is_err} OK:{$taskM->is_ok}  PRE:{$taskM->is_pre} SRC_EXIST:NOT_EXIST EXT:{$taskM->video_ext} \n{$taskM->video_title}   \n");
                if ($taskM->is_src_exist === Def::staYes)
                {
                    $taskM->is_src_exist = Def::staNot;
                    $taskM->is_ok        = Def::staYes;
                    $taskM->save();
                    $this->printer->tabEcho("update SRC NOT EXIST");
                }
                continue;
            }

            $taskM->is_src_exist = Def::staYes;
            $taskM->save();


            $lsCmdFileinfo = $id36toFileinfoKV[$taskM->id36];

            if (strlen($lsCmdFileinfo->albumNameDir) > 0)
            {
                $albumDirPath = "/{$lsCmdFileinfo->albumNameDir}/";
            }
            else
            {
                $albumDirPath = "/";
            }
            $videoAlbumAndTitleStr = strtolower("{$albumDirPath}{$lsCmdFileinfo->title}");

            $this->printer->tabEcho("file:{$lsCmdFileinfo->fullFilename}");

            $this->printer->endTabEcho('make_single_video_task');

            $this->printer->tabEcho("\n\n\n\ncurr: {$true_i}/{$true_cnt}    ID:{$taskM->id}  ERR:{$taskM->is_err} OK:{$taskM->is_ok}  PRE:{$taskM->is_pre} SRC_EXIST:{$taskM->is_src_exist} EXT:{$lsCmdFileinfo->ext} \n{$lsCmdFileinfo->fullFilename}   \n");


            $this->printer->newTabEcho('make_single_video_task', 'make single task,get video info');
            $videoInfoObj = new FileVideoInfo($lsCmdFileinfo->fullFilename);
            $videoInfoObj->setPrinter($printer);
            // $videoInfoObj->initFilename();
            // var_dump($videoInfoObj->getHalfTrimFlagFullPath(), $videoInfoObj->getFullTrimFlagFullPath());die;
            // if (in_array($ext, ['ts', 'mkv'], true))
            $lowerExt = strtolower($lsCmdFileinfo->ext);
            if (!in_array($lowerExt, ['mp4', 'avi'], true))
            {
                $taskM->is_pre = Def::staYes;
                $taskM->save();
                $this->printer->tabEcho('not avi/mp4  ,skip');
                continue;
            }

            if (isset($taskM->src_video_info['width']))
            {
                $videoInfoObj->width           = intval($taskM->src_video_info['width']);
                $videoInfoObj->height          = intval($taskM->src_video_info['height']);
                $videoInfoObj->fps             = intval($taskM->src_video_info['fps']);
                $videoInfoObj->rateK           = intval($taskM->src_video_info['rateK']);
                $videoInfoObj->durationSeconds = intval($taskM->src_video_info['duration']);
                $videoInfoObj->isPhone         = $videoInfoObj->height > $videoInfoObj->width;
            }
            else
            {
                $msgs = [];
                if ($videoInfoObj->initVideoInfo($msgs) === false)
                {
                    $taskM->is_err = Def::staYes;
                    $taskM->save();
                    $msgsStr = join('|', $msgs);
                    echo CLIStrFormatter::error("ERROR initVideo失败\n{$lsCmdFileinfo->fullFilename}\n{$msgsStr}\n");
                    $this->printer->tabEcho('error  ,skip');
                    continue;
                }
            }


            $this->printer->tabEcho("\nSRC:width:{$videoInfoObj->width} height:{$videoInfoObj->height} fps:{$videoInfoObj->fps} bit_rate:{$videoInfoObj->rateK} duration:{$videoInfoObj->durationSeconds}");
            if (empty($videoInfoObj->fps) || empty($videoInfoObj->width) || empty($videoInfoObj->height) || empty($videoInfoObj->rateK))
            {
                $taskM->is_err = Def::staYes;
                $taskM->save();
                $this->printer->tabEcho(CLIStrFormatter::error("ERROR video_info_lost {$lsCmdFileinfo->fullFilename}\n"));

                continue;
            }

            $taskSettingBox = new TaskSettingBox($videoInfoObj);

            $isPhone = $videoInfoObj->isPhone;


            foreach ($KEEP_SRC_VIDEO_KWS as $kw)
            {
                if (strstr($taskSettingBox->src->baseName, $kw))
                {
                    $taskSettingBox->delSrc = false;
                    break;
                }
            }
            if ($taskSettingBox->delSrc)
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
                    if (strstr($videoAlbumAndTitleStr, $kw) || strstr($videoAlbumAndTitleStr, $kw))
                    {
                        $tmpMatches[] = $kw;
                    }
                }
                foreach ($cfg['pregs'] as $pattern)
                {
                    if (preg_match($pattern, $videoAlbumAndTitleStr))
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
            if ($expectRateK > $videoInfoObj->rateK)
            {
                if ($videoInfoObj->fps < $MIN_FPS)
                {
                    $taskSettingBox->dstFlag = 'ffCopy';
                    $taskSettingBox->dstFlag = 'low2';

                    $fpss = [12, 15, 18, 24, 30];
                    if (in_array($videoInfoObj->fps, $fpss))
                    {
                        $taskSettingBox->dstFps = $videoInfoObj->fps;
                    }
                    else
                    {
                        foreach ($fpss as $fps)
                        {
                            if ($videoInfoObj->fps < $fps)
                            {
                                break;
                            }
                            $taskSettingBox->dstFps = $fps;
                        }

                    }
                    $taskSettingBox->dstMaxRateK  = intval($videoInfoObj->rateK * 0.9);
                    $taskSettingBox->dstMaxHeight = $videoInfoObj->height;
                    $this->printer->tabEcho("rate  video < DEFAULT && fps  video < MIN   {$videoInfoObj->rateK}  < {$expectRateK} / {$videoInfoObj->fps} < {$MIN_FPS} ,so dst flag is [ low2 ]");

                    $flags = [
                        'type'     => $taskSettingBox->dstCode,
                        'height'   => $taskSettingBox->dstMaxHeight,
                        'maxRateK' => "{$taskSettingBox->dstMaxRateK}K",
                        'fps'      => $taskSettingBox->dstFps,
                    ];

                }
                else
                {
                    $taskSettingBox->dstMaxRateK = intval($videoInfoObj->rateK * 0.9);
                    $this->printer->tabEcho("rate  video < DEFAULT && fps  video > MIN  {$videoInfoObj->rateK}  < {$expectRateK}  / {$videoInfoObj->fps} > {$MIN_FPS} ,so dst rate is [ 9/10 *video.rate ]");

                }
            }
            else
            {
                $taskSettingBox->dstMaxRateK = $expectRateK;
                $this->printer->tabEcho("rate  video > DEFAULT   {$videoInfoObj->rateK}  > {$expectRateK} ,so dst rate is [ DEFAULT ]");

            }


            if ($taskSettingBox->dstFlag === 'ffCopy')
            {

            }
            else if ($taskSettingBox->dstFlag === 'low2')
            {
                $flags['copy'] = false;

            }
            else
            {
                $flags = ['copy' => false];
                if ($isPhone)
                {
                    if ($videoInfoObj->height >= $MAX_HEIGHT)
                    {
                        $taskSettingBox->dstMaxHeight = $MAX_HEIGHT;
                    }
                    else
                    {
                        if ($videoInfoObj->height >= $MIN_HEIGHT)
                        {
                            $taskSettingBox->dstMaxHeight = $MIN_HEIGHT;
                        }
                    }
                }
                else
                {
                    if ($videoInfoObj->height >= $expectHeight)
                    {
                        $taskSettingBox->dstMaxHeight = $expectHeight;
                    }
                    else if ($videoInfoObj->height >= $MIN_HEIGHT)
                    {
                        $taskSettingBox->dstMaxHeight = $MIN_HEIGHT;
                    }
                }

                if ($videoInfoObj->fps >= $expectFps)
                {
                    $taskSettingBox->dstFps = $expectFps;
                }
                else
                {
                    if ($videoInfoObj->fps > 24 && $videoInfoObj->fps < 30)
                    {
                        $taskSettingBox->dstFps = 24;
                    }
                    else
                    {
                        $taskSettingBox->dstFps = $videoInfoObj->fps;
                    }
                }

                $flags = [
                    'type'     => $taskSettingBox->dstCode,
                    'height'   => $taskSettingBox->dstMaxHeight,
                    'maxRateK' => "{$taskSettingBox->dstMaxRateK}K",
                    'fps'      => $taskSettingBox->dstFps,
                ];

                if ($expectHeight > $videoInfoObj->height)
                {
                    $flags['height'] = $videoInfoObj->height;;
                }

            }

            if (!isset($taskM->src_video_info['width']))
            {
                $taskM->src_video_info = [
                    'width'    => $videoInfoObj->width,
                    'height'   => $videoInfoObj->height,
                    'rateK'    => $videoInfoObj->rateK,
                    'fps'      => $videoInfoObj->fps,
                    'duration' => $videoInfoObj->durationSeconds,
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

            $this->printer->tabEcho('DST:' . json_encode($taskSettingBox->getDstInfo(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $this->printer->endTabEcho('make_single_video_task', 'make task end');
        }
        $this->printer->endTabEcho('make_single_video_task');
        $this->printer->newTabEcho('check_exist_state', 'check_not_exist');
        //$taskMs = $taskM->findAllByAttributes(['is_ok' => Def::staNot,]);
        $taskMs = $taskM->findAllByAttributes(['is_src_exist' => Def::staYes,]);
        foreach ($taskMs as $i => $taskM)
        {
            $srcInfo = $taskM->getSrcVideoInfo($srcRootDir);
            if (!isset($id36toFileinfoKV[$taskM->id36]))
            {
                $taskM->is_src_exist = Def::staNot;
                $taskM->is_ok        = Def::staYes;
                $taskM->save();
                $this->printer->tabEcho("check_exist_state [{$taskM->id}] {$taskM->create_date}/{$taskM->update_date} {$taskM->video_title}.{$taskM->video_ext} ");
            }
            else
            {
                $taskM->is_src_exist = Def::staYes;
                // $taskM->is_ok        = Def::staNot;
                $taskM->save();
                $this->printer->tabEcho("check_exist_state [{$taskM->id}] {$taskM->create_date}/{$taskM->update_date} [{$id36toFileinfoKV[$taskM->id36]->filesize}] {$taskM->album_dir}/{$taskM->video_title}.{$taskM->video_ext} ");
            }

        }
        $this->printer->endTabEcho('check_exist_state', 'check_not_exist');
        $this->printer->endTabEcho('make dst config', "make task config");
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
            case 'avi':
            case 'mp4':
                $cmd = "ffmpeg -hwaccel cuda -hwaccel_output_format cuda -i '{$srcFile}' \
  -map 0:v:0 -c:v hevc_nvenc -rc vbr -cq 22 -maxrate 3600k -bufsize 9000k \
  -vf \"scale_cuda=-2:if(gte(ih\,1080)\,1080\,ih),fps=24,setpts=PTS-STARTPTS\" \
  -preset p7 -enc_time_base -1 -vsync 0 \
  -map 0:a? -c:a aac -ac 2 -b:a 192k -af \"volume=5dB\" \
  -map_metadata 0 -movflags use_metadata_tags \
  -avoid_negative_ts make_zero -write_tmcd 0 \
  '{$dstFile}'";
                break;
            default:
                die("\nno_dst_flag [{$type}] !!!\n");
                break;
        }
        echo CLIStrFormatter::getStr("\nconvFuckType2Mp4 CMD:\n{$cmd}\n", CLIStrFormatter::blue, CLIStrFormatter::white);

        passthru($cmd);
    }


    public function fetch()
    {

        echo "\nfetchTasks\n";

        Sys::app()->initPrinter();
        $keepTmp = $this->inputBox->tryGetString('keep_tmp') === 'yes';

        $printer       = Sys::app()->getPrinter()->setData2TextType('json');
        $this->printer = $printer;

        $srcRootDir = $this->inputBox->tryGetString('dir');
        if (empty($rootDir))
        {
            $srcRootDir = '/mnt/f/tmp2/format/wait/src';
        }
        $tmpRootDir = dirname($srcRootDir) . "/FFOutput_tmp";//不要直接拼接，因为有时候一开始就是src
        $dstRootDir = dirname($srcRootDir) . "/FFOutput_res";
        $preRootDir = dirname($srcRootDir) . "/FFOutput_pre";

        chmod($srcRootDir, 0777);
        chmod($tmpRootDir, 0777);
        chmod($dstRootDir, 0777);
        chmod($preRootDir, 0777);


        $taskM = new FfmpegTask();
        $tn    = $taskM->getTableName();
        $db    = $taskM->getConnection();

        $expectModel = new TaskExpect();
        $expectModel->setPrinter($this->printer);
        $expectModel->ignoreKwsMatch();


        $taskMs = $taskM->findAllByAttributes(['is_ok' => Def::staNot,]);
        //$taskMs = $taskM->findAllByAttributes(['id' => 172,]);
        $id36toSrcFileinfoKV = $taskM->getId36KV($srcRootDir);
        echo "\n";
        echo json_encode($id36toSrcFileinfoKV, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\n";
        $ts        = time();
        $true_i    = 0;
        $true_cnt  = count($taskMs);
        $swithFile = __APP_DIR__ . '/config/file/xx_video/php_task_state.txt';
        for ($i = -1; $i < $true_cnt; $i++)
        {
            while (true)
            {

                $sta = trim(file_get_contents($swithFile));
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
            echo "\n****************************************************************1\n";
            $taskM = (new FfmpegTask())->findByAttributes(['is_ok' => Def::staNot, 'run_lc' => 0]);
            if (empty($taskM))
            {
                echo CLIStrFormatter::success("\ncurr: {$true_i}/{$true_cnt} \n");
                continue;
            }
            $taskM->initData();
            $true_i++;


            if (!isset($id36toSrcFileinfoKV[$taskM->id36]))
            {
                $this->printer->tabEcho("\n\n\n\ncurr: {$true_i}/{$true_cnt}    ID:{$taskM->id}  ERR:{$taskM->is_err} OK:{$taskM->is_ok}  PRE:{$taskM->is_pre} SRC_EXIST:NOT_EXIST EXT:{$taskM->video_ext} \n{$taskM->video_title}   \n");
                $this->printer->tabEcho(CLIStrFormatter::error("ID:{$taskM->id}  {$taskM->id36} not found in kv"));
                $taskM->is_src_exist = Def::staNot;
                $taskM->is_ok        = Def::staYes;
                $taskM->save();
                continue;
            }


            $lsCmdFileinfo = $id36toSrcFileinfoKV[$taskM->id36];

            $taskM->getLsCmdFileinfosBySrc($lsCmdFileinfo);

            if (strlen($lsCmdFileinfo->albumNameDir) > 0)
            {
                $albumDirPath = "/{$lsCmdFileinfo->albumNameDir}/";
            }
            else
            {
                $albumDirPath = "/";
            }


            echo CLIStrFormatter::getStr("\ncurr: {$true_i}/{$true_cnt}   ID:{$taskM->id}  ERR:{$taskM->is_err} OK:{$taskM->is_ok}  PRE:{$taskM->is_pre} SRC_EXIST:{$taskM->is_src_exist} EXT:{$taskM->video_ext} Lock:{$taskM->run_lc}   \n{$lsCmdFileinfo->fullFilename}\n", CLIStrFormatter::green, CLIStrFormatter::white);
            echo "\n" . json_encode($taskM->src_video_info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $lc = intval(date('YmdHis'));
            $db->setText("update {$tn}  set run_lc={$lc} ,try_times=try_times+1 where id={$taskM->id} and run_lc=0")->execute();
            if (intval($db->setText("select  run_lc from {$tn}  where id={$taskM->id}")->queryScalar()) !== $lc)
            {
                echo "\n lock fail,continue\n";
                continue;
            }
            echo "\n****************************************************************2\n";
            //  echo "{$willStr}";
            echo "\ncurr : {$true_i}/{$true_cnt}    {$lsCmdFileinfo->fullFilename}  \n";
            $srcVideo = new FileVideoInfo($lsCmdFileinfo->fullFilename);
            $srcVideo->setPrinter($printer);

            $lowerExt = strtolower($srcVideo->ext);
            if ($taskM->is_pre === Def::staYes)
            {
                if ($taskM->is_pre_ok === Def::isError)
                {
                    echo(CLIStrFormatter::error("pre时报错了，需要更改状态 {$taskM->id36}\n"));
                    continue;
                }


                if (in_array($lowerExt, ['ts', 'mkv', 'mov', 'm2ts', 'flv', 'wmv', 'avi', 'mp4'], true))
                {
                    $this->printer->tabEcho(CLIStrFormatter::warning($taskM->preLsCmdFileinfo->fullFilename));
                    if ($keepTmp)
                    {
                        if (!file_exists($taskM->preLsCmdFileinfo->fullFilename))
                        {
                            $this->convFuckType2Mp4($lowerExt, $lsCmdFileinfo->fullFilename, $taskM->preLsCmdFileinfo->fullFilename);
                        }
                    }
                    else
                    {
                        if (file_exists($taskM->preLsCmdFileinfo->fullFilename))
                        {
                            unlink($taskM->preLsCmdFileinfo->fullFilename);
                            usleep(1000);
                        }
                        $this->convFuckType2Mp4($lowerExt, $lsCmdFileinfo->fullFilename, $taskM->preLsCmdFileinfo->fullFilename);
                    }

                    usleep(1000);
                    $preVideoInfoObj = new FileVideoInfo($taskM->preLsCmdFileinfo->fullFilename);
                    $preVideoInfoObj->setPrinter($printer);
                    if ($preVideoInfoObj->initVideoInfo())
                    {
                        if (empty($taskM->task_info['expect']['maxRateK']))
                        {
                            $newExpectSettingBox = $expectModel->loadLsCmdFileinfo($taskM->preLsCmdFileinfo)->loadVideoFileInfo($preVideoInfoObj)->getAutoExpectTaskSettingBox();
                            if ($newExpectSettingBox === false)
                            {
                                echo(CLIStrFormatter::error("ERROR getAutoExpectSetting fail\n"));
                                continue;
                            }
                            else
                            {
                                $taskM->task_info = [
                                    'hasExpect' => true,
                                    'expect'    => $newExpectSettingBox->getOpenInfo()
                                ];
                            }
                        }


                        $taskM->is_pre_ok      = Def::isOK;
                        $taskM->pre_video_info = [
                            'src'      => $taskM->preLsCmdFileinfo->title,
                            'width'    => $preVideoInfoObj->width,
                            'height'   => $preVideoInfoObj->height,
                            'rateK'    => $preVideoInfoObj->rateK,
                            'fps'      => $preVideoInfoObj->fps,
                            'duration' => $preVideoInfoObj->durationSeconds,
                        ];

                        echo(CLIStrFormatter::warning("PRE OK: {$lsCmdFileinfo->fullFilename}\n{$taskM->preLsCmdFileinfo->fullFilename}\n"));
                        echo(CLIStrFormatter::warning(json_encode($taskM->pre_video_info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)));
                        $taskM->save();


                    }
                    else
                    {
                        $taskM->is_err = Def::isError;
                        $taskM->save();
                        echo(CLIStrFormatter::error("ERROR conv_fail {$lsCmdFileinfo->fullFilename}\n{$taskM->preLsCmdFileinfo->fullFilename}\n"));
                        continue;
                    }

                }
                else
                {
                    echo(CLIStrFormatter::error("ERROR ext  not_support_pre {$lsCmdFileinfo->fullFilename}\n"));
                    continue;
                }
            }


            $ts1   = time();
            $date1 = date('Y-m-d H：i：s', $ts1);

            echo "[$date1] ready to convd: {$i}/{$true_cnt} \n{$lsCmdFileinfo->fullFilename}\n";

            // var_dump($new_filename);
            //$this->convAction($new_file_flag, $video_path, $new_filename, $video_max_bitrateK_set, $tmpMatches);
            echo json_encode($taskM->task_info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);


            //$ffCmd->delSrc=true;

            //
            $this->convTask($taskM, $lsCmdFileinfo);

            $ts2       = time();
            $date2     = date('Y-m-d H：i：s', $ts2);
            $cost_curr = $ts2 - $ts1;
            $cost_all  = $ts2 - $ts;
            $this->printer->tabEcho("conv ok <<");
            echo "\n" . json_encode($taskM->src_video_info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);;
            echo "\n[$date2] curr : {$true_i}/{$true_cnt}   curr cost:{$cost_curr}  all cost:{$cost_all}\n";
            $this->printer->tabEcho(">> conv ok");

            //die;
        }
    }

    public function convTask(FfmpegTask $taskM, LsCmdFileinfo $srcLsCmmFileinfo)
    {
        if (!isset($taskM->task_info['expect']['maxRateK']) && !isset($taskM->task_info['expect']['copy']))
        {
            $this->printer->tabEcho($taskM->task_info);
            $this->printer->tabEcho(CLIStrFormatter::error("TASK DATA  expect error\n"));
            return false;
        }
        $expectSetting = $taskM->task_info['expect'];

        if (strlen($taskM->album_dir) > 0)
        {
            $albumDirPath = "/{$taskM->album_dir}/";
        }
        else
        {
            $albumDirPath = "/";

        }
        $taskM->getLsCmdFileinfosBySrc($srcLsCmmFileinfo);

        $tmpTitle = $taskM->getTmpTitle();


        $mixSrcVideoFullname = '';
        $isPreOK             = false;
        if ($taskM->is_pre === Def::staYes)
        {

            if ($taskM->is_pre_ok === Def::isOK && $taskM->preLsCmdFileinfo->filesize > 0)
            {
                $isPreOK             = true;
                $mixSrcVideoFullname = $taskM->preLsCmdFileinfo->fullFilename;
            }
            else
            {
                $this->printer->tabEcho(CLIStrFormatter::error("PRE FAIL,{$taskM->id36}\n"));
                return false;
            }
        }
        else
        {
            $mixSrcVideoFullname = $taskM->srcLsCmdFileinfo->fullFilename;
        }

        //var_export($taskM->tmpLsCmdFileinfo);

        $tmpVideoDir = dirname($taskM->tmpLsCmdFileinfo->fullFilename);
        if (!file_exists($tmpVideoDir))
        {
            $this->printer->tabEcho("mkdir tmp [{$tmpVideoDir}]");
            mkdir($tmpVideoDir, 0777, true);
            unset($tmpVideoDir);
        }

        $dstResDir = dirname($taskM->dstLsCmdFileinfo->fullFilename);
        if (!file_exists($dstResDir))
        {
            $this->printer->tabEcho("mkdir res [{$dstResDir}]");
            mkdir($dstResDir, 0777, true);
            unset($dstResDir);
        }
        if (file_exists($taskM->tmpLsCmdFileinfo->fullFilename))
        {
            @unlink($taskM->tmpLsCmdFileinfo->fullFilename);
            usleep(3000);
        }

        if (is_file($taskM->dstLsCmdFileinfo->fullFilename))
        {
            @unlink($taskM->dstLsCmdFileinfo->fullFilename);
        }


        $cmdTpl        = '';
        $manualSetting = $taskM->task_info['manual'] ?? [];
        $resTypeAs     = $manualSetting['resAs'] ?? '';
        $resVer        = $manualSetting['resVer'] ?? '';
        if ($isPreOK === false && (isset($manualSetting['isCopy']) && ($manualSetting['isCopy'] === true || $manualSetting['isCopy'] === 'true')))
        {
            //-------------------------------------------------------------------
            //修复路线
            //-------------------------------------------------------------------
            $this->printer->tabEcho(CLIStrFormatter::info("走修复路线\n" . json_encode($manualSetting) . "\n"));
            $this->printer->tabEcho($manualSetting);

            $cutSs = 0;
            $cutTo = 0;
            $csf   = 22;
            if (isset($taskM->task_info['manual']['ss']) || isset($taskM->task_info['manual']['to']))
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

            switch ($resTypeAs)
            {
                case 'mkv':
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
                    switch ($resVer)
                    {
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
                            /*
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
                            */

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

            $fixCmd = str_replace(['{srcFile}', '{resFile}'], [$mixSrcVideoFullname, $taskM->tmpLsCmdFileinfo->fullFilename], $fixCmdTpl);
            if (file_exists($taskM->tmpLsCmdFileinfo->fullFilename))
            {
                @unlink($taskM->tmpLsCmdFileinfo->fullFilename);
            }
            echo "\nfix tmp special video CMD:\n{$fixCmd}\n";
            echo "\nfix tmp special video CMD tpl:\n{$fixCmdTpl}\n";

            passthru($fixCmd);


            $resFile = new FileVideoInfo($taskM->tmpLsCmdFileinfo->fullFilename);
            $resFile->setPrinter($this->printer);
            if ($resFile->initVideoInfo())
            {
                rename($taskM->tmpLsCmdFileinfo->fullFilename, $taskM->dstLsCmdFileinfo->fullFilename);
                usleep(300);
                if (is_file($taskM->dstLsCmdFileinfo->fullFilename) && filesize($taskM->dstLsCmdFileinfo->fullFilename) > 10000)
                {
                    $taskM->is_ok          = Def::staYes;
                    $taskM->dst_video_info = [
                        'src'      => $taskM->dstLsCmdFileinfo->fullFilename,
                        'width'    => $resFile->width,
                        'height'   => $resFile->height,
                        'rateK'    => $resFile->rateK,
                        'fps'      => $resFile->fps,
                        'duration' => $resFile->durationSeconds,
                    ];
                    $taskM->save();
                    $this->printer->tabEcho(CLIStrFormatter::warning("已经修复了特殊视频，请查看：{taskM->dstLsCmdFileinfo->fullFilename}"));
                }
                else
                {
                    $this->printer->tabEcho(CLIStrFormatter::error("修复特殊视频成功了，但是rename失败了，请检查：\n{$taskM->tmpLsCmdFileinfo->fullFilename}\n{$taskM->dstLsCmdFileinfo->fullFilename}"));
                }

            }
            else
            {
                $this->printer->tabEcho(CLIStrFormatter::error("修复特殊视频失败，请检查：{$taskM->tmpLsCmdFileinfo->fullFilename}"));
            }
            return false;
        }
        else
        {
            //-------------------------------------------------------------------
            //正常路线
            //-------------------------------------------------------------------
            $this->printer->tabEcho(CLIStrFormatter::info('走正常路线'));


            $dstMaxRateK  = intval(str_replace('K', '', $expectSetting['maxRateK']));
            $dstMaxHeight = intval($expectSetting['height']);
            $dstFps       = intval($expectSetting['fps']);

            $cutSs            = 0;
            $cutTo            = 0;
            $csf              = 22;
            $taskInfo         = $taskM->task_info;
            $extParamSrt      = '';
            $manualSettingExt = [];
            if (isset($taskInfo['manual']['maxRateK']))
            {
                $manualSetting = $taskInfo['manual'];
                $dstMaxRateK   = intval(str_replace('K', '', $manualSetting['maxRateK']));
                $dstMaxHeight  = intval($manualSetting['height']);
                $dstFps        = intval($manualSetting['fps']);

                $cutSs = intval($manualSetting['ss'] ?? 0);
                $cutTo = intval($manualSetting['to'] ?? 0);
                if (isset($manualSetting['csf']))
                {
                    $csf = intval($manualSetting['csf']);
                }
                if (isset($manualSetting['ext']))
                {
                    $manualSettingExt = $manualSetting['ext'];
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
            if (isset($manualSettingExt['transpose']) && $manualSettingExt['transpose'])
            {
                $vfs[] = $manualSettingExt['transpose'];
            }
            if ($dstMaxHeight > 0)
            {
                if ($resTypeAs === 'h264')
                {
                    $vfs[] = "scale_cuda=-2:if(gte(ih\,{$dstMaxHeight})\,{$dstMaxHeight}\,ih):format=yuv420p";
                }
                else
                {
                    $vfs[] = "scale=-2:if(gte(ih\,{$dstMaxHeight})\,{$dstMaxHeight}\,ih)";
                }
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
            /**
             * $common_cmd = "ffmpeg -hide_banner -i '{$srcFile}' {$cutStr} \
             * -map 0:v:0 -c:v hevc_nvenc -crf {$csf} {maxrateStr} -bufsize {$bufsize}k \
             * {vfStr} -preset p7 \
             * -map 0:a? -c:a aac -ac 2 -b:a 192k -af \"loudnorm=I=-16:TP=-1.5:LRA=11\" \
             * -map 0:s? -c:s mov_text \
             * -map_metadata 0 -movflags use_metadata_tags \
             * '{$tmpVideoAbsoluteFilename}'";
             **/
            if ($resTypeAs === 'h264')
            {
                //ffmpeg -hwaccel cuda -hwaccel_output_format cuda -i '/mnt/f/tmp2/format/wait/src/cant_play_ol_1^91f^.mp4' \
                //  -map 0:v:0 -c:v h264_nvenc -rc vbr -cq 22 -maxrate 1600k -bufsize 9000k \
                //  -vf "scale_cuda=-2:if(gte(ih\,1080)\,1080\,ih):format=yuv420p,fps=24,setpts=PTS-STARTPTS" \
                //  -preset p7 -enc_time_base -1 -vsync 0 \
                //  -map 0:a? -c:a aac -ac 2 -b:a 192k -af "volume=5dB" \
                //  -map_metadata 0 -movflags use_metadata_tags \
                //  -avoid_negative_ts make_zero -write_tmcd 0 \
                //  '/mnt/f/tmp2/format/wait/FFoutput_pre/11715^91f^.mp4'
                $common_cmd = "ffmpeg -hwaccel cuda -hwaccel_output_format cuda -i '{$mixSrcVideoFullname}' {$cutStr}\
  -map 0:v:0 -c:v h264_nvenc -rc vbr -cq {$csf} {maxrateStr} -bufsize {$bufsize}k {$extParamSrt}\
  {vfStr} \
  -preset p7 -enc_time_base -1 -vsync 0 \
  -map 0:a? -c:a aac -ac 2 -b:a 192k -af \"volume=5dB\" \
  -map_metadata 0 -movflags use_metadata_tags \
  -avoid_negative_ts make_zero -write_tmcd 0 \
  '{$taskM->tmpLsCmdFileinfo->fullFilename}'";
                $cmdTpl     = "ffmpeg -hwaccel cuda -hwaccel_output_format cuda -i '{srcFile}' {$cutStr}\
  -map 0:v:0 -c:v h264_nvenc -rc vbr -cq {$csf} {maxrateStr} -bufsize {$bufsize}k {$extParamSrt}\
  {vfStr} \
  -preset p7 -enc_time_base -1 -vsync 0 \
  -map 0:a? -c:a aac -ac 2 -b:a 192k -af \"volume=5dB\" \
  -map_metadata 0 -movflags use_metadata_tags \
  -avoid_negative_ts make_zero -write_tmcd 0 \
  '{resFile}'";
            }
            else
            {
                //ffmpeg -hide_banner -copyts -start_at_zero -i '/mnt/f/tmp2/format/wait/FFoutput_pre/11715^91f^.mp4'  \
                //-map 0:v:0 -c:v hevc_nvenc -crf 0 -maxrate 1600k -bufsize 4000k  \
                //-vf "scale=-2:if(gte(ih\,720)\,720\,ih),fps=24,setpts=PTS-STARTPTS" -preset p7 \
                //-map 0:a? -c:a aac -ac 2 -b:a 192k -af "loudnorm=I=-16:TP=-1.5:LRA=11,asetpts=PTS-STARTPTS" \
                //-map 0:s? -c:s mov_text \
                //-map_metadata 0 -movflags use_metadata_tags \
                //'/mnt/f/tmp2/format/wait/FFoutput_tmp/11715^91f^.mp4'
                $common_cmd = "ffmpeg -hide_banner -copyts -start_at_zero -i '{$mixSrcVideoFullname}' {$cutStr} \
-map 0:v:0 -c:v hevc_nvenc -crf {$csf} {maxrateStr} -bufsize {$bufsize}k {$extParamSrt} \
{vfStr} -preset p7 \
-map 0:a? -c:a aac -ac 2 -b:a 192k -af \"loudnorm=I=-16:TP=-1.5:LRA=11,asetpts=PTS-STARTPTS\" \
-map 0:s? -c:s mov_text \
-map_metadata 0 -movflags use_metadata_tags \
'{$taskM->tmpLsCmdFileinfo->fullFilename}'";
                $cmdTpl     = "ffmpeg -hide_banner -copyts -start_at_zero -i '{srcFile}' {$cutStr} \
-map 0:v:0 -c:v hevc_nvenc -crf {$csf} {maxrateStr} -bufsize {$bufsize}k {$extParamSrt} \
{vfStr} -preset p7 \
-map 0:a? -c:a aac -ac 2 -b:a 192k -af \"loudnorm=I=-16:TP=-1.5:LRA=11,asetpts=PTS-STARTPTS\" \
-map 0:s? -c:s mov_text \
-map_metadata 0 -movflags use_metadata_tags \
'{resFile}'";
            }


            $paramKeys = array_keys($map);
            $paramVals = array_values($map);
            $cmd       = str_replace($paramKeys, $paramVals, $common_cmd);
            $cmdTpl    = str_replace($paramKeys, $paramVals, $cmdTpl);



            echo CLIStrFormatter::getStr("\nmake tmp video CMD:\n{$cmd}\n", CLIStrFormatter::blue, CLIStrFormatter::white);
            echo CLIStrFormatter::getStr("\nmake tmp video CMD tpl:\n{$cmdTpl}\n", CLIStrFormatter::black, CLIStrFormatter::white);
            passthru($cmd);
            // echo CLIStrFormatter::error("ERROR initVideo失败\n{$video_path}\n{$msgsStr}\n");

            $this->printer->tabEcho(CLIStrFormatter::warning("已经生成tmp文件"));
            $this->printer->tabEcho("已经生成临时文件，准备截图");


            // 1. 获取视频时长
            $cmdDuration    = "ffprobe -v error -show_entries format=duration -of csv=p=0 " . escapeshellarg($taskM->tmpLsCmdFileinfo->fullFilename);
            $cmdDurationRes = shell_exec($cmdDuration);
            if (empty($cmdDurationRes))
            {
                echo CLIStrFormatter::error("ERROR 获取临时文件时长失败\n{$taskM->tmpLsCmdFileinfo->fullFilename}\n");
                $taskM->logError('获取临时文件时长失败');
                $taskM->is_tmp_ok = Def::isError;
                $taskM->save();
                return false;
            }
            $duration = floatval(trim($cmdDurationRes));
            if ($duration <= 0)
            {
                echo CLIStrFormatter::error("ERROR 获取临时文件时长失败\n{$taskM->tmpLsCmdFileinfo->fullFilename}\n");
                $taskM->logError('获取临时文件时长失败');
                $taskM->is_tmp_ok = Def::isError;
                $taskM->save();
                return false;
            }

            // 2. 计算中点时间
            $mid = $duration / 2;

            // 3. 提取中点帧
            $tmpCoverAbsoluteFilename = $taskM->tmpLsCmdFileinfo->fullFilename . '.jpg';
            $cmdCover                 = sprintf('ffmpeg -hide_banner -ss %.3f -i %s -vframes 1 -q:v 2 %s -y', $mid, escapeshellarg($taskM->tmpLsCmdFileinfo->fullFilename), escapeshellarg($tmpCoverAbsoluteFilename));
            echo CLIStrFormatter::getStr("\nmake cover CMD:\n{$cmdCover}\n", CLIStrFormatter::blue, CLIStrFormatter::white);
            exec($cmdCover, $output, $ret);
            if ($ret !== 0)
            {
                $tmpErrNsg = '从临时文件提取封面失败';
                echo CLIStrFormatter::error("ERROR {$tmpErrNsg}\n{$taskM->tmpLsCmdFileinfo->fullFilename}\n");
                $taskM->logError($tmpErrNsg);
                @unlink($tmpCoverAbsoluteFilename);
                usleep(1000);
            }


            if (file_exists($tmpCoverAbsoluteFilename))
            {
                $append_cover_cmd     = "ffmpeg -hide_banner -i '{$taskM->tmpLsCmdFileinfo->fullFilename}' -i '{$tmpCoverAbsoluteFilename}' \
-map 0 -map 1:v -c copy -c:v:1 copy -disposition:v:1 attached_pic \
'{$taskM->dstLsCmdFileinfo->fullFilename}'";
                $append_cover_cmd_tpl = "ffmpeg -hide_banner -i '{srcVideoFile}' -i '{srcImgFile}' \
-map 0 -map 1:v -c copy -c:v:1 copy -disposition:v:1 attached_pic \
'{resVideoFile}'";
                echo CLIStrFormatter::getStr("\nappend cover CMD:\n{$append_cover_cmd}\n", CLIStrFormatter::blue, CLIStrFormatter::white);
                echo CLIStrFormatter::getStr("\nappend cover CMD tpl:\n{$append_cover_cmd_tpl}\n", CLIStrFormatter::black, CLIStrFormatter::white);
                passthru($append_cover_cmd);
                @unlink($tmpCoverAbsoluteFilename);
                if (file_exists($taskM->dstLsCmdFileinfo->fullFilename))
                {
                    @unlink($taskM->tmpLsCmdFileinfo->fullFilename);
                }

                $resFile = new FileVideoInfo($taskM->dstLsCmdFileinfo->fullFilename);
                $resFile->setPrinter($this->printer);
                if ($resFile->initVideoInfo())
                {
                    $taskM->is_ok          = Def::staYes;
                    $taskM->dst_video_info = [
                        'src'      => $taskM->dstLsCmdFileinfo->fullFilename,
                        'width'    => $resFile->width,
                        'height'   => $resFile->height,
                        'rateK'    => $resFile->rateK,
                        'fps'      => $resFile->fps,
                        'duration' => $resFile->durationSeconds,
                    ];
                    $taskM->save();
                    if ($taskM->is_auto_del_src === Def::staYes)
                    {
                        if (file_exists($taskM->srcLsCmdFileinfo->fullFilename))
                        {
                            @unlink($taskM->srcLsCmdFileinfo->fullFilename);
                        }
                        if (file_exists($taskM->preLsCmdFileinfo->fullFilename))
                        {
                            @unlink($taskM->preLsCmdFileinfo->fullFilename);
                        }
                        usleep(200000);
                        if (!file_exists($taskM->srcLsCmdFileinfo->fullFilename))
                        {
                            $taskM->is_src_exist = Def::staNot;
                        }
                        if (!file_exists($taskM->preLsCmdFileinfo->fullFilename))
                        {
                            $taskM->is_pre_exist = Def::staNot;
                        }
                        $taskM->save();
                    }
                }


            }

        }


        //die;
    }

    public function old1()
    {

        // 处理函数
        function checkAndPrintOk($str, $target)
        {
            // 如果目标字符串为空，直接返回，避免除以零
            if (mb_strlen($target) == 0)
            {
                return false;
            }

            // “打散”为字符数组（支持中文等多字节字符）
            $arr1 = mb_str_split($str);      // PHP 7.4+ 可用
            $arr2 = mb_str_split($target);

            // 如果 PHP 版本低于 7.4，可以用下面这行代替：
            // $arr1 = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
            // $arr2 = preg_split('//u', $target, -1, PREG_SPLIT_NO_EMPTY);

            // 求交集
            $intersect = array_intersect($arr1, $arr2);

            // 将交集数组拼成字符串
            $tmpStr = implode('', $intersect);

            // 计算相似度比例
            $strLen = mb_strlen($tmpStr);
            $oldLen = mb_strlen($target);

            return [$strLen, $oldLen, $strLen / $oldLen];

        }

        Sys::app()->initPrinter();

        $printer       = Sys::app()->getPrinter()->setData2TextType('json');
        $this->printer = $printer;

        $srcRootDir = $this->inputBox->tryGetString('dir');
        if (empty($rootDir))
        {
            $srcRootDir = '/mnt/f/tmp2/format/wait/src';
        }

        $tmpRootDir = dirname($srcRootDir) . "/FFOutput_tmp";//不要直接拼接，因为有时候一开始就是src
        $dstRootDir = dirname($srcRootDir) . "/FFOutput_res";
        $preRootDir = dirname($srcRootDir) . "/FFOutput_pre";

        chmod($srcRootDir, 0777);
        chmod($tmpRootDir, 0777);
        chmod($dstRootDir, 0777);
        chmod($preRootDir, 0777);


        $taskM = new FfmpegTask();
        $tn    = $taskM->getTableName();
        $db    = $taskM->getConnection();

        $expectModel = new TaskExpect();
        $expectModel->setPrinter($this->printer);
        $expectModel->ignoreKwsMatch();


        $taskMs = $taskM->findAllByAttributes(['is_ok' => Def::staNot,]);
        //$taskMs = $taskM->findAllByAttributes(['id' => 172,]);
        $id36toSrcFileinfoKV = $taskM->getId36KV($srcRootDir);
        $cnt                 = count($id36toSrcFileinfoKV);
        $i                   = 0;
        foreach ($id36toSrcFileinfoKV as $id36 => $srcLsCmdInfoObj)
        {
            $i++;
            $matchess = [];
            $this->printer->tabEcho(CLIStrFormatter::getStr("{$i}/{$cnt}  {$srcLsCmdInfoObj->albumNameDir} / {$srcLsCmdInfoObj->title} \n", CLIStrFormatter::blue, CLIStrFormatter::white));
            $this->printer->newTabEcho('fetch_old', 'fetch old');
            $newTitle = preg_replace('/[^\p{Han}a-zA-Z\s]/u', '', "{$srcLsCmdInfoObj->albumNameDir}{$srcLsCmdInfoObj->title}");
            $this->printer->tabEcho($newTitle);

            preg_match_all('/\s(\d{3,4})/', $srcLsCmdInfoObj->title, $matches);
            //print_r($matches[1]);
            $this->printer->tabEcho(json_encode($matches[1]));
            $res       = [];
            $title2res = [];
            $this->printer->newTabEcho('check_matchs', "check ids:");

            $idCnt       = count($matches[1]);
            $findInDbCnt = 0;
            $isMatched   = false;
            foreach ($matches[1] as $idStr)
            {
                $this->printer->newTabEcho('check_match', "check:{$idStr}");
                $idStr       = intval($idStr);
                $newTaskM    = $taskM->findByPk($idStr);
                $res[$idStr] = -1;
                if ($newTaskM)
                {
                    $findInDbCnt += 1;
                    $oldTitle    = preg_replace('/[^\p{Han}a-zA-Z\s]/u', '', "{$newTaskM->album_dir}{$newTaskM->video_title}");
                    $this->printer->tabEcho("old title:{$oldTitle}");
                    $chekcRes  = checkAndPrintOk($newTitle, $oldTitle);
                    $chekcResR = checkAndPrintOk($oldTitle, $newTitle);

                    list($strLen, $oldLen, $similarity) = $chekcRes;
                    $res[$idStr]          = $chekcRes;
                    $title2res[$newTitle] = $chekcRes;
                    $this->printer->tabEcho(json_encode($chekcRes));
                    $this->printer->tabEcho(json_encode($chekcResR));

                    if ($strLen > 30 && $similarity > 0.75)
                    {
                        $isMatched = true;
                    }
                    else if ($strLen > 20 && $similarity > 0.9)
                    {
                        $isMatched = true;
                    }

                    list($strLenR, $oldLenR, $similarityR) = $chekcResR;
                    $similarityR100 = intval($similarityR * 100);

                    if ($strLenR > 20 && $similarityR100 > 100)
                    {
                        $isMatched = true;
                    }
                    else if ($strLenR > 25 && $similarityR100 > 90)
                    {
                        $isMatched = true;
                    }
                    else if (intval(($strLenR / $oldLenR) * 100) === $similarityR100)
                    {
                        $isMatched = true;
                    }
                }
                else
                {
                    $this->printer->tabEcho("Not found");
                }
                $this->printer->endTabEcho('check_match');
            }

            if (($idCnt === 2 && $findInDbCnt === 2) || $isMatched)
            {
                $id10     = base_convert($id36, 36, 10);
                $newTaskM = $taskM->findByPk($id10);
                if ($newTaskM)
                {
                    $this->printer->tabEcho("found in db");
                    $dstFilename = "{$dstRootDir}/{$srcLsCmdInfoObj->albumNameDir}/{$srcLsCmdInfoObj->title}.{$srcLsCmdInfoObj->ext}";
                    $dstDir      = dirname($dstFilename);
                    if (!file_exists($dstDir))
                    {
                        mkdir($dstDir, 0777, true);
                    }
                    rename($srcLsCmdInfoObj->fullFilename, $dstFilename);
                    usleep(500);
                    if (file_exists($dstFilename) && !is_file($srcLsCmdInfoObj->fullFilename))
                    {
                        $newTaskM->is_ok        = Def::staYes;
                        $newTaskM->is_src_exist = Def::staNot;
                        $newTaskM->save();
                    }

                }
                else
                {
                    $this->printer->tabEcho("not found in db");
                }
            }

            //   $this->printer->tabEcho($res);
            //  $this->printer->tabEcho($title2res);

            $this->printer->endTabEcho('check_matchs');
            $this->printer->endTabEcho('fetch_old');

        }

    }

}

