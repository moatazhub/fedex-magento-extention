<?php

namespace Fkra\EgyptExpress\Observer;

use Magento\Framework\Event\ObserverInterface;

class ProcessShipment implements ObserverInterface
{

    protected $messageManager;
    protected $_responseFactory;
    protected $_customLogger;
    protected $_url;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        \Fkra\EgyptExpress\Logger\Logger $customLogger,
        \Magento\Framework\UrlInterface $url
    )
    {
        $this->messageManager = $messageManager;
        $this->_responseFactory = $responseFactory;
        $this->_customLogger = $customLogger;
        $this->_url = $url;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $egexpress = $objectManager->get("\Fkra\EgyptExpress\Model\Carrier\Egyptexpress");

        try{

            $shipment  = $observer->getEvent()->getShipment();
            $order     = $shipment->getOrder();

            $orderData = $order->getData();
            $awpData['accountNo'] = $egexpress->getConfigDataSystem('accountno');
            $awpData['password']  = md5($egexpress->getConfigDataSystem('spassword'));
            $security_key = $egexpress->getConfigDataSystem('securitykey');
            /////
            $this->_customLogger->info('accountNo :' .$awpData['accountNo']);
            $this->_customLogger->info('password :' .$awpData['password']);
            $this->_customLogger->info('security_key :' .$security_key);

            // Shipper Data
            $awpData['shipper_name']         = $egexpress->getConfigDataSystem('shippername');
            $awpData['shipper_phone']        = $egexpress->getConfigDataSystem('shipperphone');
            $awpData['shipper_city']         = $egexpress->getConfigDataSystem('shippercity');
            $awpData['shipper_address1']     = $egexpress->getConfigDataSystem('shipperaddress');

            $shipping_data = $order->getShippingAddress()->getData();

            // Reciepient Data
            $awpData['recipient_name']       = $shipping_data['firstname']." ".$shipping_data['lastname'];
            $awpData['recipient_phone']      = $shipping_data['telephone'];
            ///
            $awpData['recipient_city']  = $this->getCityIdByName($shipping_data['region']);
            $this->_customLogger->info('recipient_city :' .$awpData['recipient_city']);
            if($awpData['recipient_city']){
                $awpData['recipient_address1']   = $shipping_data['street'] . " " .$shipping_data['city'] . " " . $shipping_data['region'];
                $payment = $order->getPayment();
                $method = $payment->getMethod();
                if($method == "cashondelivery"){
                    $method = "COD";
                }

                $awpData['payment_method']       = "COD";//   $method;
                $awpData['COD_amount']           = $orderData['grand_total'];

                $orderItems = $order->getAllItems();
                $qty = 0;
                $width = 0;
                $height = 0;
                $length = 0;
                foreach($orderItems as $item){
                    $qty += $item->getQty();
                    $_product = $objectManager->get('Magento\Catalog\Model\Product')->load($item->getProduct()->getId());
                    $width += $_product->getWidth();
                    $height += $_product->getHeight();
                    $length += $_product->getLength();
                }
                if($width === 0 && $height === 0 && $length === 0){
                    $dimensions = "1*1*1";
                }else{
                    $dimensions = "$width*$height*$length";
                }

                $awpData['no_of_pieces'] = $qty;

                $orderData = $order->getData();

                $awpData['weight'] = $orderData['weight'];

                $awpData['dimensions']           = $dimensions;
                $awpData['goods_description']    = $egexpress->getConfigDataSystem('goodsdescription');
                $awpData['goods_origin_country'] = $egexpress->getConfigDataSystem('goodsorigincountry');

                $keyPart1 = $qty.$awpData['weight'].$awpData['dimensions'];

                $keyPart2 = strrev(md5($security_key));

                $awpData['hashkey'] = trim(sha1($keyPart1 . $keyPart2));
                //$this->_customLogger->info('awpData :' .$awpData);

                $server_output = $this->httpPost("https://api.egyptexpress.me/api/AWBcreate", $awpData);
                $this->_customLogger->info('server_output :' .$server_output);


                // Send Curl request
                /*
				$url = "https://api.egyptexpress.me/api/".'AWBcreate';
				$ch = curl_init();

				curl_setopt($ch, CURLOPT_URL,$url);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,$awpData);

				// Receive server response ...
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				$server_output = curl_exec($ch);

				curl_close ($ch);
               */
                $this->addTrackingData($shipment,$server_output);
            }else{
                $this->messageManager->addError("City is not Valid. Unable to Create Tracking Information ");
                return false;
            }
        }catch(\Exception $ex){
            $this->messageManager->addError($ex->getMessage());
        }
    }

    public function addTrackingData($shipmentData,$data){
        $dc = json_decode($data);
        $sn = $dc->SN;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        if($sn){
            try{
                $track = $objectManager->create('Magento\Sales\Model\Order\Shipment\Track')
                    ->setShipment($shipmentData)
                    ->setTitle("EgyptExpress")
                    ->setNumber($sn)
                    ->setCarrierCode("custom")
                    ->setOrderId($shipmentData->getData('order_id'))
                    ->save();
            }catch(\Exception $ex){
                $this->messageManager->addError($ex->getMessage());
            }
        }
    }

    public function getCityIdByName($cityName){


        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );

        $result = file_get_contents("https://api.egyptexpress.me/api/shippingCities", false, stream_context_create($arrContextOptions));


        $cities = json_decode($result, true);
        $cityList = $cities['cities'];
        $citiesArray = array();
        foreach($cityList as $city){

            $name = $city['city_en'];
            if($name == $cityName){
                return $city['code'];
            }
        }
    }

    public function httpPost($url, $data){

        foreach ($data as $key=>$value)
            $this->_customLogger->info($key.":".$value);


        //$this->_customLogger->info('data :' .$data['weight']);
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ),
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            )
        );
        $context  = stream_context_create($options);
        return file_get_contents($url, false, $context);
    }


    /*
    public function getCityIdByName($cityName){

        $url = "http://82.129.197.84:8080/api/shippingCities";
        $result = file_get_contents($url);
        $cities = json_decode($result, true);
        $cityList = $cities['cities'];
        $citiesArray = array();
        foreach($cityList as $city){
            $name = $city['city_en'];
            if($name == $cityName){
                return $city['code'];
            }
        }
    }
    */
}
