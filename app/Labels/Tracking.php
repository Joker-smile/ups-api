<?php

namespace App\Tracking;

use  App\Labels\Tracking\Client;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Tracking
{

    private $uri;
    private $accessKey;
    private $userId;
    private $password;

    public function __construct()
    {
        $this->uri = 'https://onlinetools.ups.com/rest/Track';
        $this->accessKey = config('ups.accessKey');
        $this->userId = config('ups.userId');
        $this->password = config('ups.password');
    }

    public function track(array $track_numbers)
    {
        $track_data = array(
            'UPSSecurity' => array(
                'UsernameToken' => array(
                    'Username' => $this->userId,
                    'Password' => $this->password
                ),
                'ServiceAccessToken' => array(
                    'AccessLicenseNumber' => $this->accessKey
                )
            ),
            'TrackRequest' => array(
                'Request' => array(
                    'RequestOption' => 'activity',
                    'TransactionReference' => array(
                        'CustomerContext' => 'Test 001'
                    )
                ),
                'InquiryNumber' => '',
            )
        );
        $total = count($track_numbers);
        $uri = $this->uri;
        $requests = function ($total) use ($uri, $track_data, $track_numbers)
        {
            $header = array('Content-Type' => 'application/json');
            for ($i = 0; $i < $total; $i++) {
                $track_data['TrackRequest']['InquiryNumber'] = $track_numbers[$i];
                yield new Request('POST', $uri, $header, json_encode($track_data));
            }
        };

        $results = [];
        $successCallback = function ($response, $index)use(&$results)
        {
            $trackinfo = json_decode($response->getBody()->getContents(), true);
            if (!empty($trackinfo['Fault'])) {
                throw new HttpException(400, $trackinfo['Fault']['detail']['Errors']['ErrorDetail']['PrimaryErrorCode']['Description']);
            }
            $trackinfo = $trackinfo['TrackResponse']['Shipment'];
            $newdata = $this->dealData($trackinfo);
            array_push($results, $newdata);
        };

        $rejectCallback = function($reason, $index)
        {
            throw new HttpException(400, $reason);
        };

        $newClient = new Client();
        $newClient->pool($requests($total), $successCallback, $rejectCallback);
        
        return $results;
    }

    //数据处理
    private function dealData($trackinfo)
    {
        $detail = array();
        foreach ($trackinfo['Package']['Activity'] as $act) {
            $newtime = date('Y-m-d h:i:s', strtotime($act['Date'] . $act['Time']));
            $perAct = implode(array(
                $newtime,
                empty($act['ActivityLocation']['Address']['City']) ? '' : $act['ActivityLocation']['Address']['City'],
                $act['Status']['Description']), '-');
            array_push($detail, $perAct);
        }

        $startarray = end($trackinfo['Package']['Activity']);
        $lastarray = $trackinfo['Package']['Activity'][0];
        $duration = strtotime($lastarray['Date'] . $lastarray['Time']) - strtotime($startarray['Date'] . $startarray['Time']);

        $newdata = [
            'tracking_number' => $trackinfo['Package']['TrackingNumber'],
            'details' => array_reverse($detail),
            'latest_status' => $lastarray['Status']['Description'],
            'duration' => round($duration / 86400),
            'shipping_method' => 'UPS',
        ];

        return $newdata;
    }

}
