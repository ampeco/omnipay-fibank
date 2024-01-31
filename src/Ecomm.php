<?php

namespace Ampeco\OmnipayFibank;

use Ampeco\OmnipayFibank\Exceptions\EcommException;
use Ampeco\OmnipayFibank\Exceptions\NotSupportedException;
use Ramsey\Uuid\Uuid;

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
    protected $auth_certificate_pem;
    protected $auth_certificate_pass;
    protected $client_ip_addr;
    protected $connect_timeout = 60;
    protected $currency;

    protected ?string $proxy = null;

    /**
     * Ecomm constructor.
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

    public function setMerchantPreAuthorizeCertificate($value)
    {
        $this->auth_certificate_pem = $value;
    }

    public function setMerchantPreAuthorizeCertificatePassword($value)
    {
        $this->auth_certificate_pass = $value;
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

    public function setProxy(string $proxy)
    {
        $this->proxy = $proxy;
    }

    /**
     * Should we use dual message mode for payments and add card
     * @return bool
     */
    public function useDMS()
    {
        return $this->auth_certificate_pem && $this->auth_certificate_pass;
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

    /**
     * @param $amount
     * @param $description
     * @param $expiry
     * @param $language
     * @throws EcommException
     * @return array
     */
    public function createDMSAddCardRequest($amount, $description, $expiry, $language = 'en')
    {
        $params = [
            'command' => 'd',
            'amount' => $amount,
            'currency' => $this->currency,
            'client_ip_addr' => $this->client_ip_addr,
            'description' => $description,
            'language' => $language,
            'msg_type' => 'DMS',
            'biller_client_id' => Uuid::uuid4(),
            'perspayee_expiry' => date('my', strtotime($expiry)),
            'perspayee_gen' => '1',
            'oneclick' => 'Y',
        ];

        return $this->sendRequest($params);
    }

    public function createAuthorizationRequest($amount, $description, $cardReference, $language = 'en')
    {
        if (!$this->useDMS()) {
            throw new NotSupportedException('According to settings pre-authorization terminal is not supported');
        }
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

        return $this->sendRequest($params);
    }

    public function createCaptureRequest($amount, $description, $trans_id)
    {
        if (!$this->useDMS()) {
            throw new NotSupportedException('According settings pre-authorization terminal is not supported');
        }
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

    public function purchaseDMSRecurringPayment($amount, $description, $cardReference, $language = 'en')
    {
        $authResponse = $this->createAuthorizationRequest($amount, $description, $cardReference, $language);
        if (isset($authResponse['TRANSACTION_ID']) && $authResponse['TRANSACTION_ID']) {
            //additional data in order to redirect user to SCA
            $statusResponse = $this->checkTransactionStatus($authResponse['TRANSACTION_ID']);
        }

        return array_merge($authResponse, $statusResponse);
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

    protected function sendRequest($params)
    {
        $url = $this->endpoint . ':' . $this->port . $this->path;

        $ch = curl_init();

        $tempPemFile = $this->setCertificates($ch, $this->useDMS());

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
        if ($this->proxy) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        }

        $result = curl_exec($ch);

        if ($tempPemFile) {
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

    protected function setCertificates(\CurlHandle $ch, bool $withPreAuthCertificate = false)
    {
        $tempPemFile = null;
        if ($withPreAuthCertificate) {
            $certificate = $this->auth_certificate_pem;
            $pass = $this->auth_certificate_pass;
        } else {
            $certificate = $this->certificate_pem;
            $pass = $this->certificate_pass;
        }

        if ($certificate) {
            $tempPemFile = tmpfile();
            fwrite($tempPemFile, $certificate);
            $tempPemPath = stream_get_meta_data($tempPemFile);
            $tempPemPath = $tempPemPath['uri'];

            curl_setopt($ch, CURLOPT_SSLCERT, $tempPemPath);
        }

        if ($pass) {
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $pass);
        }

        return $tempPemFile;
    }
}
