<?php

namespace modules\dev\v1\api\pay;

use hammer\web\ActionBase;
use hammer\sys\Sys;
use models\ext\tool\Curl;


class ActionApple extends ActionBase
{
    /**
     * @return string
     */
//后期静态绑定代替了

    public function run()
    {

        $str=file_get_contents($_FILES['receipt']['tmp_name']);

        $url   = 'https://sandbox.itunes.apple.com/verifyReceipt';
        //$url='https://buy.itunes.apple.com/verifyReceipt';
        $json=json_encode([
            'receipt-data' => $str,
             //'password'     => 'e918bb59195545a09e83d408a2847f02'
        ]);
        die($json);
        $str      = Curl::curlPostSafeUrl($url,$json );
        $data     = json_decode($str, true);

        die($str);
      //  var_dump($data);
      //  echo "\n";
       // echo $json;
        die;
        if (!(isset($data['receipt']['bundle_id']) && isset($data['receipt']['in_app']) && is_array($data['receipt']['in_app']) && count($data['receipt']['in_app'])))die('接口有问题');
      //  if ($data['receipt']['bundle_id'] !== 'com.fangtangtv.huxiaobao')
            var_dump($data);
        $maxTime = 0;
        $info    = [];
        foreach ($data['receipt']['in_app'] as $info2) {
            $ts = intval($info2['purchase_date_ms']);

        }
        $ipayOrderId = $info['transaction_id'];

    }

}