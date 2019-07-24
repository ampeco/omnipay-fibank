<?php

namespace Ampeco\OmnipayFibank\Message;

use Omnipay\Common\Message\ResponseInterface;

class PurchaseRequest extends AbstractRequest
{
    
    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */
    public function getData()
    {
        return [
            'amount'       => $this->getAmountInteger(),
            'description'  => $this->getDescription(),
            'recc_pmnt_id' => $this->getCardReference(),
            'language'     => $this->getLanguage(),
        ];
    }
    
    /**
     * Send the request with specified data
     *
     * @param mixed $data The data to send
     * @return ResponseInterface
     */
    public function sendData($data)
    {
        $response = $this->fibank->purchaseRecurringPayment($data['amount'], $data['description'],
            $data['recc_pmnt_id'], $data['language']);
        
        return $this->createResponse($response, null, [
            '108' => 'Merchant communication with cardholder has to be done',
            '114' => 'It is possible to try to execute the transaction next time',
            '180' => 'Cardholder ended cooperation. Regular payment has been deleted',
            '2xx' => 'Regular payment has been deleted',
        ]);
    }
}
