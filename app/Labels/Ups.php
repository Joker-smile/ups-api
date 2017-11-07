<?php

namespace App\Labels;

use App\Order;
use App\Labels\ShipFunction;
class Ups implements LabelInterface
{

    public function setOrder(Order $order)
    {
        $this->order = $order;
    }

    //获取PDF发票
    public function invoice()
    {
//        $accept = $this->shipping();
//        $content=base64_decode($accept->Form->Image->GraphicImage);
//        return $content;
    }

    public function format()
    {
        return 'gif';
    }

    /*
     * 打印gif 面单
     * @param  gif $content
     * @param  TrackingNumber $number
     * return string
     * */
    public function print()
    {
        $accept = $this->shipping();
        $content = base64_decode($accept->PackageResults->LabelImage->GraphicImage);
        $number = $accept->PackageResults->TrackingNumber;
        $is_file = false;
        return compact('content', 'number', 'is_file');
    }

    public function shipping()
    {
        $accessKey = config('ups.access_key');
        $userId = config('ups.user_id');
        $password = config('ups.password');


        $result=$this->order;
        $res=new ShipFunction();
        $shipment=$res->allFunction($result);
        // Get shipment info
        try {
            $api = new \Ups\Shipping($accessKey, $userId, $password);
            $labelSpec = new \Ups\Entity\ShipmentRequestLabelSpecification(\Ups\Entity\ShipmentRequestLabelSpecification::IMG_FORMAT_CODE_GIF);

            $labelSpec->setStockSizeHeight('4');
            $labelSpec->setStockSizeWidth('6');

            $confirm = $api->confirm(\Ups\Shipping::REQ_VALIDATE, $shipment);

            if ($confirm) {
                $accept = $api->accept($confirm->ShipmentDigest);

                return $accept;
            }
        } catch (\Exception $e) {
            var_dump($e);
        }

    }
}