<?php

namespace Ampeco\OmnipayFibank;

use Ampeco\OmnipayFibank\Exceptions\EcommException;
use Illuminate\Support\Facades\Log;

/**
 * Class Ecomm
 */
class Ecomm
{
    const TEST_URL = 'https://mdpay-test.fibank.bg';
    const LIVE_URL = 'https://mdpay.fibank.bg';
    const PORT = '9443';

    const V2_PORT = '10443';

    const PATH = '/ecomm/MerchantHandler';
    const V2_PATH = '/ecomm_v2/MerchantHandler';

    const CLIENT_PATH = '/ecomm/ClientHandler';
    const V2_CLIENT_PATH = '/ecomm_v2/ClientHandler';

    protected $endpoint;
    protected $port = self::PORT;
    protected $path = self::PATH;
    protected $clientPath = self::CLIENT_PATH;
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
        $this->setV1();
    }

    public function setV2()
    {
        $this->port = self::V2_PORT;
        $this->path = self::V2_PATH;
        $this->clientPath = self::V2_CLIENT_PATH;
    }

    public function setV1()
    {
        $this->port = self::PORT;
        $this->path = self::PATH;
        $this->clientPath = self::CLIENT_PATH;
    }

    public function setTestMode()
    {
        $this->endpoint = static::TEST_URL;
    }

    public function setLiveMode()
    {
        $this->endpoint = static::LIVE_URL;
    }

    public function setMerchantCertificate($value)
    {
        $this->certificate_pem = $value;
    }

    public function setMerchantCertificatePassword($value)
    {
        $this->certificate_pass = $value;
    }

    public function setCurrencyCode($currencyCode)
    {
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
     * @throws EcommException
     * @return array
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
     * @throws EcommException
     * @return array
     */
    public function refundTransaction($trans_id, $amount = null)
    {
        $params = [
            'command' => 'k',
            'trans_id' => $trans_id,
        ];

        if ($amount !== null) {
            $params['amount'] = $amount;
        }

        return $this->sendRequest($params);
    }

    /**
     * @param $trans_id
     * @param $amount
     * @throws EcommException
     * @return array
     */
    public function reverseTransaction($trans_id, $amount = null)
    {
        $params = [
            'command' => 'r',
            'trans_id' => $trans_id,
        ];

        if ($amount !== null) {
            $params['amount'] = $amount;
        }

        return $this->sendRequest($params);
    }

    public function createRecurringPayment($amount, $description, $expiry, $language = 'en')
    {
        $params = [
            'command' => 'z',
            'amount' => $amount,
            'currency' => $this->currency,
            'client_ip_addr' => $this->client_ip_addr,
            'description' => $description,
            'language' => $language,
            'msg_type' => 'SMS',
            'perspayee_expiry' => date('my', strtotime($expiry)),
            'perspayee_gen' => '1',
        ];

        return $this->sendRequest($params);
    }

    public function createRecurringPayment2($amount, $description, $expiry, $language = 'en')
    {
        $params = [
            'command' => 'd',
            'amount' => $amount,
            'currency' => $this->currency,
            'client_ip_addr' => $this->client_ip_addr,
            'description' => $description,
            'language' => $language,
            'msg_type' => 'DMS',
            'biller_client_id' => 'asdf3',
            'perspayee_expiry' => date('my', strtotime($expiry)),
            'perspayee_gen' => '1',
            'oneclick' => 'Y',
        ];

        return $this->sendRequest($params);
    }

    public function createPreAuthorizationRequest($amount, $description, $cardReference, $language = 'en')
    {
        $params = [
            'command' => 'f',
            'amount' => $amount,
            'currency' => $this->currency,
            'client_ip_addr' => $this->client_ip_addr,
            'description' => $description,
            'language' => $language,
            'msg_type' => 'DMS',
            'biller_client_id' => $cardReference,
            'oneclick' => 'Y',
            'template_type' => 'DMS',
        ];

        Log::debug('createPreAuthorizationRequest', [$params]);

        return $this->sendRequest($params);
    }

    public function createTransactionCompletionCaptureRequest($amount, $description, $trans_id)
    {
        $params = [
            'command' => 't',
            'trans_id' => $trans_id,
            'amount' => $amount,
            'currency' => $this->currency,
            'client_ip_addr' => $this->client_ip_addr,
            'description' => $description,
            'msg_type' => 'DMS',
        ];

        return $this->sendRequest($params);
    }

    public function purchaseRecurringPayment($amount, $description, $recc_pmnt_id, $language = 'en')
    {
        $params = [
            'command' => 'e',
            'amount' => $amount,
            'currency' => $this->currency,
            'client_ip_addr' => $this->client_ip_addr,
            'description' => $description,
            'biller_client_id' => $recc_pmnt_id,
            'language' => $language,
        ];

        return $this->sendRequest($params);
    }

    public function deleteRecurringPayment($recc_pmnt_id)
    {
        $params = [
            'command' => 'x',
            'biller_client_id' => $recc_pmnt_id,
        ];

        return $this->sendRequest($params);
    }

    /**
     * @param $trans_id
     * @throws EcommException
     * @return array
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
        return $this->endpoint . $this->clientPath . '?trans_id=' . urlencode($trans_id);
    }

    /**
     * @param $params
     * @throws EcommException
     * @return array
     */
    protected function sendRequest($params)
    {
        $url = $this->endpoint . ':' . $this->port . $this->path;

        $ch = curl_init();

        if ($this->certificate_pem) {
            $tempPemFile = tmpfile();
            fwrite($tempPemFile, $this->certificate_pem);
            $tempPemPath = stream_get_meta_data($tempPemFile);
            $tempPemPath = $tempPemPath['uri'];

            curl_setopt($ch, CURLOPT_SSLCERT, $tempPemPath);
        }
        if ($this->certificate_pass) {
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
        curl_setopt($ch, CURLOPT_PROXY, '3.122.122.204:3128');
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
