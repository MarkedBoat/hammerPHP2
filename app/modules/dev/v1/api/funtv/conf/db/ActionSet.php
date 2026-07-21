<?php

namespace modules\dev\v1\api\funtv\conf\db;

use hammer\web\ActionBase;


class ActionSet extends ActionBase
{
//后期静态绑定代替了

    //find /data/code/poseidon_server/ -name mysql_conf.php > /home/kinglone/poseidon_server_mysql_conf_files
    public function run()
    {
        $post   = json_decode($this->inputBox->getNotEmptyString('opts'), true);
        $map    = json_decode(file_get_contents('/data/code/poseidon_server/phplib/common/doc-kl/conf/mysql_map.json'), true);
        $conf   = array('encode' => 'utf8','is_common_dbconf' => true);
        $rows   = array();
        $is_all = isset($post['is_all']) ? $post['is_all'] : false;
        unset($post['is_all']);
        $env = $post['all_env'];
        unset($post['all_env']);
        if ($is_all === false)
        {
            foreach ($post as $key => $env)
            {
                $conf[$key] = $map[$key][$env];
                $rows[]     = array('key' => $key, 'val' => $env);
            }
        }
        else
        {
            foreach ($post as $key => $env_)
            {
                $conf[$key] = $map[$key][$env];
                $rows[]     = array('key' => $key, 'val' => $env);
            }
        }

        $data = "<?php\nclass Conf_Mysql extends Common_Dbconf\n{\nconst TRY_MAX = 3;\npublic static \$dbconf=" . var_export($conf, true) . ";\n public static function initConf(\$key){}
    \n}\n\n";
        $r1   = file_put_contents('/data/code/poseidon_server/phplib/common/doc-kl/conf/mysql_map_opt.json', json_encode($rows));
        $r3   = file_put_contents('/data/code/poseidon_server/phplib/common/doc-kl/conf/mysql.php', $data);

        $r4 = file_put_contents('/data/code/poseidon_cms/phplib/common/doc-kl/conf/mysql_map_opt.json', json_encode($rows));
        $r6 = file_put_contents('/data/code/poseidon_cms/phplib/common/doc-kl/conf/mysql.php', $data);

        return array('rows' => $rows, 'conf' => $conf, array($r1, $r3), array($r4, $r6));
    }

}