<?php
/**
 * Created by PhpStorm.
 * User: EinsteinCarrey
 * Date: 3/16/2017
 * Time: 5:00 PM
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class SMSCenter extends CI_Controller {


    private $uwaziiPassword ='IVPwWOgW';
    private $uwaziiUsername ='jambocapitalict';
    private $messageSender  = "JAMBO-CAP";

    function __construct(){
        parent::__construct();
        $this->load->model('ApiGateway');
    }



}
