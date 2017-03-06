<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Core extends CI_Controller {

     public $curl_headers;
     public $curl_options;
     public $curl_output;
     public $curl_errorCode;
     public $curl_errorMessage;
     public $curl_OutputHeader;
     public $BaseUrl;
     public $Url;
     public $connection;


     public function __construct(){
         $this->curl_headers = array(
                                     'Fineract-Platform-TenantID:default'
                                 );
         $this->BaseUrl ="https://192.168.0.50/fineract-provider/api/v1/";
         $this->curl_options = array(
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

             //Accept Self signed certificates
             CURLOPT_SSL_VERIFYPEER => false,
             CURLOPT_SSL_VERIFYHOST => false,
             CURLOPT_HTTPHEADER => $this->curl_headers
         );

     }

     public function index(){
         $client = $this->findClientByPhoneNumber('0707070707');
         $clientID =$client->entityId;
         $clientData = $this->getClientData($clientID);
         print_r($clientData);
     }

	public function queryServer()
	{

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




//        if((is_null($phoneNumber)) || (strlen($phoneNumber)<1)){
//            return;
//        }

        $this->connection = curl_init( $this->Url );
        curl_setopt_array( $this->connection, $this->curl_options );
        $this->curl_output = curl_exec( $this->connection );
        $this->curl_errorCode  = curl_errno( $this->connection );
        $this->curl_errorMessage  = curl_error( $this->connection );
        $this->curl_OutputHeader  = curl_getinfo( $this->connection );
        curl_close( $this->connection );

        return json_decode($this->curl_output);

	}

	public function findClientByPhoneNumber($phoneNumber){
        $this->Url =$this->BaseUrl."search?query=".$phoneNumber;
        $client = $this->queryServer();

        //if Client exists
        if(sizeof($client)>0){
            return $client[0];
        }

    }

    public function getClientData($ClientID){
        $this->Url =$this->BaseUrl."clients/".$ClientID;
        $clientData = $this->queryServer();

        //if Client Exists
        if(!array_key_exists('errors',$clientData)>0){
            return $clientData;
        }

    }
}
