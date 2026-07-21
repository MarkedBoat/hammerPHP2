<?php

namespace modules\dev\v1\api\KL_PC;

use hammer\web\ActionBase;


class ActionGetHdItems extends ActionBase
{

//后期静态绑定代替了

    public function run()
    {

        @ob_clean();
        $hdItemsFilename = __APP_DIR__ . '/config/file/xx_video/hd_items.json';
        $hdItems         = [];
        $isExist         = false;
        if (!file_exists($hdItemsFilename))
        {
            $hdItems = json_decode('[{"height":720,"maxRateK":1600,"fps":24,"title":"骑兵/标准"},{"height":720,"maxRateK":2000,"fps":24,"title":"720 2000k 24fps"},{"height":720,"maxRateK":2400,"fps":30,"title":"720高帧"},{"height":720,"maxRateK":2400,"fps":24,"title":"高清720"},{"height":720,"maxRateK":3000,"fps":30,"title":"超清清720 30fps"},{"height":720,"maxRateK":3000,"fps":24,"title":"超清清720 24fps"},{"height":1080,"maxRateK":1600,"fps":24,"title":"骑兵/标准/低质手机"},{"height":1080,"maxRateK":2000,"fps":24,"title":"1080P 2M 24fps"},{"height":1080,"maxRateK":2400,"fps":24,"title":"普通步兵1080"},{"height":1080,"maxRateK":3000,"fps":30,"title":"顶级步兵1080"}]', true);
        }
        else
        {
            $isExist = true;
            $hdItems = json_decode(file_get_contents($hdItemsFilename), true);
        }
        return ['hdItems' => $hdItems, 'x' => $isExist];

         \hammer\web\HttpApp::$hasOutput = true;
        die(file_get_contents($filename));


    }

}