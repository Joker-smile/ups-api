<?php
/**
 * Created by PhpStorm.
 * User: joker
 * Date: 17-11-6
 * Time: 下午2:37
 */

namespace App\Labels\UPS;

use Ups\Entity\Shipment;
use Ups\Entity\ItemizedPaymentInformation;
use Ups\Entity\ShipmentServiceOptions;
use Ups\Entity\RateInformation;
use Ups\Entity\ReferenceNumber;
use Ups\Entity\Address;
use Ups\Entity\ShipTo;
use Ups\Entity\ShipFrom;
use Ups\Entity\SoldTo;
use Ups\Entity\Service;
use Ups\Entity\InternationalForms;
use Ups\Entity\Product;
use Ups\Entity\Unit;
use Ups\Entity\UnitOfMeasurement;
use Ups\Entity\Notification;
use Ups\Entity\EmailMessage;
use Ups\Entity\Package;
use Ups\Entity\PackagingType;
use Ups\Entity\Dimensions;
class API{

    private $shipment;
    private $address;
    public function __construct(Shipment $shipment,Address $address)
    {
        $this->shipment=$shipment;
        $this->address=$address;
    }

    public function create($result)
    {
        // Start shipment
        $shipTo = $this->setRecipient($result);
        $soldTo = $this->setSold();

        $shipFrom = $this->setShipFrom();
        $service = $this->setService();

        $package = $this->addPackages();
        $package->setDescription('test');

        $referenceNumber=$this->setReferenceNumber();
        $shipper = $this->setSender();

        $shipmentserviceoptions = new ShipmentServiceOptions();
        $internationlForms = $this->setInvoice();
        $notificatin = $this->setNotify();
        $shipmentserviceoptions->addNotification($notificatin);
        $shipmentserviceoptions->setInternationalForms($internationlForms);

        $rateInformation = new RateInformation();
        $rateInformation->setNegotiatedRatesIndicator(1);

        $this->shipment->setShipper($shipper)
            ->setShipTo($shipTo)->setSoldTo($soldTo)
            ->setShipFrom($shipFrom)->setService($service)
            ->setDescription('refer to invoice')->addPackage($package)
            ->setReferenceNumber($referenceNumber)
            ->setShipmentServiceOptions($shipmentserviceoptions)
            ->setRateInformation($rateInformation);
        $this->shipment->setItemizedpaymentInformation(new ItemizedPaymentInformation('Shipper', (object)array('AccountNumber' => '4FV119'), 'Shipper', (object)array('AccountNumber' => '4FV119')));

        return $this->shipment;

    }

    private function setReferenceNumber()
    {
        // Set Reference Number--可选，参考编码
        $referenceNumber = new ReferenceNumber();
        $referenceNumber->setCode(ReferenceNumber::CODE_INVOICE_NUMBER);
        $referenceNumber->setValue('123456');
        return $referenceNumber;
    }

    private  function setSender()
    {
        // Set shipper--发件人，必须
        $shipper = $this->shipment->getShipper();
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
        $this->address->setAddressLine1(substr($result->address,0,35)); //地址行一，35个字符
        $this->address->setAddressLine2(substr($result->address,35,35));//地址行二，35个字符
        $this->address->setAddressLine3(substr($result->address,70));//地址行三，35个字符
        $this->address->setPostalCode($result->zip_code);//邮编
        $this->address->setCity($result->city);//城市
        $this->address->setCountryCode($result->country);//国家
        $this->address->setStateProvinceCode($result->state);//州
        $shipTo = new ShipTo();
        $shipTo->setAddress($this->address);
        $shipTo->setCompanyName('FORU'); //公司名
        $shipTo->setAttentionName($result->name);//联系人名
        $shipTo->setPhoneNumber($result->phone_number);//电话号码

        return $shipTo;
    }

    private function setShipFrom()
    {
        // From address--发自，必须
        $this->address->setAddressLine1('8F GAOXIN KEJI CHUANGXIN');
        $this->address->setAddressLine2('FUWU CENTE');
        $this->address->setPostalCode('110120');
        $this->address->setCity('QUANZHOU');
        $this->address->setCountryCode('CN');
        $this->address->setStateProvinceCode('FJ');
        $shipFrom = new ShipFrom();
        $shipFrom->setAddress($this->address);
        $shipFrom->setAttentionName('MR LIN');
        $shipFrom->setCompanyName('FUJIAN ZHENGYU TRADING CO.,LTD');
        $shipFrom->setPhoneNumber('8618600000000');

        return $shipFrom;
    }

    private function setSold()
    {
        // Sold to-- 销售方，当有发票的时候必须
        $this->address->setAddressLine1('11111111');
        $this->address->setAddressLine2('11111111');
        $this->address->setAddressLine3('11111111');
        $this->address->setPostalCode('10018');
        $this->address->setCity('New York');
        $this->address->setCountryCode('US');
        $this->address->setStateProvinceCode('NY');
        $soldTo = new SoldTo();
        $soldTo->setAddress($this->address);
        $soldTo->setAttentionName('NATLIN');
        $soldTo->setCompanyName($soldTo->getAttentionName());
        $soldTo->setPhoneNumber('11111111111');

        return $soldTo;
    }

    private function setService()
    {
        // Set service--服务方式，必须
        $service = new Service();
        $service->setCode(Service::S_SAVER);//服务方式S_WW_EXPEDITED，S_SAVER，S_WW_EXPRESS，S_WW_EXPRESSPLUS
        $service->setDescription($service->getName());

        return $service;
    }

    private function setInvoice()
    {
        //set invoice
        $internationlForms = new InternationalForms();
        $internationlForms->setType(InternationalForms::TYPE_INVOICE);
        $internationlForms->setReasonForExport(InternationalForms::RFE_SAMPLE);
        $invoicedate = new \DateTime('2017-11-02');
        $internationlForms->setInvoiceDate($invoicedate);
        $internationlForms->setCurrencyCode('USD');

        $product = new Product();
        $product->setDescription1('bags');
        $unitinv = new Unit();
        $unitinvm = new UnitOfMeasurement();
        $unitinv->setNumber('100');
        $unitinv->setValue('1');
        $unitinvm->setCode(UnitOfMeasurement::PROD_PIECES);
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
        $notificatin = new Notification();
        $notificatin->setNotificationCode(Notification::CODE_QV_SHIP);
        $emailMessage = new EmailMessage();
        $emailaddress = array('1067197739@qq.com');
        $emailMessage->setEmailAddresses($emailaddress);
        $notificatin->setEmailMessage($emailMessage);

        return $notificatin;
    }

    private  function addPackages()
    {
        // Add Package--包裹，必须
        $package = new Package();
        $package->getPackagingType()->setCode(PackagingType::PT_PACKAGE);
        $package->getPackageWeight()->setWeight(10);
        $unit = new UnitOfMeasurement;
        $unit->setCode(UnitOfMeasurement::UOM_KGS);
        $package->getPackageWeight()->setUnitOfMeasurement($unit);

        // Set dimensions--箱规，不是必须的，可以不填
        $dimensions = new Dimensions();
        $dimensions->setHeight(50);
        $dimensions->setWidth(50);
        $dimensions->setLength(50);
        $unit = new UnitOfMeasurement;
        $unit->setCode(UnitOfMeasurement::UOM_CM);
        $dimensions->setUnitOfMeasurement($unit);
        $package->setDimensions($dimensions);

        return $package;
    }


}