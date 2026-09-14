<?php

namespace modules\spider\v1\api\boss_zhipin;

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

        $infos = $this->getRawJsonData();

        Sys::app()->letFileLogging(true)->log('chat', json_encode($infos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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


            $newchatDao = $chatDao->findByAttributes([
                'com_title' => $info['comName'],
                'hr_title'  => $info['hrTitle'],
                'job_title' => $info['title'],
            ]);
            if ($newchatDao)
            {
                $msgs = $newchatDao->last_chat_content['msgs'];
                $strs = [];
                foreach ($msgs as $msg)
                {
                    $isHr    = $msg['isHr'] ?? 'me';
                    $time    = $msg['time'] ?? 'time';
                    $content = $msg['textContent'] ?? 'content';
                    $strs[]  = "{$isHr}_{$time}_{$content}";
                }
                foreach ($info['msgs'] as $msg)
                {
                    $isHr    = $msg['isHr'] ?? 'me';
                    $time    = $msg['time'] ?? 'time';
                    $content = $msg['textContent'] ?? 'content';

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


                $newchatDao->last_chat_content = json_encode(['time' => date('Y-m-d H:i:s'), 'msgs' => $msgs], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $newchatDao->save();
            }
            else
            {

                $newchatDao                    = new BossChat();
                $newchatDao->com_title         = $info['comName'];
                $newchatDao->hr_title          = $info['hrTitle'];
                $newchatDao->job_title         = $info['title'];
                $newchatDao->last_chat_content = json_encode(['time' => date('Y-m-d H:i:s'), 'msgs' => $info['msgs']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $newchatDao->save();
            }

            $getBinds = $bind;
            unset($getBinds[':last_chat_content']);
            $row = $cmdGet->bindArray($getBinds)->queryRow();
            if ($row)
            {
                $rows[]                     = $row;
                $bind                       = [':id' => $row['id']];
                $bind[':last_chat_content'] = json_encode(['time' => date('Y-m-d H:i:s'), 'msgs' => $info['msgs']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
}