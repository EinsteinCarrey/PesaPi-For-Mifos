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
            // ToDo: Log error messages
            // $this->errorMessage;
            die();
        }

        # get clients ID
        $client = $this->MpesaClientHandler_->findClientByPhoneNumber($data['phoneNumber']);
        $data['clientID'] = $client->entityId;

        # check if client has an active loan
        $clientHasAnActiveLoan = $this->MpesaClientHandler_->clientHasActiveLoanAccount($data);

        # check if this account is a staff account
        $accountBelongsToStaff = $this->MpesaClientHandler_->accountBelongsToStaff($data);

        $transaction_charge = 0;
        if(!$accountBelongsToStaff) {

            # Deduct (Transaction charges) Ksh 5/= from the amount posted by client
            $transaction_charge = 5;
            $amount_posted = $data['amount'];
            $amount_after_deduction = ($amount_posted - $transaction_charge);
            $data['amount'] = $amount_after_deduction;
        }

        if($clientHasAnActiveLoan){
            # Make repayments
            $outPut = $this->MpesaClientHandler_->makeRepaymentToALoanAccount($data);
            $account_type = "loan";

        }else{

            # Deposit to savings account
            $outPut = $this->MpesaClientHandler_->makeDepositToClientSavingsAccount($data);
            $account_type = "savings";

        }


        # Confirm mpesa payment has been processed and posted successfully
        if(array_key_exists('resourceId',$outPut['result'])){

            # Approve transaction to savings account
            $this->MpesaClientHandler_->approveTransaction($outPut);


            # record transaction in Mpesa register
            $this->LocalDBHandler->recordTransactionThatHaveBeenPostedToMifosDatabase($outPut['data']);

            # send a message to clients
            $sms_feedback_message = "Confirmed. Ksh ";
            $sms_feedback_message.= $data['amount']+$transaction_charge;
            $sms_feedback_message.= " has been received. \n";
            $sms_feedback_message.= "Your new ".$account_type . " balance is Ksh.".$outPut['new_balance']."/=\n\n";
            $data['messageBody'] = $sms_feedback_message;
            $this->MpesaClientHandler_->sendMessageToClient($data);

            if(!$accountBelongsToStaff) {

                # Deposit Ksh 5/= to BulkSMS charges account
                $transaction_data = $data;
                $transaction_data['amount'] = 5;
                $transaction_data['clientID'] = 7879;
                $bulk_sms_charge = $this->MpesaClientHandler_->makeDepositToClientSavingsAccount($transaction_data);

                # Approve transaction to BulkSMS charges account
                $this->MpesaClientHandler_->approveTransaction($bulk_sms_charge);

            }
        }

    }

    public function index(){

        $data['mpesaTransactions'] = $this->LocalDBHandler->getMpesaTransactions();
        $this->load->view('MpesaTransactions', $data);
    }



}
