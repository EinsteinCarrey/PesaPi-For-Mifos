<?php
/**
 * @property resource connection
 */

class ApiGateway extends CI_Model{

    private $curl_headers = array();
    private $curl_options;
    private $curl;

    function __construct(){
        parent::__construct();
        $this->errorMessage = array();
    }

    function setHttpPostHeaders(){
        $this->curl_headers = array_merge($this->curl_headers, array(
                "X-HTTP-Method-Override: POST",
                'Content-Type: application/json'
            )
        );
    }
    function setGeneralHttpHeaders(){

        if(ENVIRONMENT == "production") {
            $authenticationString = "phone:kennyRodgers@JCI";
        }else{
            $authenticationString = "AccountForTesting:qwertyuiop";
        }

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
            CURLOPT_USERPWD        => $authenticationString,

            //Accept Self signed certificates
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $this->curl_headers
        );
    }

    function queryMifosServer($data){

        if(ENVIRONMENT == "production") {
            $mifosBaseUrl ="https://127.0.0.1/fineract-provider/api/v1/";
        }else{
            $mifosBaseUrl ="https://197.248.110.202/fineract-provider/api/v1/";
        }
        $data['Url'] = $mifosBaseUrl . $data['specificQueryUrl'];

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

        if(isset($data['isPostRequest']) && ($data['isPostRequest'])) {
            $this->setHttpPostHeaders();
        }
        $this->setGeneralHttpHeaders();

        $this->connection = curl_init( $data['Url'] );
        curl_setopt_array( $this->connection, $this->curl_options );

        // Check if this should be a post request
        // use GET method by default
        if(isset($data['isPostRequest']) && ($data['isPostRequest'])) {
            curl_setopt($this->connection, CURLOPT_POST, 1);
            curl_setopt($this->connection, CURLOPT_POSTFIELDS, $data['postBody']);
        }

        # make a curl request and capture output
        $this->curl['output'] = curl_exec( $this->connection );
        $this->curl['errorCode']  = curl_errno( $this->connection );
        $this->curl['errorMessage']  = curl_error( $this->connection );
        $this->curl['OutputHeader']  = curl_getinfo( $this->connection );
        curl_close( $this->connection );

        return json_decode($this->curl['output']);

    }

}