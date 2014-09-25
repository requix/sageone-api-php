<?php

/*
Library Name: SageOne-API-PHP
Description: A PHP Library to access the SageOne API
Version: 0.1
Author: Eddie Harrison

Copyright 2013  Eddie Harrison  (email:eddie@eddieharrison.co.uk)
*/

class SageOne {
    
    private $clientId;
    private $clientSecret;
    
    private $oAuthAuthoriseURL = 'https://app.sageone.com/oauth/authorize';
    private $oAuthAccessTokenURL = 'http://app.sageone.com/oauth/token';
    private $accessToken;
    
    private $apiUrl = 'https://app.sageone.com/api/v1';
    
    private $debug = true;
    
    function __construct($clientId, $clientSecret){
        
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }
    
    public function getAuthoriseURL($callback){
        
        $authoriseURL  = $this->oAuthAuthoriseURL;
        $authoriseURL .= '?response_type=code';
        $authoriseURL .= '&client_id='.$this->clientId;
        $authoriseURL .= '&redirect_uri='.urlencode($callback);
        
        return $authoriseURL;
    }
    
    public function getAccessToken($code, $callback){
        
        if($this->accessToken){
            return $this->accessToken;
        }
        
        $params = array(
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $callback,
            'scope' => 'full_access' // required to create contacts, invoices etc.
        );
        
        $result = $this->call('', 'oauth', $params);
        
        
        // Get array of accessToken
        $accessToken = json_decode($result, true);
        
        // Save accessToken
        $this->accessToken = $accessToken['accessToken'];
    
        // Return the response as an array
        return $accessToken;
    }
    
    public function setAccessToken($accessToken){
        $this->accessToken = $accessToken;
    }
    
    public function createContact($data){
        
        // Wrap params in array
        $data = array("contact" => $data);
        
        $result = $this->post('/contacts', $data);
        return $result;
    }
    
    public function createInvoice($params){
        
        // Wrap params in array
        $data = array("sales_invoice" => $params);
        
        $result = $this->post('/sales_invoices', $data);
        return $result;
    }
    
    public function createInvoicePayment($sales_invoice_id, $params){
        
        // Wrap params in array
        $data = array("sales_invoice_payment" => $params);
        
        $result = $this->post('/sales_invoices/'.$sales_invoice_id.'/payments', $data);
        return $result;
    }
    
    public function getTaxRates(){
        
        $result = $this->get('/tax_rates');
        return $result;
    }
    
    public function getLedgerAccounts(){
        
        $result = $this->get('/ledger_accounts');
        return $result;
    }
    
    public function getServices(){
        
        $result = $this->get('/services');
        return $result;
    }
    
    
    /**************************************************************************
    * Private functions
    **************************************************************************/
    
    private function call($endpoint, $type, $data=false){
        
        // To-do: Validate endpoints
        // To-do: Validate types
        
        // Data (if set) has to be array and then converted to json
        if($data){
            $data = json_encode($data);
        }
        
        $ch = curl_init();
        
        // Set curl url to call
        if($type == 'oauth'){
            $curlURL = $this->oAuthAccessTokenURL;
        } else {
            $curlURL = $this->apiUrl.$endpoint;
        }
        curl_setopt($ch, CURLOPT_URL, $curlURL);
        
        // Setup curl options
        $curl_options = array(
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_USERAGENT      => 'SageOne-API-PHP'
        );
        
        switch($type){
            case 'post':
                $curl_options = $curl_options + array(
                    CURLOPT_HTTPHEADER => array(
                        'Accept: application/json',
                        'Authorization: Bearer '.$this->accessToken,
                        'Content-Type: application/json'
                    ),
                    CURLOPT_POST        => 1,
                    CURLOPT_POSTFIELDS  => $data
                );
                break;
                
            case 'get':
                $curl_options = $curl_options + array(
                    CURLOPT_HTTPHEADER => array(
                        'Accept: application/json',
                        'Authorization: Bearer '.$this->accessToken
                    )
                );
                break;
                
            case 'oauth':
                $curl_options = $curl_options + array(
                    CURLOPT_HTTPHEADER => array('Accept: application/json'),
                    CURLOPT_POST       => 1,
                    CURLOPT_POSTFIELDS => $data
                );
                break;
        }
        
        // Set curl options
        curl_setopt_array($ch, $curl_options);
        
        // Send the request
        $result = curl_exec($ch);
        $error = curl_errno($ch);
        
        if($this->debug){
            var_dump($result);
            var_dump($error);
        }
        
        // Close the connection
        curl_close($ch);
        
        return json_decode($result, true);
    }
    
    private function get($endpoint, $data=false){
        return $this->call($endpoint, 'get', $data);
    }
    
    private function post($endpoint, $data){
        return $this->call($endpoint, 'post', $data);
    }
}

?>