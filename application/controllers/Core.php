<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Core extends CI_Controller {

     private $errorMessage;
     private $curl_headers;
     private $curl_options;
     private $curl_output;
     private $curl_errorCode;
     private $curl_errorMessage;
     private $curl_OutputHeader;
     private $BaseUrl;
     private $Url;
     private $connection;
     private $ClientAccounts;
     private $secret ='FWv{VvB7#dSsJ(\S5_f)3C3S';

    //List of account to where the amount will be deposited
    //Ordered by priority level
    private $priorityList = array('loanAccounts','savingsAccounts');

    function __construct(){
         parent::__construct();
         $this->curl_headers = array(
                                     'Fineract-Platform-TenantID:default',
                                     "X-HTTP-Method-Override: POST",
                                     'Content-Type: application/json'
                                 );

         //ToDo(Einstein): check This Before Deploying
         $this->BaseUrl ="https://192.168.0.50/fineract-provider/api/v1/";
         $this->curl_options = array(
             CURLOPT_RETURNTRANSFER => true,     // return web page
             CURLOPT_HEADER         => false,    // don't return headers
             CURLOPT_FOLLOWLOCATION => true,     // follow redirects
             CURLOPT_ENCODING       => "",       // handle all encodings
             CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:51.0) Gecko/20100101 Firefox/51.0", // who am i
             CURLOPT_AUTOREFERER    => true,     // set referer on redirect
             CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
             CURLOPT_TIMEOUT        => 120,      // timeout on response
             CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
             CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
             //ToDo(Einstein): check This Before Deploying
             CURLOPT_USERPWD        => "phone:kennyRodgers@JCI",

             //Accept Self signed certificates
             CURLOPT_SSL_VERIFYPEER => false,
             CURLOPT_SSL_VERIFYHOST => false,
             CURLOPT_HTTPHEADER => $this->curl_headers
         );
         $this->errorMessage = array();;

     }

    public function index(){
         $data =  $this->getPostedData();
        $client = $this->findClientByPhoneNumber($data['phoneNumber']);
        $data['clientID'] = $client->entityId;
        $this->ClientAccounts = $this->getClientAccounts($data['clientID']);

        if($this->clientHasActiveLoanAccount()){
            $result = $this->makeRepaymentToALoanAccount($data);
        }else{
            $result = $this->makeDepositToClientSavingsAccount($data);
        }

        print_r($result);
        return;
     }

    function getPostedData(){
         $data = null;

         $data['receipt']= $_POST["receipt"];
         $data['type'] = $_POST["type"];
         $data['time'] = $_POST["time"];
         $data['phoneNumber'] = $_POST["phonenumber"];
         $data['name'] = $_POST["name"];
         $data['account'] = $_POST["account"];
         $data['amount'] = ($_POST["amount"]/100); //Convert Amount From cents To Shillings
         $data['postBalance'] = ($_POST["postbalance"]/100); //Convert Balance From Cents To shillings
         $data['transactionCost'] = $_POST["transactioncost"];
         $data['secret'] = $_POST["secret"];

         if($this->dataHasErrors($data)) {
             print_r($this->errorMessage);
             die();
         }
         return $data;
     }

    function dataHasErrors($data){

         //check If Phone Number Has Been Provided
         if((is_null($data['phoneNumber'])) || (strlen($data['phoneNumber'])==0)) {
             array_push($this->errorMessage,"Phone Number Has Not been provided ");
         }

         //check If Phone Number Provided is kenyan && is correcct
         if(! (substr($data['phoneNumber'],0,3) == '254') && (strlen($data['phoneNumber'])==12)) {
             array_push($this->errorMessage,"Phone Number provided is not valid ");
         }

         //check if secret provided is Authentic
         if($data['secret'] != $this->secret) {
             array_push($this->errorMessage,"Secret Key Provided is not Authentic");
         }

         return sizeof($this->errorMessage) > 0;
     }

    function findClientByPhoneNumber($phoneNumber){
        $phoneNumber= substr($phoneNumber,3);
        $this->Url =$this->BaseUrl."search?query=".$phoneNumber;
        $client = $this->queryMifosServer();

        //check if Client exists
        if(sizeof($client)<1){
            print_r("There is no recorded client with phone number ". $phoneNumber );
            die();
        }

        return $client[0];

    }

    function getClientData($ClientID){
        $this->Url =$this->BaseUrl."clients/".$ClientID;
        $clientData = $this->queryMifosServer();

        //if Client Exists
        if(!array_key_exists('errors',$clientData)){
            return $clientData;
        }

    }

    function getClientAccounts($ClientID){
        $this->Url =$this->BaseUrl."clients/".$ClientID."/accounts";
        $clientAccounts = $this->queryMifosServer();

        //if Client Exists
        if(!array_key_exists('errors',$clientAccounts)){
            return $clientAccounts;
        }

    }

    function clientHasActiveLoanAccount(){
            $clientHasAnActiveLoanAccount = false;
            if(array_key_exists('loanAccounts',$this->ClientAccounts)){
                $loanAccounts = $this->ClientAccounts->loanAccounts;
                foreach ($loanAccounts as $loanAccount){
                    if($loanAccount->status->value == 'Active'){
                        $clientHasAnActiveLoanAccount = true;
                    }
                }
            }

            return $clientHasAnActiveLoanAccount;
    }

    function getClientActiveSavingsAccounts(){
        //if Client Has Savings Account(s)
        if(array_key_exists('savingsAccounts',$this->ClientAccounts)){

            $ActiveSavingsAccounts=array();

            $savingsAccounts = $this->ClientAccounts->savingsAccounts;
            foreach ($savingsAccounts as $savingsAccount){
                if($savingsAccount->status->value == 'Active'){
                    array_push($ActiveSavingsAccounts,$savingsAccount);
                }
            }

            return $ActiveSavingsAccounts;
        }

    }

    function getClientActiveLoanAccounts(){
        //if Client Has Savings Account(s)
        if(array_key_exists('loanAccounts',$this->ClientAccounts)){

            $ActiveLoanAccounts=array();

            $loanAccounts = $this->ClientAccounts->loanAccounts;
            foreach ($loanAccounts as $loanAccount){
                if($loanAccount->status->value == 'Active'){
                    array_push($ActiveLoanAccounts,$loanAccount);
                }
            }

            return $ActiveLoanAccounts;
        }

    }

    function makeRepaymentToALoanAccount($data)
    {

        $clientsLoanAccounts = $this->getClientActiveLoanAccounts($data['clientID'] );

        $firstLoanAccountID = ($clientsLoanAccounts[0]->id);
        $firstLoanAccountNumber= ($clientsLoanAccounts[0]->accountNo);

        $this->Url = $this->BaseUrl . "loans/" . $firstLoanAccountID . "/transactions?command=repayment";
        $jsonPostBody = array(
            "locale" => "en",
            "dateFormat" => "dd MMMM yyyy",
            "transactionDate" => $this->getDateFromEpochSecondsTimestamp($data['time']),
            "transactionAmount" => $data["amount"],
            "paymentTypeId" => "6",
            "accountNumber" => $firstLoanAccountNumber,
            "checkNumber" => "",
            "routingCode" => "",
            "receiptNumber" => $data["receipt"],
            "bankNumber" => ""
        );

        $postBody = json_encode($jsonPostBody);
        $outPut = $this->queryMifosServer(true, $postBody);
        return $outPut;
    }

    function makeDepositToClientSavingsAccount($data)
    {
        $clientSavingsAccounts = $this->getClientActiveSavingsAccounts($data['clientID'] );
        $firstClientSavingsAccountID = ($clientSavingsAccounts[0]->id);
        $firstClientSavingsAccountNumber = ($clientSavingsAccounts[0]->accountNo);

        $this->Url = $this->BaseUrl . "savingsaccounts/" . $firstClientSavingsAccountID . "/transactions?command=deposit";
        $jsonPostBody = array(
            "locale" => "en",
            "dateFormat" => "dd MMMM yyyy",
            "transactionDate" => $this->getDateFromEpochSecondsTimestamp($data['time']),
            "transactionAmount" => $data["amount"],
            "paymentTypeId" => "6",
            "accountNumber" => $firstClientSavingsAccountNumber,
            "checkNumber" => "",
            "routingCode" => "",
            "receiptNumber" => $data["receipt"],
            "bankNumber" => ""
        );
        $postBody = json_encode($jsonPostBody);
        $outPut = $this->queryMifosServer(true, $postBody);
        return $outPut;
    }

    function getDateFromEpochSecondsTimestamp($epochTimestamp){

        $months = explode(" ","Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec");

        $time =  date("d", $epochTimestamp)." "; //transaction Date
        $time .=  $months[date("m", $epochTimestamp)-1]." "; //append moths short Name
        $time .=  date("Y", $epochTimestamp); // append year

        return $time;
    }

    function queryMifosServer($isPostRequest=false, $postBody = null)
    {

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
    
}
