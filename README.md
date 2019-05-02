# omnipay-fibank
Omnipay plugin for [fibank ECOMM](https://webops.eu/blog/fibank-bg-ecomm-картови-раплащания/) recurring payments

## Installation
```bash
composer require ampeco/omnipay-fibank
```

## Getting started

Create the gateway
```php
$gateway = Omnipay::create('\Ampeco\OmnipayFibank\Gateway');
$gateway->initialize([
    'merchantCertificate'           => '... The PEM certificate you got from the bank',
    'merchantCertificatePassword'   => 'The Certificate Password',
    'createCardAmount'              => 1.00, // The amount and currency to use for the create account initial payment
    'createCardCurrency'            => 'BGN',
    'testMode' => true,
]);
```

Add a new credit card
```php
try{
    $response = $gateway->createCard([
        'clientIp'      => 'CLIENT IP ADDRESS',
        'expiry'        => date('Y-m-d', strtotime('+10 years')),
        'description'   => 'Register a new payment method. The amount will be credited to your account',
])->send();
} catch (EcommException $e) {
    abort(422, $e->getMessage());
}

if (!$response->isSuccessful()) {
    abort(422, $response->getMessage());
}

// You must redirect the client to:
echo $response->getRedirectUrl();
echo $response->getTransactionId(); // The transaction ID assigned by the bank
```

Check if the client completed the card registration
```php
$transactionReference = '1234567890'; // Fetched from above - $response->getTransactionId()
try {
    $result = $gateway->transactionResult([
        'transactionId' => $transactionReference,
        'clientIp' => 'CLIENT IP ADDRESS',
    ])->send();
} catch (EcommException $e) {
    abort(422, $e->getMessage());
}

if (!$result->isSuccessful()){
    abort(422, $result->getMessage());
}

// The card reference
echo $result->getCardReference(); // recurring_test_reference1234`
```

Charge the saved credit card reference
```php
try {
    $cardReference = 'recurring_test_reference1234'; // saved from above - $result->getCardReference();
    $response = $gateway->purchase([
        'cardReference'     => $cardReference,
        'amount'            => 3,
        'currency'          => 'BGN',
        'description'       => 'Purchase #1234',
    ])->send();
} catch (EcommException $e) {
    abort(422, $e->getMessage());
}

if ($response->isSuccessful()) {
    echo $response->getTransactionReference();
    
} else {
    abort(422, $response->getMessage());
}
```