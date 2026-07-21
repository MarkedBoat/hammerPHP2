<?php

namespace modules\dev\v1\api\funtv\conf\db;

use hammer\web\ActionBase;


class ActionOpts extends ActionBase
{
//后期静态绑定代替了

    public function run()
    {
        $rows = json_decode(file_get_contents('/data/code/poseidon_server/phplib/common/doc-kl/conf/mysql_map_opt.json'), true);
        $map=array();

        foreach ($rows as $row){
            $map[$row['key']]=$row;
        }
        ksort($map);

        return array_values($map);
    }

}