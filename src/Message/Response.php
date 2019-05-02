<?php

namespace Ampeco\OmnipayFibank\Message;

use Ampeco\OmnipayFibank\Exceptions\EcommException;
use Omnipay\Common\Message\AbstractResponse;

class Response extends AbstractResponse
{
    
    /**
     * Is the response successful?
     *
     * @return boolean
     */
    public function isSuccessful()
    {
        if (isset($this->data['isSuccessful'])){
            return (bool)$this->data['isSuccessful'];
        }
        return @$this->data['RESULT'] == 'OK';
    }
    
    public function getTransactionId()
    {
        return @$this->data['TRANSACTION_ID'];
    }
    
    public function getTransactionReference()
    {
        return @$this->data['TRANSACTION_ID'];
    }
    
    public function getRedirectUrl()
    {
        return @$this->data['redirect_url'];
    }
    
    public function isRedirect()
    {
        return isset($this->data['redirect_url']);
    }
    
    public function getCardReference()
    {
        return @$this->data['RECC_PMNT_ID'];
    }
    
    public function getCode()
    {
        return @$this->data['RESULT_CODE'];
    }
    
    public function getMessage()
    {
        if (isset($this->data['RESULT_CODE']) && isset($this->data['additionalResultCodes'])){
            $firstNumber = substr($this->data['RESULT_CODE'], 0, 1);
            if (isset($this->data['additionalResultCodes'][$this->data['RESULT_CODE']])){
                return $this->data['additionalResultCodes'][$this->data['RESULT_CODE']];
            } else if (isset($this->data['additionalResultCodes'][$firstNumber.'xx'])){
                return $this->data['additionalResultCodes'][$firstNumber . 'xx'];
            }
        }
        return @EcommException::$responseCodes[$this->data['RESULT_CODE']];
    }
    
    public function getPaymentMethod(){
        if (!isset($this->data['CARD_NUMBER'])){
            return null;
        }
        $card_number = $this->data['CARD_NUMBER'];
        $res = new \stdClass();
        $res->imageUrl = '';
        $res->last4 = '';
        $res->cardType = 'Unknown';
        if (!$card_number){
            return $res;
        }
        
        $prefix = substr($card_number, 0, 1);
        switch ($prefix){
            case 4:
                $res->cardType = 'Visa';
                break;
            case 5:
                $res->cardType = 'MasterCard';
                break;
            case 6:
                $res->cardType = 'Discover/Diners Club';
                break;
            case 3:
                $res->cardType = 'Maestro';
                break;
        }
        
        $last4 = substr($card_number, -4);
        $res->last4 = $last4;
        
        return $res;
    }
    
}