<?php

namespace App\Labels;

use App\Order;

interface LabelInterface
{
    public function print();

    public function invoice();

    public function format();

    public function setOrder(Order $order);
}

//$ups->setOrder($order);
//
//$result = $ups->print();
//
//$ups->invoice();