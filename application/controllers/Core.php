<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Core extends CI_Controller {

    //ToDo(Einstein): check This Before Deploying
     public $environment = "testing"; //production


    public function receivePaymentViaMpesa(){

        load_this_controller('MpesaClientHandler');
        $data =  $this->MpesaClientHandler_->getPesaPiPostedData();

        if($this->environment == "testing"){
            $data['phoneNumber'] = '0707070707';
        }

        $client = $this->MpesaClientHandler_->findClientByPhoneNumber($data['phoneNumber']);
        $data['clientID'] = $client->entityId;
        $this->ClientAccounts = $this->MpesaClientHandler_->getClientAccounts($data['clientID']);

        if($this->MpesaClientHandler_->clientHasActiveLoanAccount()){
            $result = $this->MpesaClientHandler_->makeRepaymentToALoanAccount($data);
        }else{
            $result = $this->MpesaClientHandler_->makeDepositToClientSavingsAccount($data);
        }

        $this->LocalDBHandler->recordTransactionPostedToMifosDatabase($result['transactionParameters']);

        return $result;
     }

    public function sendSmsMessageToClient($data=null){

        load_this_controller('SMSCenter');
        $this->SMSCenter_->sendMessageToClient($data);

     }

    public function index(){
    }

}
