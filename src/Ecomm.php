<?php

namespace Ampeco\OmnipayFibank;



use Ampeco\OmnipayFibank\Exceptions\EcommException;

/**
 * Class Ecomm
 */
class Ecomm
{
    const TEST_URL = 'https://mdpay-test.fibank.bg';
    const LIVE_URL = 'https://mdpay.fibank.bg';
    const PORT = '9443';

    protected $endpoint;
    protected $certificate_pem;
    protected $certificate_pass;
    protected $client_ip_addr;
    protected $connect_timeout = 60;
    protected $currency;

    /**
     * Ecomm constructor.
     * @param array $config
     */
    public function __construct()
    {
        $this->setLiveMode();
    }
    
    public function setTestMode(){
        $this->endpoint = static::TEST_URL;
    }
    public function setLiveMode(){
        $this->endpoint = static::LIVE_URL;
    }
    
    public function setMerchantCertificate($value){
        $this->certificate_pem = $value;
    }
    public function setMerchantCertificatePassword($value)
    {
        $this->certificate_pass = $value;
    }

    public function setCurrencyCode($currencyCode){
        $this->currency = $currencyCode;
    }
    /**
     * @param $client_ip_addr
     */
    public function setClientIpAddr($client_ip_addr)
    {
        $this->client_ip_addr = $client_ip_addr;
    }

    /**
     * @param $seconds
     */
    public function setConnectTimeout($seconds)
    {
        $this->connect_timeout = $seconds;
    }
    
    /**
     * @param $amount
     * @param $description
     * @return array
     * @throws EcommException
     * @throws \Gentor\Fibank\Service\EcommException
     */
    public function sendTransaction($amount, $description)
    {
        $params = [
            'command' => 'v',
            'amount' => $amount,
            'currency' => $this->currency,
            'client_ip_addr' => $this->client_ip_addr,
            'description' => $description,
        ];

        return $this->sendRequest($params);
    }
    
    /**
     * @param $trans_id
     * @param $amount
     * @return array
     * @throws EcommException
     * @throws \Gentor\Fibank\Service\EcommException
     */
    public function refundTransaction($trans_id, $amount = null)
    {
        $params = [
            'command'  => 'k',
            'trans_id' => $trans_id,
        ];
        
        if ($amount !== null){
            $params['amount'] = $amount;
        }
        
        return $this->sendRequest($params);
    }
    
    public function createRecurringPayment($amount, $description, $expiry, $language='en'){
        $params = [
            'command'            => 'z',
            'amount'             => $amount,
            'currency'           => $this->currency,
            'client_ip_addr'     => $this->client_ip_addr,
            'description'        => $description,
            'language'        => $language,
            'msg_type'           => 'SMS',
            'perspayee_expiry'   => date('my', strtotime($expiry)),
            'perspayee_gen'      => '1',
        ];
        return $this->sendRequest($params);
    }
    
    public function purchaseRecurringPayment($amount, $description, $recc_pmnt_id, $language='en')
    {
        $params = [
            'command'          => 'e',
            'amount'           => $amount,
            'currency'         => $this->currency,
            'client_ip_addr'   => $this->client_ip_addr,
            'description'      => $description,
            'biller_client_id' => $recc_pmnt_id,
            'language' => $language,
        ];
        return $this->sendRequest($params);
    }
    
    public function deleteRecurringPayment($recc_pmnt_id)
    {
        $params = [
            'command'          => 'x',
            'biller_client_id' => $recc_pmnt_id,
        ];
        return $this->sendRequest($params);
    }
    
    /**
     * @param $trans_id
     * @return array
     * @throws EcommException
     * @throws \Gentor\Fibank\Service\EcommException
     */
    public function checkTransactionStatus($trans_id)
    {
        $params = [
            'command' => 'c',
            'trans_id' => $trans_id,
            'client_ip_addr' => $this->client_ip_addr,
        ];

        return $this->sendRequest($params);
    }

    /**
     * @param $trans_id
     * @return string
     */
    public function getRedirectUrl($trans_id)
    {
        return $this->endpoint . '/ecomm/ClientHandler?trans_id=' . urlencode($trans_id);
    }

    /**
     * @param $params
     * @return array
     * @throws EcommException
     */
    protected function sendRequest($params)
    {
        $url = $this->endpoint . ':' . static::PORT . '/ecomm/MerchantHandler';
        
        $ch = curl_init();

        if ($this->certificate_pem){
        
            $tempPemFile = tmpfile();
            fwrite($tempPemFile, $this->certificate_pem);
            $tempPemPath = stream_get_meta_data($tempPemFile);
            $tempPemPath = $tempPemPath['uri'];
        
            curl_setopt($ch, CURLOPT_SSLCERT, $tempPemPath);
        }
        if ($this->certificate_pass){
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->certificate_pass);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_NOPROGRESS, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);

        $result = curl_exec($ch);
    
        if ($this->certificate_pem) {
            fclose($tempPemFile);
        }

        if ($error = curl_error($ch)) {
            curl_close($ch);
            throw new EcommException($error);
        }

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (200 != $http_code) {
            curl_close($ch);
            throw new EcommException('Error: ' . $http_code, $http_code);
        }

        curl_close($ch);

        $response = [];

        if (substr($result, 0, 5) == 'error') {
            $error = substr($result, 6);
            throw new EcommException($error);
        } else {
            foreach (explode("\n", $result) as $nvp) {
                list($key, $value) = explode(': ', $nvp);
                $response[$key] = $value;
            }
        }

        return $response;
    }

}