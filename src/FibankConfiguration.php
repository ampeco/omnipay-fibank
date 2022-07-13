<?php

namespace Ampeco\OmnipayFibank;

use Money\Currencies\ISOCurrencies;
use Money\Currency;

class FibankConfiguration
{
    public static $global = [];

    public static function ecomm(): Ecomm
    {
        $res = new Ecomm();

        if (isset(self::$global['merchantCertificate'])) {
            $res->setMerchantCertificate(self::$global['merchantCertificate']);
        }

        if (isset(self::$global['merchantCertificatePassword'])) {
            $res->setMerchantCertificatePassword(self::$global['merchantCertificatePassword']);
        }

        if (isset(self::$global['merchantPreAuthorizeCertificate'])) {
            $res->setMerchantPreAuthorizeCertificate(self::$global['merchantPreAuthorizeCertificate']);
        }

        if (isset(self::$global['merchantPreAuthorizeCertificatePassword'])) {
            $res->setMerchantPreAuthorizeCertificatePassword(self::$global['merchantPreAuthorizeCertificatePassword']);
        }

        if (isset(self::$global['v2']) && self::$global['v2']) {
            $res->setV2();
        }

        if (isset(self::$global['currency'])) {
            $currencies = new ISOCurrencies();
            $currencyCode = $currencies->numericCodeFor(new Currency(self::$global['currency']));
            $res->setCurrencyCode($currencyCode);
        }

        if (isset(self::$global['connectTimeout'])) {
            $res->setConnectTimeout(self::$global['connectTimeout']);
        }

        if (isset(self::$global['testMode']) && self::$global['testMode']) {
            $res->setTestMode();
        } else {
            $res->setLiveMode();
        }

        return $res;
    }

    public static function merchantCertificate($value = null)
    {
        if (empty($value)) {
            return self::$global['merchantCertificate'];
        }
        self::$global['merchantCertificate'] = $value;
    }

    public static function merchantCertificatePassword($value = null)
    {
        if (empty($value)) {
            return self::$global['merchantCertificatePassword'];
        }
        self::$global['merchantCertificatePassword'] = $value;
    }

    public static function merchantPreAuthorizeCertificate($value = null)
    {
        if (empty($value)) {
            return self::$global['merchantPreAuthorizeCertificate'];
        }
        self::$global['merchantPreAuthorizeCertificate'] = $value;
    }

    public static function merchantPreAuthorizeCertificatePassword($value = null)
    {
        if (empty($value)) {
            return self::$global['merchantPreAuthorizeCertificatePassword'];
        }
        self::$global['merchantPreAuthorizeCertificatePassword'] = $value;
    }

    public static function currency($value = null)
    {
        if (empty($value)) {
            return self::$global['currency'];
        }
        self::$global['currency'] = $value;
    }

    public static function connectTimeout($value = null)
    {
        if (empty($value)) {
            return self::$global['connectTimeout'];
        }
        self::$global['connectTimeout'] = $value;
    }

    public static function testMode($value = null)
    {
        if (empty($value)) {
            return self::$global['testMode'];
        }
        self::$global['testMode'] = $value;
    }
}
