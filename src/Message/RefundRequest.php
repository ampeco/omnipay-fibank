<?php

namespace Ampeco\OmnipayFibank\Message;

use Omnipay\Common\Message\ResponseInterface;

class RefundRequest extends AbstractRequest
{
    
    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */
    public function getData()
    {
        $amount = $this->getAmountInteger();
        return [
            'amount' => $amount,
            'trans_id' => $this->getTransactionId(),
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
        $response = $this->fibank->refundTransaction($data['trans_id'], $data['amount']);
        
        return $this->createResponse($response);
    }
}