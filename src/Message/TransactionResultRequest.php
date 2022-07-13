<?php

namespace Ampeco\OmnipayFibank\Message;

use Omnipay\Common\Message\ResponseInterface;

class TransactionResultRequest extends AbstractRequest
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
            'trans_id' => $this->getTransactionId(),
            'withPreAuthCertificate' => $this->getWithPreAuthCertificate(),
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
        $response = $this->fibank->checkTransactionStatus($data['trans_id'], $data['withPreAuthCertificate']);
        $response['TRANSACTION_ID'] = $data['trans_id'];

        return $this->createResponse($response);
    }
}
