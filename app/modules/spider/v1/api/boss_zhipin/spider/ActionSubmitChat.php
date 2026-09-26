<?php

namespace modules\spider\v1\api\boss_zhipin\spider;

use hammer\param\DataBox;
use hammer\sys\Sys;
use hammer\web\ActionBase;
use models\common\Def;
use modules\spider\dao\BossChat;
use modules\spider\dao\BossComp;
use modules\spider\dao\BossJob;


class ActionSubmitChat extends ActionBase
{
    protected $inputType = 'JSON';

    /**
     * @throws \Exception
     */
    public function run()
    {
        date_default_timezone_set('Asia/Shanghai');
        $infos = $this->getRawJsonData();

        Sys::app()->letFileLogging(true)->log('chat', $infos);
        Sys::app()->setDebug(true);

        $jobDao      = new BossJob();
        $comDao      = new BossComp();
        $chatDao     = new BossChat();
        $jobTn       = $jobDao->getTableName();
        $comTn       = $comDao->getTableName();
        $chatTn      = $chatDao->getTableName();
        $cmdUpdate   = $jobDao->getConnection()->setText("update  {$jobTn} set last_chat_content=:last_chat_content where id=:id");
        $cmdGet      = $jobDao->getConnection()->setText("select jt.id,jt.title,jt.hr_title,ct.title from {$jobTn} as jt left join {$comTn} as ct on jt.com_id=ct.com_id where ct.title=:com_title and jt.hr_title=:hr_title and jt.title=:job_title");
        $cmdFindChat = $chatDao->getConnection()->setText("select * from {$chatTn} where com_title=:com_title and hr_title=:hr_title and job_title=:job_title");
        $attrs       = ['hrTitle' => ':hr_title', 'comName' => ':com_title', 'title' => ':job_title', 'msgs' => ':last_chat_content'];
        $allErrors   = [];
        $sqls        = [];
        $emptys      = [];
        $rows        = [];
        foreach ($infos as $info)
        {
            $errors = [];
            $bind   = [];
            foreach ($attrs as $attr => $filed)
            {
                if (empty($info[$attr]))
                {
                    $errors[] = $attr;
                }
                else
                {
                    $bind[$filed] = $info[$attr];
                }
            }

            if (count($errors) > 0)
            {
                $allErrors[] = $errors;
                continue;
            }

            $startDateTime = '2000-01-01 00:00:00';
            if (isset($info['startTime']))
            {
                $startDateTime = $this->normalizeCharTime($info['startTime']);
            }


            $newchatDao = $chatDao->findByAttributes([
                'com_title' => $info['comName'],
                'hr_title'  => $info['hrTitle'],
                'job_title' => $info['title'],
            ]);
            if ($newchatDao)
            {
                $msgs = $newchatDao->last_chat_content['msgs'];
                $strs = [];
                foreach ($msgs as $tmp_i => $msg)
                {
                    $sendTime                 = $this->normalizeCharTime($msg['time'] ?? '');
                    $msgs[$tmp_i]['sendTime'] = $sendTime;
                    $isHr                     = $msg['isHr'] ?? 'me';
                    $time                     = $sendTime ?? 'time';
                    $content                  = $msg['textContent'] ?? 'content';
                    $strs[]                   = "{$isHr}_{$time}_{$content}";

                }
                foreach ($info['msgs'] as $msg)
                {
                    $sendTime = $this->normalizeCharTime($msg['time'] ?? '');
                    $isHr     = $msg['isHr'] ?? 'me';
                    $time     = $sendTime ?? 'time';
                    $content  = $msg['textContent'] ?? 'content';

                    $str = "{$isHr}_{$time}_{$content}";
                    if (in_array($str, $strs, true))
                    {

                    }
                    else
                    {
                        $strs[] = $str;
                        $msgs[] = $msg;
                    }
                }

                if ($newchatDao->start_date === '2000-01-01 00:00:00' && strtotime($newchatDao->start_date) > $startDateTime)
                {
                    $newchatDao->start_date = $startDateTime;
                }
                $newchatDao->last_chat_content = json_encode(['time' => date('Y-m-d H:i:s'), 'start_date' => $info['startTime'] ?? '', 'msgs' => $msgs], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $newchatDao->save();
            }
            else
            {

                $newchatDao            = new BossChat();
                $newchatDao->com_title = $info['comName'];
                $newchatDao->hr_title  = $info['hrTitle'];
                $newchatDao->job_title = $info['title'];
                foreach ($info['msgs'] as $tmp_i => $msg)
                {
                    $sendTime                         = $this->normalizeCharTime($msg['time'] ?? '');
                    $info['msgs'][$tmp_i]['sendTime'] = $sendTime;
                }
                $newchatDao->last_chat_content = json_encode(['time' => date('Y-m-d H:i:s'), 'start_date' => $info['startTime'] ?? '', 'msgs' => $info['msgs']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                $newchatDao->start_date = $startDateTime;

                $newchatDao->save();
            }

            $getBinds = $bind;
            unset($getBinds[':last_chat_content']);
            $row = $cmdGet->bindArray($getBinds)->queryRow();
            if ($row)
            {
                $rows[]                     = $row;
                $bind                       = [':id' => $row['id']];
                $bind[':last_chat_content'] = json_encode(['time' => date('Y-m-d H:i:s'), 'start_date' => $info['startTime'] ?? '', 'msgs' => $info['msgs']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                try
                {
                    $cmdUpdate->bindArray($bind)->execute();

                } catch (\Exception $e)
                {
                    $sqls[] = $cmdUpdate->getDebugInfo();
                }

            }
            else
            {
                $emptys[] = $info;
            }


        }


        return ['allErrors' => $allErrors, 'sqls' => $sqls, 'empty' => $emptys, 'rows' => $rows];


    }

    public function isDebug()
    {
        return true;
    }

    /**
     * 标准化 last_time
     *
     * @param mixed $last_time 原始时间
     * @param string $format 输出格式
     * @return string|null
     */
    function normalizeCharTime($last_time, $format = 'Y-m-d H:i:s')
    {
        if ($last_time === null)
        {
            return '';
        }

        $raw = trim((string)$last_time);

        if ($raw === '' || strtoupper($raw) === 'NULL')
        {
            return '';
        }

        // 统一全角符号和多余空格
        $raw = str_replace(['：', '／', '－', '．'], [':', '/', '-', '.'], $raw);
        $raw = preg_replace('/\s+/u', ' ', $raw);

        $now      = time();
        $curYear  = (int)date('Y', $now);
        $curMonth = (int)date('n', $now);
        $curDay   = (int)date('j', $now);

        // 1. 已经是完整日期时间：2026-09-17 10:05:00、2026-09-17 10:05、2026-09-17
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}(?:[ T]\d{1,2}:\d{2}(?::\d{2})?)?$/', $raw))
        {
            $ts = strtotime($raw);
            return $ts !== false ? date($format, $ts) : null;
        }

        // 2. 中文完整日期：2026年9月10日 23:19
        if (preg_match('/^(\d{4})年(\d{1,2})月(\d{1,2})日\s*(\d{1,2}):(\d{2})(?::(\d{2}))?$/u', $raw, $match))
        {
            $year   = (int)$match[1];
            $month  = (int)$match[2];
            $day    = (int)$match[3];
            $hour   = (int)$match[4];
            $minute = (int)$match[5];
            $second = isset($match[6]) ? (int)$match[6] : 0;

            if (!checkdate($month, $day, $year) || $hour > 23 || $minute > 59 || $second > 59)
            {
                return '';
            }

            return date($format, mktime($hour, $minute, $second, $month, $day, $year));
        }

        // 3. 昨天 14:29
        if (preg_match('/^昨天\s*(\d{1,2}):(\d{2})(?::(\d{2}))?$/u', $raw, $match))
        {
            $hour   = (int)$match[1];
            $minute = (int)$match[2];
            $second = isset($match[3]) ? (int)$match[3] : 0;

            if ($hour > 23 || $minute > 59 || $second > 59)
            {
                return '';
            }

            $ts = mktime($hour, $minute, $second, $curMonth, $curDay - 1, $curYear);
            return date($format, $ts);
        }

        // 4. 今天 14:29
        if (preg_match('/^今天\s*(\d{1,2}):(\d{2})(?::(\d{2}))?$/u', $raw, $match))
        {
            $hour   = (int)$match[1];
            $minute = (int)$match[2];
            $second = isset($match[3]) ? (int)$match[3] : 0;

            if ($hour > 23 || $minute > 59 || $second > 59)
            {
                return '';
            }

            $ts = mktime($hour, $minute, $second, $curMonth, $curDay, $curYear);
            return date($format, $ts);
        }

        // 5. 月-日 时:分：09-10 23:19
        if (preg_match('/^(\d{1,2})-(\d{1,2})\s+(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $raw, $match))
        {
            $month  = (int)$match[1];
            $day    = (int)$match[2];
            $hour   = (int)$match[3];
            $minute = (int)$match[4];
            $second = isset($match[5]) ? (int)$match[5] : 0;

            if (!checkdate($month, $day, $curYear) || $hour > 23 || $minute > 59 || $second > 59)
            {
                return '';
            }

            $ts = mktime($hour, $minute, $second, $month, $day, $curYear);

            // 无年份时，如果解析结果晚于当前时间，按去年处理
            if ($ts > $now)
            {
                $year = $curYear - 1;
                if (!checkdate($month, $day, $year))
                {
                    return '';
                }
                $ts = mktime($hour, $minute, $second, $month, $day, $year);
            }

            return date($format, $ts);
        }

        // 6. 月/日 时:分：09/10 23:19
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\s+(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $raw, $match))
        {
            $month  = (int)$match[1];
            $day    = (int)$match[2];
            $hour   = (int)$match[3];
            $minute = (int)$match[4];
            $second = isset($match[5]) ? (int)$match[5] : 0;

            if (!checkdate($month, $day, $curYear) || $hour > 23 || $minute > 59 || $second > 59)
            {
                return '';
            }

            $ts = mktime($hour, $minute, $second, $month, $day, $curYear);

            if ($ts > $now)
            {
                $year = $curYear - 1;
                if (!checkdate($month, $day, $year))
                {
                    return '';
                }
                $ts = mktime($hour, $minute, $second, $month, $day, $year);
            }

            return date($format, $ts);
        }

        // 7. 只有时间：10:05、11:14、11:20
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $raw, $match))
        {
            $hour   = (int)$match[1];
            $minute = (int)$match[2];
            $second = isset($match[3]) ? (int)$match[3] : 0;

            if ($hour > 23 || $minute > 59 || $second > 59)
            {
                return '';
            }

            $ts = mktime($hour, $minute, $second, $curMonth, $curDay, $curYear);

            // 只有时间且晚于当前时间，通常表示昨天
            if ($ts > $now)
            {
                $ts = mktime($hour, $minute, $second, $curMonth, $curDay - 1, $curYear);
            }

            return date($format, $ts);
        }

        // 无法识别时返回 null；如果希望保留原值，可改成 return $raw;
        return null;
    }

}