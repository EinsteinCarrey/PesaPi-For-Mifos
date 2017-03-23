<?php
/**
 * Created by PhpStorm.
 * User: EinsteinCarrey
 * Date: 3/20/2017
 * Time: 10:12 AM
 */

class ApiGateway extends CI_Model{

    private $BaseUrl;
    private $curl_headers = array();
    private $curl_options;
    private $curl;
    private $errorMessage;

    function __construct(){
        parent::__construct();
        $this->errorMessage = array();
    }

    function setHttpHeaders(){

        array_merge($this->curl_headers, array(
                "X-HTTP-Method-Override: POST",
                'Content-Type: application/json'
            )
        );

        $this->curl_options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:51.0) Gecko/20100101 Firefox/51.0", // who am i
            CURLOPT_AUTOREFERER    => true,     // set referrer on redirect
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
    }

    function queryMifosServer($data){
        $mifosBaseUrl ="https://192.168.0.50/fineract-provider/api/v1/";
        $data['Url'] = $mifosBaseUrl . $data['specificQueryUrl'];

        if($this->environment == "production"){
            $this->BaseUrl ="https://127.0.0.1/fineract-provider/api/v1/";
        }
        $this->curl_headers = array(
            'Fineract-Platform-TenantID:default'
        );

        return $this->queryApiServer($data);
    }

    function queryUwaziiSmsServer($data=null){
        $data['Url'] = "http://107.20.199.106/api/v3/sendsms/json";
        return $this->queryApiServer($data);
    }

    function queryApiServer($data){

        $this->setHttpHeaders();

        $this->connection = curl_init( $data['Url'] );
        curl_setopt_array( $this->connection, $this->curl_options );

        //if this is a post request /*default is GET*/
        if(isset($data['isPostRequest']) && ($data['isPostRequest'])) {
            curl_setopt($this->connection, CURLOPT_POST, 1);
            curl_setopt($this->connection, CURLOPT_POSTFIELDS, $data['postBody']);
        }

        $this->curl['output'] = curl_exec( $this->connection );
        $this->curl['errorCode']  = curl_errno( $this->connection );
        $this->curl['errorMessage']  = curl_error( $this->connection );
        $this->curl['OutputHeader']  = curl_getinfo( $this->connection );
        curl_close( $this->connection );

        return json_decode($this->curl['output']);

    }

}