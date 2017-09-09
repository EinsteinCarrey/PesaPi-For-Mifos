<?php

/**
 * Created by PhpStorm.
 * User: EinsteinCarrey
 * Date: 3/20/2017
 * Time: 12:14 PM
 */

class MpesaClientHandler extends CI_Controller {


    private $secret ='FWv{VvB7#dSsJ(\S5_f)3C3S';
    private $ClientAccounts;
    private $errorMessage;

    private $uwaziiPassword ='IVPwWOgW';
    private $uwaziiUsername ='jambocapitalict';
    private $messageSender  = "JAMBO-CAP";

    function __construct(){
        parent::__construct();
        $this->load->model('ApiGateway');
        $this->load->model('LocalDBHandler');
    }

    function getPesaPiPostedData(){
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
        $data['specificQueryUrl'] = "search?query=".$phoneNumber;
        $client = $this->ApiGateway->queryMifosServer($data);

        //check if Client exists
        if(sizeof($client)<1){
            print_r("There is no recorded client with phone number ". $phoneNumber );
            die();
        }

        return $client[0];

    }

    function getClientData($ClientID){
        $data['specificQueryUrl'] ="clients/".$ClientID;
        $clientData = $this->ApiGateway->queryMifosServer($data);

        //if Client Exists
        if(!array_key_exists('errors',$clientData)){
            return $clientData;
        }

    }

    function getClientAccounts($ClientID){
        $data['specificQueryUrl'] ="clients/".$ClientID."/accounts";
        $clientAccounts = $this->ApiGateway->queryMifosServer($data);

        //if Client Exists
        if(!array_key_exists('errors',$clientAccounts)){
            $this->ClientAccounts=$clientAccounts;
        }else{
            $this->ClientAccounts=null;
        }

    }

    function clientHasActiveLoanAccount($data){
        $this->getClientAccounts($data['clientID']);

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

    function getClientActiveSavingsAccounts($clientID=null){

        if(!is_null($clientID)){
            $this->getClientAccounts($clientID);
        }
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

    function getDateFromEpochSecondsTimestamp($epochTimestamp){

        $months = explode(
            " ",
            "January February March April May June July August September October November December"
        );

        $time =  date("d", $epochTimestamp)." "; //transaction Date
        $time .=  $months[date("m", $epochTimestamp)-1]." "; //append moths short Name
        $time .=  date("Y", $epochTimestamp); // append year

        return $time;
    }

    function makeRepaymentToALoanAccount($data)
    {

        $this->load->model('LocalDBHandler');
        $clientsLoanAccounts = $this->getClientActiveLoanAccounts($data['clientID'] );
        $firstLoanAccountID = ($clientsLoanAccounts[0]->id);
        $firstLoanAccountNumber= ($clientsLoanAccounts[0]->accountNo);

        $data['specificQueryUrl'] =  "loans/" . $firstLoanAccountID . "/transactions?command=repayment";
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
        $data['isPostRequest'] = true;
        $data['postBody'] = $postBody;
        $outPut['result'] = $this->ApiGateway->queryMifosServer($data);
        $outPut['data'] = $jsonPostBody;
        $outPut['new_balance'] = ($clientsLoanAccounts[0]->loanBalance - $data["amount"]);
        return $outPut;
    }

    function makeDepositToClientSavingsAccount($data)
    {
        $clientSavingsAccounts = $this->getClientActiveSavingsAccounts($data['clientID'] );
        $firstClientSavingsAccountID = ($clientSavingsAccounts[0]->id);
        $firstClientSavingsAccountNumber = ($clientSavingsAccounts[0]->accountNo);

        $data['specificQueryUrl'] =  "savingsaccounts/" . $firstClientSavingsAccountID . "/transactions?command=deposit";
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
        $data['isPostRequest'] = true;
        $data['postBody'] = $postBody;
        $outPut['result'] = $this->ApiGateway->queryMifosServer($data);
        $outPut['data'] = $jsonPostBody;
        $outPut['new_balance'] = $clientSavingsAccounts[0]->accountBalance + $data["amount"];
        return $outPut;
    }


    function sendMessageToClient($data = null){

        if(ENVIRONMENT != 'production') {
            $data['phoneNumber'] = "254707842711";
        }

        $jsonData = array(
            "authentication"=> array(
                "username" => $this->uwaziiUsername,
                "password" => $this->uwaziiPassword
            ),
            "messages" => array(
                array(
                    "sender" => $this->messageSender,
                    "text" => $data['messageBody'],
                    "recipients" => array(
                        array(
                            "gsm" => $data['phoneNumber']
                        )
                    )
                )
            )
        );

        $data['postBody'] = json_encode($jsonData);
        $data['isPostRequest'] = true;

        $outPut = $this->ApiGateway->queryUwaziiSmsServer( $data );
        print_r($outPut);
    }

}