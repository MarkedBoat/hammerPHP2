<?php

namespace modules\dev\v1\api\funtv\conf\redis;

use hammer\web\ActionBase;


class ActionMap extends ActionBase
{

//后期静态绑定代替了

    public function run()
    {

        return json_decode(file_get_contents('/data/code/poseidon_server/phplib/common/doc-kl/conf/redis_map.json'),true);
    }

}