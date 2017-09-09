<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Core extends CI_Controller {


    function __construct(){
        parent::__construct();
        $this->load->model('LocalDBHandler');
    }

    public function receivePaymentViaMpesa(){

        load_this_controller('MpesaClientHandler');

        if(ENVIRONMENT == "production"){
            $data =  $this->MpesaClientHandler_->getPesaPiPostedData();
        }else{
            $data['receipt']= "test_receipt_".rand(10,100000);
            $data['type'] = 6;
            $data['time'] = time();
            $data['phoneNumber'] = '0707070707';
            $data['name'] = 'testAccount';
            $data['account'] = '';
            $data['amount'] = 100; //Convert Amount From cents To Shillings
            $data['postBalance'] = 5000; //Convert Balance From Cents To shillings
            $data['transactionCost'] = '0';
            $data['secret'] = 'FWv{VvB7#dSsJ(\S5_f)3C3S';
        }

        if($this->MpesaClientHandler_->dataHasErrors($data)) {
            //ToDo: Log error messages
            // $this->errorMessage;
            die();
        }

        # get clients ID
        $client = $this->MpesaClientHandler_->findClientByPhoneNumber($data['phoneNumber']);
        $data['clientID'] = $client->entityId;

        # check if client has an active loan
        $clientHasAnActiveLoan = $this->MpesaClientHandler_->clientHasActiveLoanAccount($data);

        # Deduct (Transaction charges) Ksh 5/= from the amount posted by client
        $transaction_charge = 5;
        $amount_posted = $data['amount'];
        $amount_after_deduction = ($amount_posted - $transaction_charge);
        $data['amount'] = $amount_after_deduction;

        if($clientHasAnActiveLoan){
            # Make repayments
            $outPut = $this->MpesaClientHandler_->makeRepaymentToALoanAccount($data);

        }else{
            # Deposit to savings account
            $outPut = $this->MpesaClientHandler_->makeDepositToClientSavingsAccount($data);

        }

        print_r($outPut);

        //Confirm That amount has been posted successfully
        if(array_key_exists('resourceId',$outPut['result'])){

            # record transaction in Mpesa register
            $this->LocalDBHandler->recordTransactionThatHaveBeenPostedToMifosDatabase($outPut['data']);

            # send a message to clients
            $data['messageBody'] = "Confirmed. Ksh " .
                $data['amount']+$transaction_charge . " has been received. to your Jambo Account.";
            $this->MpesaClientHandler_->sendMessageToClient($data);

            # Deposit Ksh 5/= to BulkSMS charges account
            $transaction_data = $data;
            $transaction_data['amount'] = 5;
            $transaction_data['clientID'] = 7879;
            $this->MpesaClientHandler_->makeDepositToClientSavingsAccount($transaction_data);
        }

    }

    public function index(){

        $data['mpesaTransactions'] = $this->LocalDBHandler->getMpesaTransactions();
        $this->load->view('MpesaTransactions', $data);
    }



}
