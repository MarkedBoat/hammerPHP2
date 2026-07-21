<?php

namespace modules\dev\v1\api\test;

use hammer\web\ActionBase;


class ActionOut extends ActionBase
{
//后期静态绑定代替了
    
    
    public function run()
    {







        $dbnames = [            'poseidon_media',            'poseidon_video',            'poseidon_user',            'poseidon_home',            'db_poseidon_cdn',            'db_poseidon_aggregate_media',            'db_poseidon_media',            'db_poseidon_operation',            'db_poseidon_site',            'db_poseidon_policy',            'db_poseidon_config',            'db_poseidon_btsync',            'db_poseidon_networkmonitor',            'db_poseidon_user',            'db_poseidon_interaction',            'db_poseidon_vip',            'db_fcg_repository',            'db_poseidon_audit',            'db_poseidon_credit',            'db_poseidon_search',            'vdw',            'db_poseidon_statistics',            'db_poseidon_sms',            'db_poseidon_msg',            'db_thirdpartnar',            'poseidon_unicom',            'db_poseidon_operation_b',            'db_poseidon_warning',            'db_poseidon_plets',            'db_poseidon_article',        ];
        //  Db\('(?!db)  不是db开头的也很多
        
        $dbnames_in_db=[
            'information_schema','db_poseidon_aggregate_media','db_poseidon_article','db_poseidon_btsync','db_poseidon_btsync_stats','db_poseidon_config','db_poseidon_credit','db_poseidon_media','db_poseidon_msg','db_poseidon_operation','db_poseidon_operation_b','db_poseidon_plets','db_poseidon_policy','db_poseidon_push','db_poseidon_search','db_poseidon_site','db_poseidon_site1','db_poseidon_sms','db_poseidon_statistics','db_poseidon_warning','db_thirdpartnar','kuaikan_video','mysql','performance_schema','poseidon_authority','poseidon_home','poseidon_media','poseidon_policy','poseidon_site','poseidon_user','poseidon_video','test','video','db_fcg_repository','db_poseidon_cdn','db_poseidon_audit'
        ];

        $confs = [
            'poseidon_media',            'poseidon_video',            'poseidon_user',            'poseidon_home',            'db_poseidon_cdn',            'poseidon_aggregate',            'poseidon_media_old',            'poseidon_operation',            'poseidon_site',            'db_poseidon_policy',            'db_poseidon_config',            'poseidon_btsync',            'db_poseidon_networkmonitor',            'db_poseidon_user',            'db_poseidon_interaction',            'poseidon_vip',            'db_fcg_repository',            'db_fcg_repository_play',            'db_poseidon_audit',            'poseidon_credit',            'db_poseidon_search',            'db_poseidon_operation',            'vdw',            'poseidon_interaction',            'poseidon_search',            'db_poseidon_statistics',            'poseidon_sms',            'db_poseidon_msg',            'db_thirdpartnar',            'poseidon_unicom',            'poseidon_operation_b',            'db_poseidon_warning',            'db_poseidon_plets',            'db_poseidon_article',
        ];
        $keys = ['poseidon_aggregate' => 'db_poseidon_aggregate_media',
                 'db_poseidon_networkmonitor' => 'db_poseidon_networkmonitor',
                 'db_poseidon_user' => 'db_poseidon_user',
                 'db_poseidon_interaction' => 'db_poseidon_interaction',
                 'poseidon_vip' => 'db_poseidon_vip',
                 'vdw' => 'vdw',
                 'poseidon_interaction' => 'db_poseidon_interaction',
                 'poseidon_unicom' => 'poseidon_unicom',

        ];
        echo '<pre>';

        foreach ($confs as $confname)
        {
            $dbname='';
            if(isset($keys[$confname])){
                $dbname=$keys[$confname];
            }else{
                $confname_db='db_'.$confname;
                $ar=explode('_',$confname);
                $confname_trim=join('_',array_slice($ar,0,count($ar)-1));

                if(in_array($confname_db,$dbnames_in_db)){
                    $dbname=$confname_db;
                }else{
                    if(in_array($confname,$dbnames_in_db)){
                        $dbname=$confname;
                    }else{
                        if(in_array($confname_trim,$dbnames_in_db)){
                            $dbname=$confname_trim;
                        }else{
                            var_dump([$confname]);die;
                        }
                    }
                }
            }



            echo " '{$confname}'           => array(
            'user'    =>self::user,
            'passwd'  =>self::passwd,
            'dbname'  => '{$dbname}'           
        ),";
        }


    }

}