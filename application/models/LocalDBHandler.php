<?php

/**
 * Created by PhpStorm.
 * User: EinsteinCarrey
 * Date: 3/23/2017
 * Time: 9:34 AM
 */
class LocalDBHandler extends  CI_Model {

    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    public function recordTransactionPostedToMifosDatabase($data){

        $project_media = array(
            'mpesa_recepit_no' => $data->receiptNumber,
            'amount_posted' => $data->transactionAmount,
            'mifos_account_number' => $data->accountNumber
        );
        $this->db->insert('pesapi_payments_posted_to_mifos',$project_media);

    }


}