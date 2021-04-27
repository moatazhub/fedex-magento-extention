<?php

namespace Fkra\EgyptExpress\Service;

class CurlService{

    public function __construct()
    {
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
