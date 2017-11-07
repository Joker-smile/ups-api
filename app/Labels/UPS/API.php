<?php
/**
 * Created by PhpStorm.
 * User: joker
 * Date: 17-11-6
 * Time: 下午2:37
 */

namespace App\Labels;

use Ups\Entity\Shipment;
class API{

    public function create($result)
    {
        // Start shipment
        $shipment = new Shipment();

        // Set shipper--发件人，必须
        $shipper = $this->setSender();
        $shipment->setShipper($shipper);

        // To address-收件人必须
        $shipTo = $this->setRecipient($result);
        $shipment->setShipTo($shipTo);

        // From address--发自，必须
        $shipFrom = $this->setShipFrom();
        $shipment->setShipFrom($shipFrom);

        // Sold to-- 销售方，当有发票的时候必须
        $soldTo = $this->setSold();
        $shipment->setSoldTo($soldTo);

        // Set service--服务方式，必须
        $service = $this->setService();
        $shipment->setService($service);

        // Set description--货物描述，必须
        $shipment->setDescription('refer to invoice');

        //add package
        $package = $this->addPackages();

        // Add descriptions because it is a package
        $package->setDescription('test');

        // Add this package
        $shipment->addPackage($package);

        // Set Reference Number--可选，参考编码
        $referenceNumber = new \Ups\Entity\ReferenceNumber;

        $referenceNumber->setCode(\Ups\Entity\ReferenceNumber::CODE_INVOICE_NUMBER);
        $referenceNumber->setValue('123456');
        //}
        $shipment->setReferenceNumber($referenceNumber);

        // Set payment information--必选，付款方式

        $shipment->setItemizedpaymentInformation(new \Ups\Entity\ItemizedPaymentInformation('Shipper', (object)array('AccountNumber' => '4FV119'), 'Shipper', (object)array('AccountNumber' => '4FV119')));

        //Set ShipmentServiceOptions--可选
        //invoice 发票--可选
        $shipmentserviceoptions = new \Ups\Entity\ShipmentServiceOptions;
        $internationlForms = $this->setInvoice();
        $shipmentserviceoptions->setInternationalForms($internationlForms);

        //发送电子邮件通知--可选
        $notificatin = $this->setNotify();
        $shipmentserviceoptions->addNotification($notificatin);
        $shipment->setShipmentServiceOptions($shipmentserviceoptions);

        // Ask for negotiated rates (optional)
        $rateInformation = new \Ups\Entity\RateInformation;
        $rateInformation->setNegotiatedRatesIndicator(1);
        $shipment->setRateInformation($rateInformation);

        return $shipment;

    }


    private  function setSender()
    {
        // Set shipper--发件人，必须
        // Start shipment
        $shipment = new Shipment();
        $shipper = $shipment->getShipper();
        $shipper->setShipperNumber('4FV119');// ups账号
        $shipper->setName('FUJIAN ZHENGYU TRADING CO.,LTD');
        $shipper->setAttentionName('MR LIN');
        $shipperAddress = $shipper->getAddress();
        $shipperAddress->setAddressLine1('8F GAOXIN KEJI CHUANGXIN');
        $shipperAddress->setAddressLine1('FUWU CENTER');
        $shipperAddress->setPostalCode('110120');
        $shipperAddress->setCity('QUANZHOU');
        $shipperAddress->setCountryCode('CN');
        $shipper->setAddress($shipperAddress);
        $shipper->setPhoneNumber('8618600000000');

        return $shipper;
    }


    private function setRecipient($result)
    {
        // To address-收件人必须
        $address = new \Ups\Entity\Address();
        $address->setAddressLine1(substr($result->address,0,35)); //地址行一，35个字符
        $address->setAddressLine2(substr($result->address,35,35));//地址行二，35个字符
        $address->setAddressLine3(substr($result->address,69));//地址行三，35个字符
        $address->setPostalCode($result->zip_code);//邮编
        $address->setCity($result->city);//城市
        $address->setCountryCode($result->country);//国家
        $address->setStateProvinceCode($result->state);//州
        $shipTo = new \Ups\Entity\ShipTo();
        $shipTo->setAddress($address);
        $shipTo->setCompanyName('FORU'); //公司名
        $shipTo->setAttentionName($result->name);//联系人名
        $shipTo->setPhoneNumber($result->phone_number);//电话号码
        return $shipTo;
    }

    private function setShipFrom()
    {
        // From address--发自，必须
        $address = new \Ups\Entity\Address();
        $address->setAddressLine1('8F GAOXIN KEJI CHUANGXIN');
        $address->setAddressLine2('FUWU CENTE');
        $address->setPostalCode('110120');
        $address->setCity('QUANZHOU');
        $address->setCountryCode('CN');
        $address->setStateProvinceCode('FJ');
        $shipFrom = new \Ups\Entity\ShipFrom();
        $shipFrom->setAddress($address);
        $shipFrom->setAttentionName('MR LIN');
        $shipFrom->setCompanyName('FUJIAN ZHENGYU TRADING CO.,LTD');
        $shipFrom->setPhoneNumber('8618600000000');

        return $shipFrom;
    }
    private function setSold()
    {
        // Sold to-- 销售方，当有发票的时候必须
        $address = new \Ups\Entity\Address();
        $address->setAddressLine1('11111111');
        $address->setAddressLine2('11111111');
        $address->setAddressLine3('11111111');
        $address->setPostalCode('10018');
        $address->setCity('New York');
        $address->setCountryCode('US');
        $address->setStateProvinceCode('NY');
        $soldTo = new \Ups\Entity\SoldTo;
        $soldTo->setAddress($address);
        $soldTo->setAttentionName('NATLIN');
        $soldTo->setCompanyName($soldTo->getAttentionName());
        $soldTo->setPhoneNumber('11111111111');
        return $soldTo;
    }
    private function setService()
    {
        // Set service--服务方式，必须
        $service = new \Ups\Entity\Service;
        $service->setCode(\Ups\Entity\Service::S_SAVER);//服务方式S_WW_EXPEDITED，S_SAVER，S_WW_EXPRESS，S_WW_EXPRESSPLUS
        $service->setDescription($service->getName());
        return $service;
    }

    private function setInvoice()
    {
        //set invoice
        $internationlForms = new \Ups\Entity\InternationalForms;
        $internationlForms->setType(\Ups\Entity\InternationalForms::TYPE_INVOICE);
        $internationlForms->setReasonForExport(\Ups\Entity\InternationalForms::RFE_SAMPLE);
        $invoicedate = new \DateTime('2017-11-02');
        $internationlForms->setInvoiceDate($invoicedate);
        $internationlForms->setCurrencyCode('USD');

        $product = new \Ups\Entity\Product;
        $product->setDescription1('bags');
        $unitinv = new \Ups\Entity\Unit;
        $unitinvm = new \Ups\Entity\UnitOfMeasurement;
        $unitinv->setNumber('100');
        $unitinv->setValue('1');
        $unitinvm->setCode(\Ups\Entity\UnitOfMeasurement::PROD_PIECES);
        $unitinv->setUnitOfMeasurement($unitinvm);
        $product->setUnit($unitinv);
        $product->setPartNumber('123456');
        $product->setOriginCountryCode('CN');
        $internationlForms->addProduct($product);
        return $internationlForms;
    }


    private function setNotify()
    {
        //设置通知
        $notificatin = new \Ups\Entity\Notification;
        $notificatin->setNotificationCode(\Ups\Entity\Notification::CODE_QV_SHIP);
        $emailMessage = new \Ups\Entity\EmailMessage;
        $emailaddress = array('1067197739@qq.com');
        $emailMessage->setEmailAddresses($emailaddress);
        $notificatin->setEmailMessage($emailMessage);
        return $notificatin;
    }

    private  function addPackages()
    {
        // Add Package--包裹，必须
        $package = new \Ups\Entity\Package();
        $package->getPackagingType()->setCode(\Ups\Entity\PackagingType::PT_PACKAGE);
        $package->getPackageWeight()->setWeight(10);
        $unit = new \Ups\Entity\UnitOfMeasurement;
        $unit->setCode(\Ups\Entity\UnitOfMeasurement::UOM_KGS);
        $package->getPackageWeight()->setUnitOfMeasurement($unit);

        // Set dimensions--箱规，不是必须的，可以不填
        $dimensions = new \Ups\Entity\Dimensions();
        $dimensions->setHeight(50);
        $dimensions->setWidth(50);
        $dimensions->setLength(50);
        $unit = new \Ups\Entity\UnitOfMeasurement;
        $unit->setCode(\Ups\Entity\UnitOfMeasurement::UOM_CM);
        $dimensions->setUnitOfMeasurement($unit);
        $package->setDimensions($dimensions);
        return $package;
    }


}