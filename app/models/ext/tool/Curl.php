<?php

namespace models\ext\tool;
class Curl
{
    static public function curlPostSafeUrl($url, $params)
    {
        $agent = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.1 Safari/537.11';
        $curl  = curl_init();
        // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $agent);
        // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1);
        // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl);
        // 执行操作
        if (curl_errno($curl))
        {
            echo 'Errno:' . curl_error($curl);
            //捕抓异常
            return false;
        }
        else
        {
            curl_close($curl);
            // 关闭CURL会话
            return $tmpInfo;
            // 返回数据
        }
    }

    static public function curlGetSafeUrl($url, &$info = [])
    {
        $agent = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.1 Safari/537.11';
        $curl  = curl_init();
        // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $agent);
        // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, false);
        // 发送一个常规的Post请求
        //curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 获取的信息以文件流的形式返回

        //header头里面写点玩意
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer access_token'));

        $tmpInfo = curl_exec($curl);
        $info    = curl_getinfo($curl);
        // 执行操作
        if (curl_errno($curl))
        {
            echo 'Errno:' . curl_error($curl);
            //捕抓异常
            return false;
        }
        else
        {
            curl_close($curl);
            // 关闭CURL会话
            return $tmpInfo;
            // 返回数据
        }
    }


    public static function curlRequest($url, $params, $method = 'post')
    {
        $AllParams = http_build_query($params);
        //   return self::curlRequest($apis[$env], $AllParams, '', 'post');

        $user_agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)";
        $ch         = curl_init();    // 初始化CURL句柄
        //   curl_setopt($ch, CURLOPT_PROXY, $proxy);//设置代理服务器

        curl_setopt($ch, CURLOPT_URL, $url);        //设置请求的URL
        //curl_setopt($ch, CURLOPT_FAILONERROR, 1); // 启用时显示HTTP状态码，默认行为是忽略编号小于等于400的HTTP信息
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//启用时会将服务器服务器返回的“Location:”放在header中递归的返回给服务器
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);// 设为TRUE把curl_exec()结果转化为字串，而不是直接输出
        if ('post' == $method)
        {
            curl_setopt($ch, CURLOPT_POST, 1);                //启用POST提交
            curl_setopt($ch, CURLOPT_POSTFIELDS, $AllParams); //设置POST提交的字符串
        }
        //curl_setopt($ch, CURLOPT_PORT, 80); //设置端口
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);      // 超时时间
        //curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);//HTTP请求User-Agent:头
        //curl_setopt($ch,CURLOPT_HEADER,1);//设为TRUE在输出中包含头信息
        //$fp = fopen("example_homepage.txt", "w");//输出文件
        //curl_setopt($ch, CURLOPT_FILE, $fp);//设置输出文件的位置，值是一个资源类型，默认为STDOUT (浏览器)。
        //SSL
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        //curl_setopt($ch,CURLOPT_CAINFO,dirname(__FILE__).'/../cacert.pem');

        /*curl_setopt($ch,CURLOPT_HTTPHEADER,array(
            'Accept-Language: zh-cn',
            'Connection: Keep-Alive',
            'Cache-Control: no-cache'
        ));*/
        //设置HTTP头信息
        $document = curl_exec($ch);                 //执行预定义的CURL
        $info     = curl_getinfo($ch);              //得到返回信息的特性


        /*
        if($info[http_code]=="405"){
             echo "bad proxy {$proxy}\n";  //代理出错
            exit;
         }*/

        $result = array(
            '__content' => $document,
            '__info'    => $info
        );

        curl_close($ch);
        return $result;
    }

    public static function upToContent($filename)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => "http://content.fengmi.tv/upload.php",
            CURLOPT_REFERER        => 'funtv_server',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => class_exists('\CURLFile') ? array('file' => new \CURLFile($filename)) : array('file' => '@' . realpath($filename)),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}

