<?php

namespace models\common\api;

use hammer\common\Respone;
use hammer\sys\Sys;
use hammer\web\InterfaceResFormatter;

class ResFormatter implements InterfaceResFormatter
{
    public function __construct()
    {

    }
    public function outputAndReturnState(Respone $respone)
    {
        $type = $respone->getType();
        $res = $respone->getData();
        switch ($type) {
            case Respone::TYPE_JSON:
                @header('content-Type:application/json;charset=utf8');
                $data = [
                    'status' => 200,
                    'data'   => $res,
                    'code'   => Sys::app()->interruption()->getCode(),
                ];
                break;
            case Respone::xml:
                $this->outputXml($respone);
                break;
            case Respone::html:
                $this->outputHtml($respone);
                break;
            default:
                $this->outputJson($respone);
                break;
        }
    }
}