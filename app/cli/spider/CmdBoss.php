<?php


namespace cli\spider;

use hammer\cli\CmdBase;
use hammer\db\DbQuery;
use hammer\db\old\DbData;
use hammer\param\DataBox;
use hammer\sys\Sys;
use models\common\Def;
use models\ext\tool\CLIStrFormatter;
use models\ext\tool\Printer;
use modules\spider\dao\BossChat;
use modules\spider\dao\BossComp;
use modules\spider\dao\BossImgOcr;
use modules\spider\dao\BossJob;
use modules\spider\v1\api\boss_zhipin\ActionFillJobInfo;
use thiagoalessio\TesseractOCR\TesseractOCR;

use DeepSeek\DeepSeekClient;
use DeepSeek\Enums\Models;
use GuzzleHttp\Exception\GuzzleException;


ini_set('memory_limit', '2048M');


class CmdBoss extends CmdBase
{

    public static function getClassName()
    {
        return __CLASS__;
    }


    public function init()
    {
        parent::init();
    }

    public function testChrome()
    {
        // exec(" '/mnt/c/Program Files/Google/Chrome/Application/chrome.exe'   'baidu.com'");
        $list = [
            ['阜阳', 'https://www.zhipin.com/web/geek/jobs?city=101220800&jobType=1901&query=php',],
            ['北京', 'https://www.zhipin.com/web/geek/jobs?city=101010100&jobType=1901&query=php',],
            ['合肥', 'https://www.zhipin.com/web/geek/jobs?city=101220100&jobType=1901&query=php',],
            ['上海', 'https://www.zhipin.com/web/geek/jobs?city=101020100&jobType=1901&query=php',],
        ];

        foreach ($list as $ar)
        {
            echo "\n打开list页面  {$ar[0]}：{$ar[1]}\n";

            exec(" '/mnt/c/Program Files/Google/Chrome/Application/chrome.exe'   '{$ar[1]}'");
        }
    }

    public function testImg()
    {
        $ocr = new  TesseractOCR(__HAMMER_DIR__ . '/uploads/1.png');
        // （可选）如果图片包含中文，指定语言包
        // $ocr->lang('chi_sim');

        // 执行识别
        $text = $ocr->run();

        echo "识别结果：\n" . $text;

    }


    /**
     * 遍历指定目录下所有 PNG 图片，MD5 去重后 OCR 并存入数据库
     * @param string $dirPath 目录路径
     */
    private function processImagesWithOcr($dirPath)
    {
        // 确保目录以斜杠结尾
        $dirPath = rtrim($dirPath, '/') . '/';
        $pattern = $dirPath . '*.png';

        // 获取所有 PNG 文件（此处仅遍历当前层级，如需子目录请使用 RecursiveIterator）
        $files = glob($pattern);
        if (empty($files))
        {
            echo "未找到 PNG 图片。\n";
            return;
        }

        $md5toId   = [];      // 存储已处理过的 MD5
        $md5toText = [];
        $total     = count($files);
        $success   = 0;
        $skipped   = 0;

        foreach ($files as $index => $filePath)
        {
            if (0)
            {
                echo sprintf("[%d/%d] 处理文件: %s  path:%s\n", $index + 1, $total, basename($filePath), $filePath);
                $imgId = intval(pathinfo($filePath, PATHINFO_FILENAME));
                passthru("timg {$filePath}");
                $ocrText = (new TesseractOCR($filePath))->lang('chi_sim', 'eng')   // 中英文混合识别，需确保已安装语言包
                ->run();
                $ocrText = trim($ocrText);
                echo "识别结果 {$ocrText}\n";
                continue;
            }


            echo sprintf("[%d/%d] 处理文件: %s  path:%s\n", $index + 1, $total, basename($filePath), $filePath);
            $imgId = intval(pathinfo($filePath, PATHINFO_FILENAME));

            if (1)
            {
                passthru("timg -ps -g 30x40  {$filePath}");
                if (0)
                {
                    $ocrText = (new TesseractOCR($filePath))->lang('chi_sim', 'eng')   // 中英文混合识别，需确保已安装语言包
                    ->run();
                    $ocrText = trim($ocrText);
                    echo "识别结果 {$ocrText}\n";
                    continue;
                }

            }

            // 1. 计算文件 MD5
            $md5 = md5_file($filePath);

            $dao = BossImgOcr::model()->findByAttributes(['img_md5' => $md5]);
            if ($dao && strlen(strval($dao->ocr_text)) > 0)
            {
                echo " 已经计算， text:[{$dao->ocr_text}], 跳过\n";
                echo json_encode($dao->getOpenInfo(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                echo "\n";
                $md5toId[$md5]   = $dao->img_id;
                $md5toText[$md5] = $dao->ocr_text;
                continue;
            }
            if ($md5 === false)
            {
                echo "  错误：无法计算 MD5，跳过。\n";
                continue;
            }

            // 2. MD5 去重判断
            if (isset($md5toId[$md5]))
            {
                echo "  MD5 重复  {$md5}  text:[{$md5toText[$md5]}]，跳过。\n";
                $skipped++;
                continue;
            }


            // 4. 执行 OCR 识别
            try
            {
                $ocrText  = (new TesseractOCR($filePath))->lang('chi_sim', 'eng')   // 中英文混合识别，需确保已安装语言包
                ->run();
                $ocrText  = trim($ocrText);
                $isOk     = 1;  // 识别成功标记为 OK
                $errorMsg = '';
            } catch (Exception $e)
            {
                $ocrText  = '';
                $isOk     = 2;  // 识别失败标记为错误
                $errorMsg = $e->getMessage();
                echo "  警告：OCR 识别失败 - {$errorMsg}\n";
            }

            // 记录该 MD5 已处理（只要 MD5 相同，即使后续不同文件也跳过）
            $md5toId[$md5]   = $imgId;
            $md5toText[$md5] = $ocrText;

            // 5. 使用 ORM 保存识别记录
            try
            {
                // 以下为伪代码，请根据实际 ORM 类的方法调整
                if ($dao)
                {
                    $ocrRecord = $dao;
                }
                else
                {
                    $ocrRecord          = new BossImgOcr();
                    $ocrRecord->img_md5 = $md5;
                }

                $ocrRecord->img_id = $imgId;

                $ocrRecord->ocr_text   = $ocrText;
                $ocrRecord->fixed_text = '';         // 矫正结果暂时为空
                //  $ocrRecord->is_ok      = $isOk;
                $ocrRecord->save();
                echo "  成功：MD5={$md5}, img_id={$imgId}, OCR长度=" . strlen($ocrText) . "\n";
                $success++;
            } catch (Exception $e)
            {
                echo "  数据库保存失败：" . $e->getMessage() . "\n";
            }
        }

        echo "\n处理完成。总计: {$total}, 成功识别(去重后): {$success}, 重复跳过: {$skipped}\n";
    }

    public function testImgDir()
    {
        // 执行：常量 __HAMMER_DIR__ 需提前定义（例如在入口文件中）
        if (!defined('__HAMMER_DIR__'))
        {
            die('常量 __HAMMER_DIR__ 未定义');
        }

        $targetDir = __HAMMER_DIR__ . '/uploads/boss-zhipin/salary_pngs/';
        if (!is_dir($targetDir))
        {
            die("目录不存在: {$targetDir}");
        }


        $this->processImagesWithOcr($targetDir);

        BossImgOcr::model()->getConnection()->setText("update  boss_job as j left join boss_img_ocr as ocr on ocr.img_md5=j.salary_shoot_md5  and ocr.ocr_text!='' set j.salary_ocr=ocr.ocr_text where ocr.ocr_text is not null and j.salary_ocr='';")->execute();
    }


    public function formatData()
    {
        $this->formatSalary();
        $this->markNotMatch();
        $this->markRepeat();
        $this->deepseekCheckOld();
    }

    /**
     * @throws \Exception
     */
    public function markRepeat()
    {
        echo "\n";
        $jobDao = new BossJob();
        $jobTn  = $jobDao->getTableName();
        $db     = $jobDao->getConnection();

        $oldDb = new DbData();
        $oldDb->setTablename($jobTn)->connectPDO($db);
        $countRows      = $db->setText("select job_md5,count(id) as cnt from {$jobTn} where detail is not null group by job_md5 having cnt>1;
")->queryAll();
        $repeatMd5Cnt   = count($countRows);
        $repeatMd5Index = 0;
        foreach ($countRows as $row)
        {
            $repeatMd5Index++;
            $md5   = $row['job_md5'];
            $times = $row['cnt'];
            echo "\n正在处理 {$repeatMd5Index}/{$repeatMd5Cnt}  [{$md5}] repeat:{$times} times ";
            $datarows                  = $db->setText("select id,1 as is_md5_old,{$times} as job_md5_cnt from {$jobTn} where job_md5='{$md5}' order by id desc;")->queryAll();
            $datarows[0]['is_md5_old'] = 2;
            $oldDb->updateByJsonRowsOnExist($datarows, ['id'], ['is_md5_old', 'job_md5_cnt']);
            $db->setText("update {$jobTn} set job_md5_cnt={$times} where job_md5='{$md5}';")->execute();

            $row = $db->setText("select ds_res from {$jobTn} where job_md5='{$md5}'  and ds_res!=json_object()   limit 1")->queryRow();
            if ($row)
            {
                echo "\tds_res 存在，正在处理";
                $db->setText("update {$jobTn} set ds_res=:ds_res  where job_md5='{$md5}';")->bindArray([':ds_res' => $row['ds_res']])->execute();
            }
            else
            {
                echo "\tds_res 为空，跳过";
            }

        }
        echo "\n重复标识 已经标记\n";
        //  echo json_encode($countRows, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n";


    }


    /**
     * @throws \Exception
     */
    public function markNotMatch()
    {


        $dirtyWords  = array_unique(explode("\n", trim(file_get_contents(__HAMMER_DIR__ . '/_file/lib/job_dirty_kw.txt'))));
        $dogWords    = array_unique(explode("\n", trim(file_get_contents(__HAMMER_DIR__ . '/_file/lib/dog_job.txt'))));
        $dogWordsCnt = count($dogWords);
        $compDao     = new BossComp();
        $comTn       = $compDao->getTableName();
        $dogRre      = $compDao->getConnection()->setText("update {$comTn} set is_self_biz=2 where title like  :title ");
        echo "\n 外包 kw  cnt:{$dogWordsCnt}\n";
        $dogWordsIndex = 0;
        foreach ($dogWords as $dogWord)
        {
            $dogWord = trim($dogWord);
            $dogWordsIndex++;
            echo "{$dogWordsIndex}:{$dogWord}\n";
            $dogRre->bindArray([':title' => "%{$dogWord}%"])->execute();
        }
        echo "\n 外包 kw done \n";

        $jobDao     = new BossJob();
        $jobTn      = $jobDao->getTableName();
        $pre        = $jobDao->getConnection()->setText("update {$jobTn} set is_match=2 where title=:title or title like :kw2 or detail like :kw2");
        $markDogSql = " update  {$jobTn} as j left join {$comTn} as comp on j.com_id=comp.com_id set j.is_self_biz=comp.is_self_biz where comp.is_self_biz=2 and j.is_self_biz=1;";
        $compDao->getConnection()->setText($markDogSql)->execute();
        $cnt = count($dirtyWords);
        echo "\n dirty word kw cnt:{$cnt}\n";

        foreach ($dirtyWords as $i => $word)
        {
            $word = trim($word);
            echo "{$i}:$word\n";
            $pre->bindArray([':title' => $word, ':kw2' => "%{$word}%"])->execute();
        }
        echo "\n dirty word done \n";
        $sqlsKV = [
            [
                'detail' => '确定php',
                'sqls'   => [
                    "update spider.boss_job  set is_php=1
where detail is not null 
and 
(detail     like '%php%' 
or detail     like '%lanmp%' 
or  detail     like '%yaf%' 
or  detail     like '%laravel%' 
or detail  like '%wordpress%'
or detail  like '%thinkphp%'
or title     like '%php%' 
);
"
                ]
            ],
            [
                'detail' => '学历',
                'sqls'   => [
                    "update spider.boss_job set is_edu_ok=2,deny_reason=concat(deny_reason,',统招本科') where is_edu_ok=1 and  detail  like '%统招本科%';",
                    "update spider.boss_job set is_edu_ok=2,deny_reason=concat(deny_reason,',统招本科') where  is_edu_ok=1 and  detail  like '%全日制本科%';",
                    "update spider.boss_job set is_edu_ok=2,deny_reason=concat(deny_reason,',统招本科') where  is_edu_ok=1 and  detail  like '%全日制本%';",
                    //参见  日不是日   select detail from boss_job where id=11157 and  is_edu_ok=1 and detail  like '%全⽇制本科%';
                    "update spider.boss_job set is_edu_ok=2,deny_reason=concat(deny_reason,',统招本科') where  is_edu_ok=1 and  detail  like '%全_制本%';",
                    "update spider.boss_job set is_edu_ok=2,deny_reason=concat(deny_reason,',统招本科') where  is_edu_ok=1 and  detail  like '%统招%' and detail  like '%本科%';",
                    "update spider.boss_job set is_edu_ok=2,deny_reason=concat(deny_reason,',统招本科') where  is_edu_ok=1 and  detail  like '%统招%' and detail  like '%二本%';",
                    "update spider.boss_job set is_edu_ok=2,deny_reason=concat(deny_reason,',统招本科') where  is_edu_ok=1 and  detail  like '%全日制%' and detail  like '%本科%';",
                    "update spider.boss_job set is_edu_ok=2,deny_reason=concat(deny_reason,',统招本科') where  is_edu_ok=1 and  detail  like '%全日制公办本科%';",

                ]
            ],
            [
                'detail' => '外包',
                'sqls'   => [
                    "update spider.boss_job set is_self_biz=2 where  detail  like '%外包%' or title like '%外包%';",
                    "update spider.boss_job set is_self_biz=1 where  detail  like '%非外包%' or title like '%非外包%';",

                    "update spider.boss_job set is_self_biz=2 where  detail  like '%驻场%' or title like '%驻场%';",
                    "update spider.boss_job set is_self_biz=2 where  salary_text  like '%/时%' ;",
                    "update spider.boss_job set is_self_biz=2 where  salary_text  like '%小时%' ;",
                    "update spider.boss_job set is_self_biz=2 where  title like '%某知名%' ;",
                    "update spider.boss_job set is_self_biz=2 where  detail  like '%短期项目%'  or title like '%短期%';",
                    "update spider.boss_job set is_self_biz=2 where  title like '%半年%' ;",
                    "update spider.boss_job set is_self_biz=2 where  title like '%三个月%' ;",

                    "update boss_job as jt left join  boss_comp as ct on jt.com_id=ct.com_id set jt.is_out_src=1  where ct.is_out_src=1 and jt.is_out_src=2;",
                    "update boss_job as jt left join  boss_comp as ct on jt.com_id=ct.com_id set jt.is_saas=1  where ct.is_saas=1 and jt.is_saas=2;",


                ]
            ],
            [
                'detail' => '外包',
                'sqls'   => [
                    "update spider.boss_job set is_full_time=2 where  salary_text  like '%天%' ;",
                    "update spider.boss_job set is_full_time=2 where  detail  like '%兼职%' or title like '%兼职%';",
                    "update spider.boss_job set is_saas=1 where  detail  like '%saas%' or title like '%saas%';",


                ]
            ],
            [
                'detail' => '年龄',
                'sqls'   => [
                    "update boss_job as jt left join  boss_comp as ct on jt.com_id=ct.com_id set jt.is_age_ok=2  where ct.is_age_ok=2 and jt.is_age_ok=1;",

                ]
            ],

            [
                'detail' => '不匹配',
                'sqls'   => [
                    "update spider.boss_job set is_match=2 where  title like '%java%' and title not like '%php%';",
                    "update spider.boss_job set is_match=2 where  title like '%python%' and title not like '%php%';",
                    "update spider.boss_job set is_match=2 where  title like '%go%' and title not like '%php%';",
                    "update spider.boss_job set is_match=2 where  title like '%ndroid%' and title not like '%php%';",
                    "update spider.boss_job set is_match=2 where  title like '%客户端%' and title not like '%php%';",
                    "update spider.boss_job set is_match=2 where  title like '%ios%' and title not like '%php%';",
                    "update spider.boss_job set is_match=2 where  title like '%保洁%' and title not like '%php%';",
                    "update spider.boss_job set is_match=2 where  title like '%native%' ;",
                    "update spider.boss_job set is_match=2 where  title like '%安全%' ;",
                    "update spider.boss_job set is_match=2 where  title like '%测试%' ;",
                    "update spider.boss_job set is_match=2 where  title like '%运维%' ;",
                    "update spider.boss_job set is_match=2 where  title like '%seo%' ;",
                    "update spider.boss_job set is_match=2 where  title like '%实习%' ;",
                    "update spider.boss_job set is_match=2 where  title like '%c++%' ;",
                    "update spider.boss_job set is_match=2 where  title like '%渗透%' ;",



                ]
            ],
            [
                'detail' => '薪资',
                'sqls'   => [
                    "update spider.boss_job set is_salary_ok=1 where  area_title like '_上海%' and (salary_gte>=10 or salary_lte>12);",
                    "update spider.boss_job set is_salary_ok=1 where  area_title like '_北京%' and (salary_gte>=10 or salary_lte>12);",
                    "update spider.boss_job set is_salary_ok=1 where  area_title like '_广州%' and (salary_gte>=13 or salary_lte>12);",
                    "update spider.boss_job set is_salary_ok=1 where  area_title like '_深圳%' and (salary_gte>=14 or salary_lte>12);",
                    "update spider.boss_job set is_salary_ok=1 where  area_title like '_苏州%' and (salary_gte>=12 or salary_lte>=12);",
                    "update spider.boss_job set is_salary_ok=1 where  area_title like '_常州%' and (salary_gte>=12 or salary_lte>=12);",
                    "update spider.boss_job set is_salary_ok=1 where  area_title like '_无锡%' and (salary_gte>=12 or salary_lte>=12);",
                    "update spider.boss_job set is_salary_ok=1 where  area_title like '_南京%' and (salary_gte>=12 or salary_lte>=12);",
                    "update spider.boss_job set is_salary_ok=1 where  area_title like '_合肥%' and (salary_gte>=12 or salary_lte>=12);",
                    "update spider.boss_job set is_salary_ok=1 where  area_title like '_阜阳%' and (salary_gte>=7  or salary_lte>=8);",


                ]
            ],
            [
                'detail' => '活跃时间',
                'sqls'   => [
                    "update spider.boss_job set is_hr_live=2 where  hr_active_time in ('2周内活跃','本月活跃','2月内活跃', '3月内活跃', '4月内活跃', '5月内活跃', '近半年活跃', '半年前活跃');"
                ]
            ]

        ];
        foreach ($sqlsKV as $cfg)
        {
            echo "{$cfg['detail']}\n";
            foreach ($cfg['sqls'] as $sql)
            {
                echo "{$sql}\n";
                $jobDao->getConnection()->setText($sql)->execute();
            }
        }
    }


    public function formatSalary()
    {
        $sql    = "select id,salary_text from boss_job       where salary_gte=0 and  salary_text like '%-%K';";
        $jobDao = new BossJob();
        $rows   = $jobDao->getConnection()->setText($sql)->queryAll();
        $cnt    = count($rows);
        $i      = 0;
        foreach ($rows as $row)
        {
            $i++;
            echo "{$row['id']}:{$row['salary_text']}  {$i}/{$cnt}\n";
            $salaryText = $row['salary_text'];
            list($gteNum, $lteNum) = explode('-', str_replace('K', '', $salaryText));
            if (is_numeric($lteNum) && is_numeric($gteNum))
            {
                $sql = "update boss_job set salary_gte={$gteNum},salary_lte={$lteNum} where id={$row['id']};";
                $jobDao->getConnection()->setText($sql)->execute();
            }

        }

        $sql    = "select id,salary_text from boss_job       where salary_gte=0 and  salary_text like '%-%K%薪';";
        $jobDao = new BossJob();
        $rows   = $jobDao->getConnection()->setText($sql)->queryAll();
        $cnt    = count($rows);
        $i      = 0;
        foreach ($rows as $row)
        {
            $i++;
            echo "{$row['id']}:{$row['salary_text']}  {$i}/{$cnt}\n";
            $salaryText = $row['salary_text'];
            $matches    = [];
            $res        = preg_match('/(\d+)\s*-\s*(\d+)K\s*·\s*(\d+)薪/', $salaryText, $matches);

            if ($res && count($matches) === 4)
            {
                list($str, $gteNum, $lteNum, $salaryPaychecks) = $matches;
                if (is_numeric($lteNum) && is_numeric($gteNum) && is_numeric($salaryPaychecks))
                {
                    $sql = "update boss_job set salary_gte={$gteNum},salary_lte={$lteNum},salary_paychecks={$salaryPaychecks} where id={$row['id']};";
                    $jobDao->getConnection()->setText($sql)->execute();
                }
            }
            else
            {
                var_dump($res, $matches);
            }


        }


    }


    public function deepseekCheckOld()
    {

        $modByN = $this->inputBox->tryGetInt('mod_by', true);
        $modEqN = $this->inputBox->tryGetInt('mod_eq', true);

        $printer = new Printer();
        $jobDao  = new BossJob();
        $jobTn   = $jobDao->getTableName();

        $apiKey = __DEEPSEEK_API_KEY__;


        // 每次处理的批量大小，可根据API速率限制调整[reference:9]
        $batchSize = 100;
        $queryM    = DbQuery::m($jobDao)->setSelects(['id', 'title', 'detail']);
        $count_sql = '';
        if (is_null($modByN) || is_null($modEqN))
        {
            $queryM->setWheres(['is_ok' => Def::staYes, '`ds_res`=json_object()', 'detail is not null', 'job_md5_cnt<2']);
            $count_sql = "select count(id) as cnt from {$jobTn} where is_ok=1 and ds_res=json_object() and detail is not null and job_md5_cnt<2";;

        }
        else
        {
            $queryM->setWheres(['is_ok' => Def::staYes, '`ds_res`=json_object()', 'detail is not null', 'job_md5_cnt<2', "id%{$modByN}={$modEqN}"]);
            $count_sql = "select count(id) as cnt from {$jobTn} where is_ok=1 and ds_res=json_object() and detail is not null and job_md5_cnt<2 and id%{$modByN}={$modEqN}";

        }


        $updateDsResCmd = $jobDao->getConnection()->setText("update {$jobTn} set ds_res=:json where id=:id");

        $promptCommon = "请对以下工作岗位内容进行判断，并以JSON格式返回结果，以下内容和php相关，追加 is_php=1,要求java比较熟练，追加 is_java=1,和爬虫相关追加is_spider=1,要求全日制本科的is_edu=1,要求前端精通熟练的 is_fee=1,属于广义上互联网开发岗位的is_match=1\n内容:
        ";
        $promptCommon = '你是一个岗位匹配分析助手。请根据给定的「岗位描述」，按以下规则输出一个 JSON 对象。

【你的能力基线】（用于最终适合度判断）
- 编程语言：PHP资深，12年经验，JavaScript/HTML/CSS（熟练） 实际项目经验丰富，Python/Java/Go/Node.js/微信小程序/vue/react/android等可写 demo 级代码，但是没有实际项目经验。
- 行业：互联网 8年，4年游戏，在线视频、视频聚合，互联网电视 8年，2年短视频内容分发，2年建站。
- 项目：管理后台（媒资数据、视频聚合、游戏运营）6年，内容定向爬虫 4年，支付 6年，电视支付平台 5年，建站20多个。
- 学历：自考本科（非全日制）,统招大专。
- 其他：无大型分布式系统经验，但能独立完成中小型项目，120万用户、20万日活的用户量。

【判断规则】
1. 是否为广义互联网开发岗位？
   定义：包括但不限于 后端开发、前端开发、全栈开发、爬虫工程师、测试开发（SDET）、软件项目经理（需有技术背景）、运维开发（DevOps）等。
   如果岗位属于上述范畴 → is_dev = 1，否则 → is_dev = 2。

2. 技能与学历要求匹配（每项单独判断）：
   - is_php：岗位描述中明确要求 PHP 开发经验（或精通 PHP） → 1，否则 2。
   - is_adv_java：岗位描述中明确要求 Java 精通/熟练（非“了解即可”） → 1，否则 2。
   - is_adv_go: 岗位描述中明确要求 Go 精通/熟练（非“了解即可”） → 1，否则 2。
   - is_spider：岗位描述中出现“爬虫/数据采集/反爬/Scrapy”等关键词 → 1，否则 2。
   - is_frontend（原 is_fee）：岗位描述中要求前端技术（如 Vue/React/JS/CSS）且要求“精通/熟练” → 1，否则 2。
   - is_edu_ok：岗位要求“全日制本科及以上”学历 → 1，否则 2（注意：只要出现“全日制”字样即视为1，普通本科或不限视为2）。
   - is_pm：岗位要求项目经理或者管理团队 → 1，否则 2。
   - is_ops：岗位描述中要求运维开发（DevOps） → 1，否则 2。
   - is_big_data: 岗位描述中要求大数据开发 → 1，否则 2。
   - is_ios: 岗位描述中要求 iOS 开发 → 1，否则 2(注意：是指明ios开发)。
   - is_android: 岗位描述中要求 Android 开发 → 1，否则 2(注意：是指明android开发)。
   - is_new_frontend: 岗位描述中要求 Vue/react 开发 → 1，否则 2。
   - is_fullstack：岗位描述中要求全栈开发 → 1，否则 2。
   - is_more_than_one_job: 岗位描述中是多个岗位（如“前端开发、后端开发”等） → 1，否则 2。

3. 综合适合度判断（is_deepseek_deny）：
   基于你的能力基线，判断这个岗位是否适合你。
   - 若适合（即你的核心技能匹配岗位核心需求，且学历等硬性条件未造成绝对障碍）→ 值为 2。
   - 若不适合（如岗位要求 Java 高级开发，而你仅 demo 水平；或硬性要求全日制本科）→ 值为 1。

【输出格式】
严格按照以下 JSON 结构，不要添加任何额外文字：
{
  "is_dev": 1或2,
  "is_php": 1或2,
  "is_adv_java": 1或2,
  "is_adv_go": 1或2,
  "is_spider": 1或2,
  "is_frontend": 1或2,
  "is_edu_ok": 1或2,
  "is_pm": 1或2,
  "is_ops": 1或2,
  "is_big_data": 1或2,
  "is_ios": 1或2,
  "is_android": 1或2,
  "is_new_frontend": 1或2,
  "is_fullstack": 1或2,
  "is_more_than_one_job": 1或2,
  "is_deepseek_deny": 1或2,
  "reason": "简要说明为什么适合/不适合（20字以内）"
}

【岗位描述】
';
        $printer->newTabEcho('all_tasks', "正在处理任务...");
        $batchI = 0;
        $allI   = 0;
        $allCnt = $jobDao->getConnection()->setText($count_sql)->queryScalar();
        while (true)
        {

            $batchI++;
            $tasks = $queryM->setLimit(1, $batchSize)->queryRows();
            //die;
            if (empty($tasks))
            {
                echo "没有待处理的任务。\n";
                $printer->tabEcho(CLIStrFormatter::success("没有待处理的任务."));
                Sys::app()->setDebug(true);
                var_dump($tasks = $queryM->setLimit(1, 1)->queryPageData());
                break;
                // exit(0);
            }

            $batchCnt = count($tasks);
            $printer->newTabEcho('batch_tasks', CLIStrFormatter::info("正在处理第 {$batchI} 批次 数量:{$batchCnt}..."));
            foreach ($tasks as $task)
            {
                $allI++;
                $id      = $task['id'];
                $detail  = strip_tags($task['detail']);
                $title   = $task['title'];
                $content = "职位：{$title}\n岗位描述:{$detail}";
                // $printer->tabEcho("正在处理任务: {$allI}/{$batchI}*{$batchSize}  {$batchCnt}   id:[{$id}]\n{$content}\n");
                $printer->tabEcho("正在处理任务: ALL i/cnt:{$allI}/{$allCnt}  BATCH: cnt in  i*size {$batchCnt} in  {$batchI}*{$batchSize}     id:[{$id}]\n{$content}\n");

                try
                {
                    $client = DeepSeekClient::build($apiKey);

                    // 调用DeepSeek API进行分析
                    // 根据你的判断需求构造Prompt[reference:10]
                    $prompt = "{$promptCommon}\n{$content}";
                    // echo "\n$content\n";

                    $response = $client->withModel('deepseek-v4-flash')   // 直接写字符串 // 选择模型，V4_FLASH速度快且经济[reference:11]
                    ->setTemperature(0.3) // 较低的温度使输出更确定
                    ->setResponseFormat('json_object') // 要求返回JSON格式[reference:12]
                    ->query($prompt)->run();

                    // 解析响应并更新数据库
                    $result = json_decode($response, true);
                    //  echo "{$response}";
                    if (json_last_error() === JSON_ERROR_NONE)
                    {
                        if (isset($result['choices'][0]['message']['content']) && isset($result['choices'][0]['message']['reasoning_content']))
                        {
                            $resJson = json_decode($result['choices'][0]['message']['content'], true);
                            if (json_last_error() === JSON_ERROR_NONE)
                            {
                                $resJson['reason'] = $result['choices'][0]['message']['reasoning_content'];
                                $updateDsResCmd->bindArray([':json' => json_encode($resJson), ':id' => $id])->execute();

                                $printer->tabEcho(CLIStrFormatter::success(json_encode($resJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));

                            }
                            else
                            {
                                $updateDsResCmd->bindArray([':json' => json_encode(['isError' => 1, 'error' => 'decode_msg', 'res' => $result]), ':id' => $id])->execute();
                                $printer->tabEcho(CLIStrFormatter::error("任务 ID: $id  解码失败。\n{$result['choices'][0]['message']['content']}\n"));

                            }
                        }
                        else
                        {
                            $updateDsResCmd->bindArray([':json' => json_encode(['isError' => 1, 'error' => 'lost_msg', 'res' => $result]), ':id' => $id])->execute();
                            $printer->tabEcho(CLIStrFormatter::error("任务 ID: $id  (丢失信息)。\n" + json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));


                        }
                    }
                    else
                    {
                        $updateDsResCmd->bindArray([':json' => json_encode(['isError' => 1, 'error' => 'deocde_response', 'res' => $result]), ':id' => $id])->execute();

                        // 如果返回的不是有效JSON，将原始响应存入result
                        $printer->tabEcho(CLIStrFormatter::error("任务 ID: $id  (非JSON格式)。\n{$response}\n"));
                    }

                } catch (Exception $e)
                {
                    // 错误处理：记录错误信息，状态设为 'failed'
                    $errorMsg = $e->getMessage();
                    echo "任务 ID: $id 处理失败: $errorMsg\n";
                }

                // 在循环间稍作延迟，避免触发API速率限制[reference:13]
                usleep(500000); // 延迟0.5秒

            }

            echo "批次处理完成。\n";
        }
        $printer->endTabEcho('all_tasks', "处理任务 all OK");


    }

    public function deepseekCheck()
    {

        $modByN = $this->inputBox->tryGetInt('mod_by', true);
        $modEqN = $this->inputBox->tryGetInt('mod_eq', true);

        $printer = new Printer();
        $jobDao  = new BossJob();
        $jobTn   = $jobDao->getTableName();

        $apiKey = __DEEPSEEK_API_KEY__;


        // 每次处理的批量大小，可根据API速率限制调整[reference:9]
        $batchSize  = 1000;
        $queryM     = DbQuery::m($jobDao)->setSelects(['id', 'title', 'detail']);
        $conditions = ['is_ok' => Def::staYes, '`ds_res`=json_object()'];
        $conditions = ['is_ok' => Def::staYes, 'is_ds_deny' => 1, 'id' => 0];

        $sql = "select id,title,detail from {$jobTn} where is_ok=1 and is_ds_deny=1 and id>:max_pk and id%{$modByN}={$modEqN} order by id asc limit {$batchSize}";
        $sql = "select id,title,detail from {$jobTn} where is_ok=1 and is_ds_deny=1 and id>:max_pk and ds_res=json_object() and id%{$modByN}={$modEqN} order by id asc limit {$batchSize}";
        $sql = "select id,title,detail from {$jobTn} where is_ok=1 and id>:max_pk and ds_res=json_object() and id%{$modByN}={$modEqN} order by id asc limit {$batchSize}";
        $sql = "";

        $count_sql = '';
        if (!(is_null($modByN) || is_null($modEqN)))
        {
            $conditions[] = "id%{$modByN}={$modEqN}";

            $sql       = "select id,title,detail from {$jobTn} where is_ok=1 and id>:max_pk and ds_res=json_object() and detail is not null and job_md5_cnt<2 and id%{$modByN}={$modEqN} order by id asc limit {$batchSize}";
            $count_sql = "select count(id) as cnt from {$jobTn} where is_ok=1 and ds_res=json_object() and detail is not null and job_md5_cnt<2 and id%{$modByN}={$modEqN}";
        }
        else
        {
            $sql       = "select id,title,detail from {$jobTn} where is_ok=1 and id>:max_pk and ds_res=json_object() and detail is not null and job_md5_cnt<2 order by id asc limit {$batchSize}";
            $count_sql = "select count(id) as cnt from {$jobTn} where is_ok=1 and ds_res=json_object() and detail is not null and job_md5_cnt<2";;
        }
        $queryM->setWheres($conditions);

        $printer->newTabEcho('all_tasks', "正在处理任务...");
        $batchI = 0;
        $allI   = 0;
        $maxPK  = 0;
        $allCnt = $jobDao->getConnection()->setText($count_sql)->queryScalar();
        while (true)
        {

            $batchI++;
            $tasks = $jobDao->getConnection()->setText($sql)->bindArray([':max_pk' => $maxPK])->queryAll();
            //die;
            if (empty($tasks))
            {
                echo "没有待处理的任务。\n";
                $printer->tabEcho(CLIStrFormatter::success("没有待处理的任务."));
                Sys::app()->setDebug(true);
                var_dump($queryM->setLimit(1, 1)->queryPageData());
                break;
                // exit(0);
            }

            $batchCnt = count($tasks);
            $printer->newTabEcho('batch_tasks', CLIStrFormatter::info("正在处理第 {$batchI} 批次 数量:{$batchCnt}..."));
            foreach ($tasks as $task)
            {
                $allI++;
                $id      = $task['id'];
                $maxPK   = $id;
                $detail  = strip_tags($task['detail']);
                $title   = $task['title'];
                $content = "职位：{$title}\n岗位描述:{$detail}";
                $printer->tabEcho("正在处理任务: ALL i/cnt:{$allI}/{$allCnt}  BATCH: cnt in  i*size {$batchCnt} in  {$batchI}*{$batchSize}     id:[{$id}]\n{$content}\n");

                passthru("cd /mnt/f/doc/show/; ./hammer spider/boss deepseekCheckByPk --job_pk={$id} --env=kl-pc");
                // 在循环间稍作延迟，避免触发API速率限制[reference:13]
                //  usleep(500000); // 延迟0.5秒

            }

            echo "批次处理完成。\n";
        }
        $printer->endTabEcho('all_tasks', "处理任务 all OK");


    }

    public function deepseekCheckByPk()
    {
        $id      = $this->inputBox->getNotEmptyInt('job_pk');
        $printer = new Printer();
        $printer->setCallerDeep(4);
        $jobDao = new BossJob();
        $jobTn  = $jobDao->getTableName();

        $apiKey = __DEEPSEEK_API_KEY__;

        // 每次处理的批量大小，可根据API速率限制调整[reference:9]
        $queryM = DbQuery::m($jobDao)->setSelects(['id', 'title', 'detail'])->setWheres(['id' => $id]);

        $updateDsResCmd = $jobDao->getConnection()->setText("update {$jobTn} set ds_res=:json where id=:id");

        $promptCommon = "请对以下工作岗位内容进行判断，并以JSON格式返回结果，以下内容和php相关，追加 is_php=1,要求java比较熟练，追加 is_java=1,和爬虫相关追加is_spider=1,要求全日制本科的is_edu=1,要求前端精通熟练的 is_fee=1,属于广义上互联网开发岗位的is_match=1\n内容:
        ";
        $promptCommon = '你是一个岗位匹配分析助手。请根据给定的「岗位描述」，按以下规则输出一个 JSON 对象。

【你的能力基线】（用于最终适合度判断）
- 编程语言：PHP（资深，4年爬虫经验）、JavaScript/HTML/CSS（熟练） 实际项目经验丰富，Python/Java/Go/Node.js/微信小程序/vue/react/android等可写 demo 级代码、做过简单维护，但是没有实际的项目经验。
- 学历：自考本科（非全日制）,统招大专。
- 其他：无大型分布式系统经验，但能独立完成中小型项目，120万用户、20万日活的用户量。

【判断规则】
1. 是否为广义互联网开发岗位？
   定义：包括但不限于 后端开发、前端开发、全栈开发、爬虫工程师、测试开发（SDET）、软件项目经理（需有技术背景）、运维开发（DevOps）等。
   如果岗位属于上述范畴 → is_dev = 1，否则 → is_dev = 2。

2. 技能与学历要求匹配（每项单独判断）：
   - is_php：岗位描述中明确要求 PHP 开发经验（或精通 PHP） → 1，否则 2。
   - is_adv_java：岗位描述中明确要求 Java 精通/熟练（非“了解即可”） → 1，否则 2。
   - is_adv_go: 岗位描述中明确要求 Go 精通/熟练（非“了解即可”） → 1，否则 2。
   - is_spider：岗位描述中出现“爬虫/数据采集/反爬/Scrapy”等关键词 → 1，否则 2。
   - is_frontend（原 is_fee）：岗位描述中要求前端技术（如 Vue/React/JS/CSS）且要求“精通/熟练” → 1，否则 2。
   - is_edu_ok：岗位要求“全日制本科及以上”学历 → 1，否则 2（注意：只要出现“全日制”字样即视为1，普通本科或不限视为2）。
   - is_pm：岗位要求项目经理或者管理团队 → 1，否则 2。
   - is_ops：岗位描述中要求运维开发（DevOps） → 1，否则 2。
   - is_big_data: 岗位描述中要求大数据开发 → 1，否则 2。
   - is_ios: 岗位描述中要求 iOS 开发 → 1，否则 2(注意：是指明ios开发)。
   - is_android: 岗位描述中要求 Android 开发 → 1，否则 2(注意：是指明android开发)。
   - is_new_frontend: 岗位描述中要求 Vue/react 开发 → 1，否则 2。
   - is_fullstack：岗位描述中要求全栈开发 → 1，否则 2。
   - is_more_than_one_job: 岗位描述中是多个岗位（如“前端开发、后端开发”等） → 1，否则 2。

3. 综合适合度判断（is_deepseek_deny）：
   基于你的能力基线，判断这个岗位是否适合你。
   - 若适合（即你的核心技能匹配岗位核心需求，且学历等硬性条件未造成绝对障碍）→ 值为 2。
   - 若不适合（如岗位要求 Java 高级开发，而你仅 demo 水平；或硬性要求全日制本科）→ 值为 1。

【输出格式】
严格按照以下 JSON 结构，不要添加任何额外文字：
{
  "is_dev": 1或2,
  "is_php": 1或2,
  "is_adv_java": 1或2,
  "is_adv_go": 1或2,
  "is_spider": 1或2,
  "is_frontend": 1或2,
  "is_edu_ok": 1或2,
  "is_pm": 1或2,
  "is_ops": 1或2,
  "is_big_data": 1或2,
  "is_ios": 1或2,
  "is_android": 1或2,
  "is_new_frontend": 1或2,
  "is_fullstack": 1或2,
  "is_more_than_one_job": 1或2,
  "is_deepseek_deny": 1或2,
  "reason": "简要说明为什么适合/不适合（20字以内）"
}

【岗位描述】
';

        $tasks = $queryM->setLimit(1, 1)->queryRows();
        //die;
        if (empty($tasks))
        {
            echo "没有待处理的任务。\n";
            $printer->tabEcho(CLIStrFormatter::success("没有待处理的任务."));
            Sys::app()->setDebug(true);
            var_dump($tasks = $queryM->setLimit(1, 1)->queryPageData());
            return false;
            // exit(0);
        }

        $task    = $tasks[0];
        $id      = $task['id'];
        $detail  = strip_tags($task['detail']);
        $title   = $task['title'];
        $content = "职位：{$title}\n岗位描述:{$detail}";
        $printer->tabEcho("正在处理任务:   id:[{$id}]\n{$content}\n");

        try
        {
            $client = DeepSeekClient::build($apiKey);

            // 调用DeepSeek API进行分析
            // 根据你的判断需求构造Prompt[reference:10]
            $prompt = "{$promptCommon}\n{$content}";
            // echo "\n$content\n";

            $response = $client//
                               //->withModel('deepseek-v4-flash')   // 直接写字符串 // 选择模型，V4_FLASH速度快且经济[reference:11]
            ->withModel('deepseek-chat')   //
            ->setTemperature(0.3) // 较低的温度使输出更确定
            ->setMaxTokens(250)//限制token
            ->setResponseFormat('json_object') // 要求返回JSON格式[reference:12]
            ->query($prompt)->run();

            // 解析响应并更新数据库
            $result = json_decode($response, true);
            //  echo "{$response}";
            if (json_last_error() === JSON_ERROR_NONE)
            {
                if (isset($result['choices'][0]['message']))
                {
                    if (isset($result['choices'][0]['message']['content']) && isset($result['choices'][0]['message']['reasoning_content']))
                    {
                        $resJson = json_decode($result['choices'][0]['message']['content'], true);
                        if (json_last_error() === JSON_ERROR_NONE)
                        {
                            $resJson['reason'] = $result['choices'][0]['message']['reasoning_content'];
                            $updateDsResCmd->bindArray([':json' => json_encode($resJson), ':id' => $id])->execute();

                            $printer->tabEcho(CLIStrFormatter::success(json_encode($resJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));

                        }
                        else
                        {
                            $updateDsResCmd->bindArray([':json' => json_encode(['isError' => 1, 'error' => 'decode_msg', 'res' => $result]), ':id' => $id])->execute();
                            $printer->tabEcho(CLIStrFormatter::error("任务 ID: $id  解码失败。\n{$response}\n"));

                        }

                    }
                    else if (is_string($result['choices'][0]['message']['content']))
                    {
                        $resJson = json_decode($result['choices'][0]['message']['content'], true);
                        if (json_last_error() === JSON_ERROR_NONE)
                        {
                            $resJson['reason'] = '';
                            $updateDsResCmd->bindArray([':json' => json_encode($resJson), ':id' => $id])->execute();

                            $printer->tabEcho(CLIStrFormatter::success(json_encode($resJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));

                        }
                        else
                        {
                            $updateDsResCmd->bindArray([':json' => json_encode(['isError' => 1, 'error' => 'decode_msg', 'res' => $result]), ':id' => $id])->execute();
                            $printer->tabEcho(CLIStrFormatter::error("任务 ID: $id  解码失败 ['choices'][0]['message']['content'] 。\n{$result['choices'][0]['message']['content']}\n"));

                        }
                    }
                    else
                    {
                        $printer->tabEcho(CLIStrFormatter::error("任务 ID: $id  解码失败,结构未知 ['choices'][0]['message']['content] 。\n{$result['choices'][0]['message']['content']}\n"));

                    }
                }
                else
                {
                    $updateDsResCmd->bindArray([':json' => json_encode(['isError' => 1, 'error' => 'lost_msg', 'res' => $response]), ':id' => $id])->execute();
                    $printer->tabEcho(CLIStrFormatter::error("任务 ID: $id  (丢失信息)。\n$response\n"));

                }
            }
            else
            {
                $updateDsResCmd->bindArray([':json' => json_encode(['isError' => 1, 'error' => 'deocde_response', 'res' => $response]), ':id' => $id])->execute();

                // 如果返回的不是有效JSON，将原始响应存入result
                $printer->tabEcho(CLIStrFormatter::error("任务 ID: $id  (非JSON格式)。\n{$response}\n"));
            }

        } catch (Exception $e)
        {
            // 错误处理：记录错误信息，状态设为 'failed'
            $errorMsg = $e->getMessage();
            echo "任务 ID: $id 处理失败: $errorMsg\n";

            $printer->tabEcho(CLIStrFormatter::error("任务 ID: $id  (非JSON格式)。\n{$response}\n"));
        }

    }


    public function fillJobInfoByLog()
    {
        $file   = '/mnt/f/doc/show/runtimes/log/1.log';
        $ar     = fopen($file, 'r');
        $action = new ActionFillJobInfo();
        $i      = 0;
        while (!feof($ar))
        {
            $line = fgets($ar);
            $line = substr($line, 21);
            echo "{$i}:{$line}\n";
            $inputParam = json_decode($line, true);
            if (count($inputParam) > 4)
            {
                $inputParam['isDel'] = false;
            }
            //   var_export($inputParam);
            $action->setInputBox(new DataBox($inputParam));
            $res     = $action->run();
            $jsonRes = json_encode($res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            //   echo "{$jsonRes}\n";
            // var_export($res);
            $i++;

            // break;
        }
        fclose($ar);
        echo "\n";
    }

    public function testArray()
    {
        $m   = BossChat::model()->findByPk('96');
        $arr = $m->last_chat_content['msgs'];
        var_dump($m->last_chat_content);
        var_dump($arr);
        var_dump(array_unique($arr));
    }

    public function testLocalDeepseek()
    {
        $apiKey = 'sk-local-no-key-required';
        $baseUrl = 'http://127.0.0.1:8080/v1';

        $client = DeepSeekClient::build(
            apiKey: $apiKey,
            baseUrl: $baseUrl,      // 关键：指向本地服务
            timeout: 300,            // 本地模型生成可能较慢，超时建议调大
            clientType: 'guzzle'     // 或用 'symfony'，看你项目安装了什么
        );

        $prompt = "你是一个AI助手，告诉我你的名字";

        $promptCommon = '你是一个岗位匹配分析助手。请根据给定的「岗位描述」，按以下规则输出一个 JSON 对象。

【你的能力基线】（用于最终适合度判断）
- 编程语言：PHP资深，12年经验，JavaScript/HTML/CSS（熟练） 实际项目经验丰富，Python/Java/Go/Node.js/微信小程序/vue/react/android等可写 demo 级代码，但是没有实际项目经验。
- 行业：互联网 8年，4年游戏，在线视频、视频聚合，互联网电视 8年，2年短视频内容分发，2年建站。
- 项目：管理后台（媒资数据、视频聚合、游戏运营）6年，内容定向爬虫 4年，支付 6年，电视支付平台 5年，建站20多个。
- 学历：自考本科（非全日制）,统招大专。
- 其他：无大型分布式系统经验，但能独立完成中小型项目，120万用户、20万日活的用户量。

【判断规则】
1. 是否为广义互联网开发岗位？
   定义：包括但不限于 后端开发、前端开发、全栈开发、爬虫工程师、测试开发（SDET）、软件项目经理（需有技术背景）、运维开发（DevOps）等。
   如果岗位属于上述范畴 → is_dev = 1，否则 → is_dev = 2。

2. 技能与学历要求匹配（每项单独判断）：
   - is_php：岗位描述中明确要求 PHP 开发经验（或精通 PHP） → 1，否则 2。
   - is_adv_java：岗位描述中明确要求 Java 精通/熟练（非“了解即可”） → 1，否则 2。
   - is_adv_go: 岗位描述中明确要求 Go 精通/熟练（非“了解即可”） → 1，否则 2。
   - is_spider：岗位描述中出现“爬虫/数据采集/反爬/Scrapy”等关键词 → 1，否则 2。
   - is_frontend（原 is_fee）：岗位描述中要求前端技术（如 Vue/React/JS/CSS）且要求“精通/熟练” → 1，否则 2。
   - is_edu_ok：岗位要求“全日制本科及以上”学历 → 1，否则 2（注意：只要出现“全日制”字样即视为1，普通本科或不限视为2）。
   - is_pm：岗位要求项目经理或者管理团队 → 1，否则 2。
   - is_ops：岗位描述中要求运维开发（DevOps） → 1，否则 2。
   - is_big_data: 岗位描述中要求大数据开发 → 1，否则 2。
   - is_ios: 岗位描述中要求 iOS 开发 → 1，否则 2(注意：是指明ios开发)。
   - is_android: 岗位描述中要求 Android 开发 → 1，否则 2(注意：是指明android开发)。
   - is_new_frontend: 岗位描述中要求 Vue/react 开发 → 1，否则 2。
   - is_fullstack：岗位描述中要求全栈开发 → 1，否则 2。
   - is_more_than_one_job: 岗位描述中是多个岗位（如“前端开发、后端开发”等） → 1，否则 2。

3. 综合适合度判断（is_deepseek_deny）：
   基于你的能力基线，判断这个岗位是否适合你。
   - 若适合（即你的核心技能匹配岗位核心需求，且学历等硬性条件未造成绝对障碍）→ 值为 2。
   - 若不适合（如岗位要求 Java 高级开发，而你仅 demo 水平；或硬性要求全日制本科）→ 值为 1。

【输出格式】
严格按照以下 JSON 结构，不要添加任何额外文字：
{
  "is_dev": 1或2,
  "is_php": 1或2,
  "is_adv_java": 1或2,
  "is_adv_go": 1或2,
  "is_spider": 1或2,
  "is_frontend": 1或2,
  "is_edu_ok": 1或2,
  "is_pm": 1或2,
  "is_ops": 1或2,
  "is_big_data": 1或2,
  "is_ios": 1或2,
  "is_android": 1或2,
  "is_new_frontend": 1或2,
  "is_fullstack": 1或2,
  "is_more_than_one_job": 1或2,
  "is_deepseek_deny": 1或2,
  "reason": "简要说明为什么适合/不适合（20字以内）"
}

【岗位描述】
';
        $promptCommon2='你是岗位匹配分析助手。根据岗位描述输出JSON，不要思考过程，不要markdown。

【我的能力】
PHP资深12年；JS/CSS熟练；Python/Java/Go/Node/小程序/Vue/React/Android仅demo无实际项目；互联网/游戏/在线视频行业；管理后台、爬虫、支付、建站经验；自考本科(非全日制)+统招大专；无大型分布式，做过120万用户/20万日活项目。

【判断规则】值1=是，2=否
- is_dev: 广义互联网开发岗(后端/前端/全栈/爬虫/测试开发/技术型PM/DevOps)=1
- is_php: 岗位明确要求PHP
- is_adv_java: 要求Java精通或熟练
- is_adv_go: 要求Go精通或熟练
- is_spider: 出现爬虫/采集/反爬/Scrapy
- is_frontend: 要求前端且精通或熟练
- is_edu_ok: 出现"全日制本科"(含"全日制本科及以上")
- is_pm: 要求项目经理或带团队
- is_ops: 要求运维开发/DevOps
- is_big_data: 要求大数据开发
- is_ios: 要求iOS开发
- is_android: 要求Android开发
- is_new_frontend: 要求Vue或React
- is_fullstack: 要求全栈
- is_more_than_one_job: 混合多个不同岗位
- is_deepseek_deny: 不适合我=1，适合=2。硬伤包括：Java/Go高级、全日制本科、大数据、iOS/Android原生、算法岗、大型分布式架构师。匹配项：PHP、爬虫、管理后台、Vue/React前端、PHP全栈。

【示例】
岗位:"Java高级工程师,全日制本科,精通Java和Spring"
输出:{"is_dev":1,"is_php":2,"is_adv_java":1,"is_adv_go":2,"is_spider":2,"is_frontend":2,"is_edu_ok":1,"is_pm":2,"is_ops":2,"is_big_data":2,"is_ios":2,"is_android":2,"is_new_frontend":2,"is_fullstack":2,"is_more_than_one_job":2,"is_deepseek_deny":1,"reason":"Java高级且全日制本科"}

【输出】只输出JSON，17个字段全部必填，reason不超过20字：
{"is_dev":?,"is_php":?,"is_adv_java":?,"is_adv_go":?,"is_spider":?,"is_frontend":?,"is_edu_ok":?,"is_pm":?,"is_ops":?,"is_big_data":?,"is_ios":?,"is_android":?,"is_new_frontend":?,"is_fullstack":?,"is_more_than_one_job":?,"is_deepseek_deny":?,"reason":"..."}

【岗位描述】';
        $content='分布式经验/Docker/PostgreSQL/MySQL/运维开发经验/PHP/云计算经验/可兼职/Linux开发/部署经验
岗位职责：
1、负责软件产品的设计、编码及优化工作，确保产品的稳定性和高效性；
2、参与软件项目的需求分析，提供技术解决方案，推动项目顺利进行；
3、与团队紧密合作，共同解决开发过程中的技术难题，提升产品质量；
4、跟踪分析新技术的发展，提出创新方案，持续优化产品功能；
5、维护现有系统的正常运行，定期进行系统升级和性能调优。
任职要求：
1、精通PHP主流编程语言，具备良好的编码习惯；
2、熟悉软件开发生命周期，了解敏捷开发流程，有较强的需求分析能力；
3、具备良好的逻辑思维能力和问题解决能力，能够独立承担模块开发任务；
4、了解数据库设计，具备较强的SQL编写和优化能力；
5、具有较强的团队合作精神，能够与团队成员有效沟通，共同推动项目进展。';
        $prompt = "{$promptCommon}\n{$content}";


        echo "\n\n{$prompt}\n\n";
        if(1)
        {
            $response = $client->withModel('deepseek-r1-distill-qwen-7b')  // 模型名可随意，llama-server 不校验
                ->setTemperature(0.3)
                //->setMaxTokens(250)
                ->setResponseFormat('json_object')           // llama-server 可能不支持，见下方说明
                ->query($prompt)->run();
            // 解析响应并更新数据库
        }else
        {
            $payload = [
                'model'           => 'local',
                'messages'        => [['role' => 'user', 'content' => $prompt]],
                'temperature'     => 0.1,
              //  'max_tokens'      => 400,
                'response_format' => ['type' => 'json_object'],   // 这个字段必须出现在 body 里
                'stream'          => false,
            ];

            $ch = curl_init('http://127.0.0.1:8080/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 300,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
        }



        $printer= new Printer();
        // 解析响应并更新数据库
        $result = json_decode($response, true);
        var_export($result);

        function parseModelJson(string $raw): ?array
        {
            $s = trim($raw);

            // 1. 剥掉 ```json ... ``` 或 ``` ... ``` 代码块
            if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $s, $m)) {
                $s = trim($m[1]);
            }

            // 2. 有的模型前面会带思考文字，取第一个 { 到最后一个 }
            $start = strpos($s, '{');
            $end   = strrpos($s, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $s = substr($s, $start, $end - $start + 1);
            }

            // 3. 尝试直接解析
            $data = json_decode($s, true);
            if (is_array($data)) {
                return $data;
            }

            // 4. 兜底：清理常见的尾逗号、单引号等
            $s = preg_replace('/,\s*([}\]])/', '$1', $s);   // 去尾逗号
            $data = json_decode($s, true);
            if (is_array($data)) {
                return $data;
            }

            return null;
        }
        //  echo "{$response}";
        if (json_last_error() === JSON_ERROR_NONE)
        {
            if (isset($result['choices'][0]['message']['content']) && isset($result['choices'][0]['message']['reasoning_content']))
            {
                $resJson = parseModelJson($result['choices'][0]['message']['content']);
                if (json_last_error() === JSON_ERROR_NONE)
                {
                    $resJson['reason'] = $result['choices'][0]['message']['reasoning_content'];

                    $printer->tabEcho(CLIStrFormatter::success(json_encode($resJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));

                }
                else
                {
                    $printer->tabEcho(CLIStrFormatter::error("任务 ID: xxx  解码失败。\n{$result['choices'][0]['message']['content']}\n"));

                }
            }
            else
            {
                $printer->tabEcho(CLIStrFormatter::error("任务 ID: $id  (丢失信息)。\n" + json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));


            }
        }
        else
        {
            // 如果返回的不是有效JSON，将原始响应存入result
            $printer->tabEcho(CLIStrFormatter::error("任务   (非JSON格式)。\n{$response}\n"));
        }

    }

}