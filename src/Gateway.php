<?php

namespace Ampeco\OmnipayFibank;

use Omnipay\Common\AbstractGateway;
use Omnipay\Common\Http\ClientInterface;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Braintree Gateway
 * @method \Omnipay\Common\Message\RequestInterface completeAuthorize(array $options = array())
 * @method \Omnipay\Common\Message\RequestInterface completePurchase(array $options = array())
 * @method \Omnipay\Common\Message\RequestInterface updateCard(array $options = array())
 */
class Gateway extends AbstractGateway
{
    /** @var Ecomm */
    protected $fibank;

    /**
     * Create a new gateway instance
     *
     * @param ClientInterface $httpClient A Guzzle client to make API calls with
     * @param HttpRequest $httpRequest A Symfony HTTP request object
     * @param Ecomm|null $ecomm
     */
    public function __construct(ClientInterface $httpClient = null, HttpRequest $httpRequest = null, Ecomm $ecomm = null)
    {
        $this->fibank = $ecomm ?: FibankConfiguration::ecomm();

        parent::__construct($httpClient, $httpRequest);
    }

    /**
     * Get gateway display name
     *
     * This can be used by carts to get the display name for each gateway.
     * @return string
     */
    public function getName()
    {
        return 'Fibank';
    }

    public function getDefaultParameters()
    {
        return [
            'merchantCertificate'         => '',
            'merchantCertificatePassword' => '',
            'testMode'                    => false,
            'v2'                          => false,
        ];
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

    public function setMerchantPreAuthorizeCertificate($value)
    {
        return $this->setParameter('merchantPreAuthorizeCertificate', $value);
    }

    public function setMerchantPreAuthorizeCertificatePassword($value)
    {
        return $this->setParameter('merchantPreAuthorizeCertificatePassword', $value);
    }

    public function getCreateCardAmount()
    {
        return $this->getParameter('createCardAmount');
    }

    public function setCreateCardAmount($value)
    {
        return $this->setParameter('createCardAmount', $value);
    }

    public function getCreateCardCurrency()
    {
        return $this->getParameter('createCardCurrency');
    }

    public function setCreateCardCurrency($value)
    {
        return $this->setParameter('createCardCurrency', $value);
    }

    public function getSuccessUrl()
    {
        return $this->getParameter('successUrl');
    }

    public function setSuccessUrl($value)
    {
        return $this->setParameter('successUrl', $value);
    }

    public function getConnectTimeout()
    {
        return $this->getParameter('connectTimeout');
    }

    public function setConnectTimeout($value)
    {
        return $this->setParameter('connectTimeout', $value);
    }

    protected function createRequest($class, array $parameters)
    {
        $obj = new $class($this->httpClient, $this->httpRequest, $this->fibank);

        return $obj->initialize(array_replace($this->getParameters(), $parameters));
    }

    public function authorize(array $parameters = [])
    {
        return $this->createRequest('\Ampeco\OmnipayFibank\Message\AuthorizeRequest', $parameters);
    }

    public function supportsAuthorize()
    {
        return $this->fibank->useDMS();
    }

    public function capture(array $parameters = [])
    {
        return $this->createRequest('\Ampeco\OmnipayFibank\Message\CaptureRequest', $parameters);
    }

    public function supportsCapture()
    {
        return true;
    }

    public function void(array $parameters = [])
    {
        return $this->reverse($parameters);
    }

    public function createCard(array $parameters = [])
    {
        if (!isset($parameters['currency'])) {
            $parameters['currency'] = $this->getCreateCardCurrency();
        }
        if (!isset($parameters['amount'])) {
            $parameters['amount'] = $this->getCreateCardAmount();
        }

        return $this->createRequest('\Ampeco\OmnipayFibank\Message\CreateCardRequest', $parameters);
    }

    public function deleteCard(array $parameters = [])
    {
        return $this->createRequest('\Ampeco\OmnipayFibank\Message\DeleteCardRequest', $parameters);
    }

    public function purchase(array $parameters = [])
    {
        return $this->createRequest('\Ampeco\OmnipayFibank\Message\PurchaseRequest', $parameters);
    }

    public function refund(array $parameters = [])
    {
        return $this->createRequest('\Ampeco\OmnipayFibank\Message\RefundRequest', $parameters);
    }

    public function transactionResult(array $parameters = [])
    {
        return $this->createRequest('\Ampeco\OmnipayFibank\Message\TransactionResultRequest', $parameters);
    }

    public function reverse(array $parameters = [])
    {
        return $this->createRequest('\Ampeco\OmnipayFibank\Message\ReversalRequest', $parameters);
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement @method \Omnipay\Common\Message\RequestInterface completeAuthorize(array $options = array())
        // TODO: Implement @method \Omnipay\Common\Message\RequestInterface completePurchase(array $options = array())
        // TODO: Implement @method \Omnipay\Common\Message\RequestInterface updateCard(array $options = array())
    }
}
