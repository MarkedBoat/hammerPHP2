<?php

namespace modules\spider\v1\api\boss_zhipin\spider;

use hammer\db\DbQuery;
use hammer\param\DataBox;
use hammer\sys\Sys;
use hammer\web\ActionBase;
use models\ext\tool\SimpleSSE;
use modules\spider\dao\BossComp;
use modules\spider\dao\BossJob;


class ActionTaskFlag extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {
        $action = $this->inputBox->checkInVals(['get', 'add', 'del'])->getNotEmptyString('action');

        $file     = __HAMMER_DIR__ . "/uploads/boss-zhipin/task-flag.json";
        $configKV = new \stdClass();
        if (!file_exists($file))
        {
            file_put_contents($file, $configKV);
        }
        else
        {
            $configKV = json_decode(file_get_contents($file));
            if (is_null($configKV))
            {
                $configKV = new \stdClass();
            }
        }
        if ($action === 'get')
        {
            if ($this->inputBox->tryGetString('sse') === 'yes')
            {
                $sse = new SimpleSSE();
                while (true)
                {
                    $sse->send(file_get_contents($file));
                    sleep(2);
                }
                $sse->end();
            }
            else
            {
                die(file_get_contents($file));
            }

            return $configKV;
        }
        else
        {
            $key = $this->inputBox->checkInVals(['index', 'jobs', 'detail'])->getNotEmptyString('key');
            if ($action === 'add')
            {
                $configKV->$key = 1;
                file_put_contents($file, json_encode($configKV));
            }
            else if ($action === 'del')
            {
                //unset($configKV->$key);
                $configKV->$key = 0;
                file_put_contents($file, json_encode($configKV));
            }
            return $configKV;
        }


    }

    public function isDebug()
    {
        return true;
    }
}