<?php

namespace Fkra\EgyptExpress\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
//use Monolog\Logger;
//use Monolog\Handler\StreamHandler;

class Egyptexpress extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'egyptexpress';
    protected $_customLogger;
    protected $curlService;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Checkout\Model\Cart $cartModel,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Fkra\EgyptExpress\Logger\Logger $customLogger,
        \Fkra\EgyptExpress\Service\CurlService $curlService,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_cart = $cartModel;
        $this->_checkoutSession = $checkoutSession;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_customLogger = $customLogger;
        $this->curlService = $curlService;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return ['egyptexpress' => $this->getConfigData('name')];
    }

    /**
     * @param RateRequest $request
     * @return bool|Result
     */
    public function collectRates(RateRequest $request)
    {

        $price = 0;

        if (!$this->getConfigFlag('active')) {
            return false;
        }



        $data = $this->getFormData();

        $weight = $data['weight'];
        $this->_customLogger->info('weight :' .$weight);

        $originCityId = $this->getConfigData('origincity');
        $this->_customLogger->info('originCity :' .$originCityId);

        $destinationCity = $this->_checkoutSession->getQuote()->getShippingAddress()->getRegion();
        $this->_customLogger->info('destinationCity :' .$destinationCity);

        $dcityId = $this->getCityIdByName($destinationCity);
        $this->_customLogger->info('dcityId :' .$dcityId);
        

        $response = $this->curlService->postHttpRequest("https://api.egyptexpress.me/api/shippingCalculator",
            array("source"=> $originCityId ,"destination"=> $dcityId,"weight_unit"=>"2","weight"=>$weight)
        );

        $response_decode = json_decode($response);
        if ($response_decode->response_code == 200)
        {
            $price = $response_decode->total_price;
            // $price = $price * .25;

        }

        else
            $price = 0;

        $this->_customLogger->info('response_code :' .$response_decode->response_code);
       // $this->_customLogger->info('response_price :' .$response_decode->total_price);



        $result = $this->_rateResultFactory->create();

        $method = $this->_rateMethodFactory->create();

        $method->setCarrier('egyptexpress');
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod('egyptexpress');
        $method->setMethodTitle($this->getConfigData('name'));

        $method->setPrice($price);
        $method->setCost($price);

        $result->append($method);

        return $result;

    }

    public function getFormData()
    {
        $items = $this->_cart->getQuote()->getAllItems();

        $weight = 0;
        $qty = 0;
        foreach($items as $item) {
            $weight += ($item->getWeight() * $item->getQty()) ;
            $qty += $item->getQty();
        }

        $data['weight'] = $weight;
        $data['qty'] = $qty;

        return $data;
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
                return $city['id'];
            }
        }
    }
    
    public function getConfigDataSystem($name){
        return $this->getConfigData($name);
    }
}
