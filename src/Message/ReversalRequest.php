<?php

namespace Ampeco\OmnipayFibank\Message;

use Omnipay\Common\Message\ResponseInterface;

class ReversalRequest extends AbstractRequest
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
        $this->fibank->createTransactionCompletionCaptureRequest(100, '(#09aa987283) Register a new payment method. The amount will be reverted right after the card registration', $data['trans_id']);
        
        $response = $this->fibank->reverseTransaction($data['trans_id'], 100);
        
        return $this->createResponse($response);
    }
}
