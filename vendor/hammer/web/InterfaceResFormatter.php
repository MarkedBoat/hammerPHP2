<?php

namespace hammer\web;

use hammer\common\Respone;

interface InterfaceResFormatter
{
    public function outputAndReturnState(Respone $respone);
}