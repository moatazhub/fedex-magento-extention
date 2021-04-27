<?php
namespace Fkra\EgyptExpress\Controller\Index;
ini_set('display_errors','1');
class Awpprint extends \Magento\Framework\App\Action\Action
{

    protected $_customLogger;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Block\Adminhtml\Order\View\Info $Orderinfo,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Sales\Model\Order\Shipment $shipment,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Fkra\EgyptExpress\Logger\Logger $customLogger
    ){
        $this->Orderinfo = $Orderinfo;
        $this->shipment = $shipment;
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_customLogger = $customLogger;
        return parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->request->getParam('shipment_id');
        $this->_customLogger->info('id :' .$id);
        $shipment = $this->shipment->getCollection()->addFieldToFilter('entity_id',$id)->load();
        $shipment = $this->shipment->load($id);
        $tracks = $shipment->getTracksCollection();
        $sn = "";
        foreach($tracks as $track){
            if($track->getTitle() == "EgyptExpress"){
                $sn = $track->getTrackNumber();
            }
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $egexpress = $objectManager->get("\Fkra\EgyptExpress\Model\Carrier\Egyptexpress");

        $accountNo =  $egexpress->getConfigDataSystem('accountno');
        $password = md5($egexpress->getConfigDataSystem('spassword'));
        $security_key = $egexpress->getConfigDataSystem('securitykey');

        //encrypt security key
        $keyEncrypted = strrev(md5($security_key));
        //hash key
        $Hhash_key = trim(sha1($sn . $keyEncrypted));

        $postdata['accountNo'] =  $accountNo ;
        $postdata['password'] = $password;
        $postdata['SN'] = $sn;
        $postdata['hashkey'] = $Hhash_key;
        $this->_customLogger->info('postdata :' .$postdata['SN']);
       /*
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($postdata)
            ),
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            )
        );
        $context  = stream_context_create($options);
        $output = file_get_contents("http://82.129.197.84:8080/api/AWBhtml", false, $context);
        */
       // $output = $this->postHttpRequest("http://82.129.197.84:8080/api/AWBhtml", $postdata);
        //$this->_customLogger->info('$output :' .$output);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://api.egyptexpress.me/api/AWBhtml");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        $output = curl_exec($ch);
        
        $this->_customLogger->info('$output :' .$output);
        
        $info = curl_getinfo($ch);
        curl_close($ch);

        $res = json_decode($output);


        $result = $this->resultJsonFactory->create();
        $result->setData(['data' => $output]);



        return $result;

    }

    public function postHttpRequest($url, $data){

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
}