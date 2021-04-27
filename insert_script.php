<?php
$servername = "localhost";
$username = "";
$password = "";
$dbname = "";


 $citiesArray = array();


$arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );

        $result = file_get_contents("https://api.egyptexpress.me/api/shippingCities", false, stream_context_create($arrContextOptions));

      // $i = 0;
      //  $url = "http://82.129.197.84:8080/api/shippingCities";
      //  $result = file_get_contents($url);
        $cities = json_decode($result, true);
        $cityList = $cities['cities'];
       
        foreach($cityList as $city){
          //  $i++;
            $code = $city['code'];
            $name = $city['city_en'];
            $cityArray = array(
                'value' => $code,
                'label' => $name
            );
            array_push($citiesArray,$cityArray);
        }

        //echo '<pre>';print_r($citiesArray);die;
       // echo $i;
        print_r($citiesArray) ;


// Create connection
  $conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
 if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
    }


  foreach($citiesArray as $city)
  
   {
       
    // if( $city['value'] != "ALX" )//|| $city['value'] != "CAI" || $city['value'] != "RAM"  || $city['value'] != "DHB" || $city['value'] != "DAM" ||  $city['value'] != "15May" || $city['value'] != "ABS"
        
     
     
    
       /*
         $city['value'] == 'CAI' || 
         $city['value'] == 'RAM' ||
         $city['value'] == 'DHB' || 
         $city['value'] == 'DAM' ||
         $city['value'] == '15May' ||
         $city['value'] == 'ABS' )
         */
     
      
   $code = $city['value'];
   $name = $city['label'];


$sql1 = "INSERT INTO mg_directory_country_region ( country_id, code,default_name)
        VALUES ( 'EG', '$code', '$name')";



if (mysqli_query($conn, $sql1)) {
    $last_id = mysqli_insert_id($conn);
    
    $sql2 = "INSERT INTO mg_directory_country_region_name ( locale, region_id,name)
        VALUES ( 'en_US', '$last_id' , '$name')";
        
        mysqli_query($conn, $sql2);
    
    
    echo "New record created successfully. Last inserted ID is: " . $last_id;
} else {
    echo "Error: " . $sql . "<br>" . mysqli_error($conn);
}




}

mysqli_close($conn);


?>