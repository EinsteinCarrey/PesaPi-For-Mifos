<?php
/**
 * Created by PhpStorm.
 * User: EinsteinCarrey
 * Date: 3/16/2017
 * Time: 5:00 PM
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class SMSCenter extends Core {


    private $uwaziiPassword ='IVPwWOgW';
    private $uwaziiUsername ='jamboca pitalict';

    function __construct(){
        parent::__construct();
        $this->load->model('ApiGateway');
    }


    function sendMessageToClient($data = null){

        $data['messageSender'] = "Jambo Cap";
        $data['messageBody'] = "Testing message from Einstein";
        $data['destinationPhoneNumber'] = "254707842711";

        $jsonData = array(
            "authentication"=> array(
                "username" => $this->uwaziiUsername,
                "password" => $this->uwaziiPassword
            ),
            "messages" => array(
                array(
                    "sender" => $data['messageSender'],
                    "text" => $data['messageBody'],
                    "recipients" => array(
                        array(
                            "gsm" => $data['destinationPhoneNumber']
                        )
                    )
                )
            )
        );

        $data['postBody'] = json_encode($jsonData);
        $data['isPostRequest'] = true;

        $outPut = $this->ApiGateway->queryUwaziiSmsServer($data);
        print_r($outPut);
    }


}
