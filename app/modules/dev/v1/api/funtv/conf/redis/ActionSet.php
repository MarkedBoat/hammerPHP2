<?php

namespace modules\dev\v1\api\funtv\conf\redis;

use hammer\web\ActionBase;


class ActionSet extends ActionBase
{
//后期静态绑定代替了

    //find /data/code/poseidon_server/ -name redis_conf.php > /home/kinglone/poseidon_server_redis_conf_files
    public function run()
    {
        $post = json_decode($this->inputBox->getNotEmptyString('opts'), true);
        $map  = json_decode(file_get_contents('/data/code/poseidon_server/phplib/common/doc-kl/conf/redis_map.json'), true);
        $conf = array();
        $rows = array();


        $is_all = isset($post['is_all']) ? $post['is_all'] : false;
        unset($post['is_all']);
        $env = $post['all_env'];
        unset($post['all_env']);
        if ($is_all === false)
        {
            foreach ($post as $key => $env)
            {
                $conf[$key] = $map[$env];
                $rows[]     = array('key' => $key, 'val' => $env);
            }
        }
        else
        {
            foreach ($post as $key => $env_)
            {
                $conf[$key] = $map[$env];
                $rows[]     = array('key' => $key, 'val' => $env);
            }
        }


        $data = "<?php\nclass Conf_Redis\n{\npublic static \$conf=" . var_export($conf, true) . ";\n}\n";
        $r1   = file_put_contents('/data/code/poseidon_server/phplib/common/doc-kl/conf/redis_map_opt.json', json_encode($rows));
        $r3   = file_put_contents('/data/code/poseidon_server/phplib/common/doc-kl/conf/redis.php', $data);

        $r4   = file_put_contents('/data/code/poseidon_cms/phplib/common/doc-kl/conf/redis_map_opt.json', json_encode($rows));
        $r6   = file_put_contents('/data/code/poseidon_cms/phplib/common/doc-kl/conf/redis.php', $data);
        return array('rows' => $rows, 'conf' => $conf, array($r1, $r3),array($r4, $r6));
    }

}