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
                                     'Fineract-Platform-TenantID:default',
                                     "X-HTTP-Method-Override: POST",
                                     'Content-Type: application/json'
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
         //$client = $this->findClientByPhoneNumber('0707842711');
         $client = $this->findClientByPhoneNumber('0707070707');
         $clientID = $client->entityId;
         $clientSavingsAccounts = $this->getClientActiveSavingsAccounts($clientID);
         $data['firstClientSavingsAccountID'] = ($clientSavingsAccounts[0]->id);
         $data['firstClientSavingsAccountNumber'] = ($clientSavingsAccounts[0]->accountNo);
         $data['amount'] = 3000;
         $this->makeDepositToClientSavingsAccount($data);
     }

	public function queryServer($isPostRequest=false, $postBody = null)
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

        //if this is a post request /*default is GET*/
        if($isPostRequest) {
            curl_setopt($this->connection, CURLOPT_POST, 1);
            curl_setopt($this->connection, CURLOPT_POSTFIELDS, $postBody);
        }

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

    function getClientData($ClientID){
        $this->Url =$this->BaseUrl."clients/".$ClientID;
        $clientData = $this->queryServer();

        //if Client Exists
        if(!array_key_exists('errors',$clientData)){
            return $clientData;
        }

    }

    function getClientAccounts($ClientID){
        $this->Url =$this->BaseUrl."clients/".$ClientID."/accounts";
        $clientAccounts = $this->queryServer();

        //if Client Exists
        if(!array_key_exists('errors',$clientAccounts)){
            return $clientAccounts;
        }

    }

    function getClientActiveSavingsAccounts($ClientID){
        $ClientAccounts = $this->getClientAccounts($ClientID);

        //if Client Has Savings Account(s)
        if(array_key_exists('savingsAccounts',$ClientAccounts)){

            $ActiveSavingsAccounts=array();

            $savingsAccounts = $ClientAccounts->savingsAccounts;
            foreach ($savingsAccounts as $savingsAccount){
                if($savingsAccount->status->value == 'Active'){
                    array_push($ActiveSavingsAccounts,$savingsAccount);
                }
            }

            return $ActiveSavingsAccounts;
        }

    }

    function makeDepositToClientSavingsAccount($data){


        $this->Url = $this->BaseUrl."savingsaccounts/".$data['firstClientSavingsAccountID']."/transactions?command=deposit";

        echo $this->Url."<br>";
        $jsonPostBody = array(
                "locale" => "en",
                "dateFormat" => "dd MMMM yyyy",
                "transactionDate" => "07 March 2017",
                "transactionAmount" => $data["amount"],
                "paymentTypeId" => "6",
                "accountNumber" => $data["firstClientSavingsAccountNumber"],
                "checkNumber" => "",
                "routingCode" => "",
                "receiptNumber" => "JCI000106032017",
                "bankNumber" => ""
        );
        $postBody=json_encode($jsonPostBody);
        $outPut = $this->queryServer(true,$postBody);

        print_r($postBody);
        print_r($outPut);
    }





}
