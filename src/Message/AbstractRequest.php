<?php

namespace Ampeco\OmnipayFibank\Message;
use Ampeco\OmnipayFibank\Ecomm;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\Common\Exception\InvalidRequestException;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Omnipay\Common\Message\AbstractRequest as BaseAbstractRequest;

/**
 * Abstract Request
 *
 */
abstract class AbstractRequest extends BaseAbstractRequest
{
    /**
     * @var Ecomm
     */
    protected $fibank;

    /**
     * Create a new Request
     *
     * @param ClientInterface $httpClient  A Guzzle client to make API calls with
     * @param HttpRequest     $httpRequest A Symfony HTTP request object
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
    
    public function getMerchantCertificatePassword()
    {
        return $this->getParameter('merchantCertificatePassword');
    }
    
    public function setMerchantCertificatePassword($value)
    {
        return $this->setParameter('merchantCertificatePassword', $value);
    }
    public function configure()
    {
        if ($this->getTestMode()){
            $this->fibank->setTestMode();
        } else {
            $this->fibank->setLiveMode();
        }
        
        $this->fibank->setMerchantCertificate($this->getMerchantCertificate());
        $this->fibank->setMerchantCertificatePassword($this->getMerchantCertificatePassword());
        
        $this->fibank->setClientIpAddr($this->getClientIp());
        
        $this->fibank->setCurrencyCode($this->getCurrencyNumeric());
    }
    
    
    protected function createResponse($data, $isSuccessful=null, $additionalResultCodes = [])
    {
        if (isset($data['TRANSACTION_ID']) && !isset($data[''])){
            $data = array_merge($data, [
                'redirect_url' => $this->fibank->getRedirectUrl($data['TRANSACTION_ID'])
            ]);
        }
        if (!is_null($isSuccessful)){
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
    
    
}
