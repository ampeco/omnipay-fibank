<?php

namespace Ampeco\OmnipayFibank\Message;

use Ampeco\OmnipayFibank\Ecomm;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\Common\Message\AbstractRequest as BaseAbstractRequest;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Abstract Request
 */
abstract class AbstractRequest extends BaseAbstractRequest
{
    /** @var Ecomm */
    protected $fibank;

    /**
     * Create a new Request
     *
     * @param ClientInterface $httpClient A Guzzle client to make API calls with
     * @param HttpRequest $httpRequest A Symfony HTTP request object
     * @param Ecomm $braintree The Braintree Gateway
     */
    public function __construct(ClientInterface $httpClient, HttpRequest $httpRequest, Ecomm $fibank)
    {
        $this->fibank = $fibank;

        parent::__construct($httpClient, $httpRequest);
    }

    /**
     * Set the correct configuration sending
     *
     * @return \Omnipay\Common\Message\ResponseInterface
     */
    public function send()
    {
        $this->configure();

        return parent::send();
    }

    public function getMerchantCertificate()
    {
        return $this->getParameter('merchantCertificate');
    }

    public function setMerchantCertificate($value)
    {
        return $this->setParameter('merchantCertificate', $value);
    }

    public function getV2()
    {
        return $this->getParameter('v2');
    }

    public function setV2($value)
    {
        return $this->setParameter('v2', $value);
    }

    public function getMerchantCertificatePassword()
    {
        return $this->getParameter('merchantCertificatePassword');
    }

    public function setMerchantCertificatePassword($value)
    {
        return $this->setParameter('merchantCertificatePassword', $value);
    }

    public function getConnectTimeout()
    {
        return $this->getParameter('connectTimeout');
    }

    public function setConnectTimeout($value)
    {
        return $this->setParameter('connectTimeout', $value);
    }

    public function getLanguage()
    {
        return $this->getParameter('language');
    }

    public function setLanguage($value)
    {
        return $this->setParameter('language', $value);
    }

    public function configure()
    {
        if ($this->getTestMode()) {
            $this->fibank->setTestMode();
        } else {
            $this->fibank->setLiveMode();
        }

        if ($this->getV2()) {
            $this->fibank->setV2();
        } else {
            $this->fibank->setV1();
        }

        $this->fibank->setMerchantCertificate($this->getMerchantCertificate());
        $this->fibank->setMerchantCertificatePassword($this->getMerchantCertificatePassword());

        $this->fibank->setMerchantPreAuthorizeCertificate($this->getMerchantPreAuthorizeCertificate());
        $this->fibank->setMerchantPreAuthorizeCertificatePassword($this->getMerchantPreAuthorizeCertificatePassword());

        $this->fibank->setClientIpAddr($this->getClientIp());

        $this->fibank->setCurrencyCode($this->getCurrencyNumeric());

        if ($this->getConnectTimeout()) {
            $this->fibank->setConnectTimeout($this->getConnectTimeout());
        }
    }

    protected function createResponse($data, $isSuccessful = null, $additionalResultCodes = [])
    {
        if (isset($data['TRANSACTION_ID']) && !isset($data[''])) {
            $data = array_merge($data, [
                'redirect_url' => $this->fibank->getRedirectUrl($data['TRANSACTION_ID']),
            ]);
        }
        if (!is_null($isSuccessful)) {
            $data['isSuccessful'] = $isSuccessful;
        }
        if ($additionalResultCodes) {
            $data['additionalResultCodes'] = $additionalResultCodes;
        }

        return $this->response = new Response($this, $data);
    }

    public function setExpiry($expiry)
    {
        $this->parameters->set('expiry', $expiry);
    }

    public function setMerchantPreAuthorizeCertificate($value)
    {
        return $this->setParameter('merchantPreAuthorizeCertificate', $value);
    }

    public function setMerchantPreAuthorizeCertificatePassword($value)
    {
        return $this->setParameter('merchantPreAuthorizeCertificatePassword', $value);
    }

    public function getMerchantPreAuthorizeCertificate()
    {
        return $this->getParameter('merchantPreAuthorizeCertificate');
    }

    public function getMerchantPreAuthorizeCertificatePassword()
    {
        return $this->getParameter('merchantPreAuthorizeCertificatePassword');
    }

    public function getWithPreAuthCertificate()
    {
        return $this->fibank->useDMS();
        // return (bool) $this->getParameter('withPreAuthCertificate');
    }

//    public function setWithPreAuthCertificate($value)
//    {
//        return $this->setParameter('withPreAuthCertificate', $value);
//    }
}
