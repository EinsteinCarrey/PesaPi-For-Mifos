<?php

/**
 * Created by PhpStorm.
 * User: EinsteinCarrey
 * Date: 3/23/2017
 * Time: 9:34 AM
 */
class LocalDBHandler extends CI_Model {

    function __construct(){
        parent::__construct();
    }

    public function getMpesaTransactions(){
        $this->db->select('receipt,round(amount/100) as amount,mifos_account_number,name,phonenumber,time,amount_posted');
        $this->db->from('pesapi_payment');
        $this->db->join('pesapi_payments_posted_to_mifos','pesapi_payments_posted_to_mifos.mpesa_recepit_no = pesapi_payment.receipt', 'left outer');
        $query = $this->db->get();
        return $query->result();
    }

    public function recordTransactionThatHaveBeenPostedToMifosDatabase($data){
        $project_media = array(
            'mpesa_recepit_no' => $data['receiptNumber'],
            'amount_posted' => $data['transactionAmount'],
            'mifos_account_number' => $data['accountNumber']
        );
        $this->db->insert('pesapi_payments_posted_to_mifos',$project_media);
    }


}