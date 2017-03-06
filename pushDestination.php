<?php

/*
$receipt = $_POST["receipt"];
$type = $_POST["type"];
$time = $_POST["time"];
$phoneNumber = $_POST["phonenumber"];
$name = $_POST["name"];
$account = $_POST["account"];
$amount = $_POST["amount"];
$postBalance = $_POST["postbalance"];
$transactionCost = $_POST["transactioncost"];
$secret = $_POST["secret"];
*/


$curl_headers = array(
    'Fineract-Platform-TenantID:default'
);

$options = array(
    CURLOPT_RETURNTRANSFER => true,     // return web page
    CURLOPT_HEADER         => false,    // don't return headers
    CURLOPT_FOLLOWLOCATION => true,     // follow redirects
    CURLOPT_ENCODING       => "",       // handle all encodings
    CURLOPT_USERAGENT      => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)", // who am i
    CURLOPT_AUTOREFERER    => true,     // set referer on redirect
    CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
    CURLOPT_TIMEOUT        => 120,      // timeout on response
    CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
    CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
    CURLOPT_USERPWD        => "administrator:058982.0",
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => $curl_headers
);


$BaseUrl ="https://192.168.0.50/fineract-provider";
$contentUrl ="/api/v1/search?query=0707842710";
$url =$BaseUrl.$contentUrl;


class PushDestination{

    public function searchClient($phoneNumber = null){
        if((is_null($phoneNumber)) || (strlen($phoneNumber)<1)){
            return;
        }

    }
}


$connection = curl_init( $url );
curl_setopt_array( $connection, $options );
$content = curl_exec( $connection );
$error     = curl_errno( $connection );
$errorMessage  = curl_error( $connection );
$header  = curl_getinfo( $connection );
curl_close( $connection );

$header['errorNumber']   = $error;
$header['errorMessage']  = $errorMessage;
$header['content'] = $content;

print_r($header['content']);
