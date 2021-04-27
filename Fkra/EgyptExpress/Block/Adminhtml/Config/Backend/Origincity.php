<?php
/**
 * @author Wtc Team
 * @copyright Copyright (c) 201
 * @package
 */
namespace Fkra\EgyptExpress\Block\Adminhtml\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;


class Origincity implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
      $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );

        $result = file_get_contents("https://api.egyptexpress.me/api/shippingCities", false, stream_context_create($arrContextOptions));


      //  $url = "http://82.129.197.84:8080/api/shippingCities";
      //  $result = file_get_contents($url);
        $cities = json_decode($result, true);
        $cityList = $cities['cities'];
        $citiesArray = array();
        foreach($cityList as $city){
            $code = $city['id'];
            $name = $city['city_en'];
            $cityArray = array(
                'value' => $code,
                'label' => $name
            );
            array_push($citiesArray,$cityArray);
        }

        //echo '<pre>';print_r($citiesArray);die;

        return $citiesArray;
    }

}