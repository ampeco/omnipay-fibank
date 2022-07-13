<?php

namespace Ampeco\OmnipayFibankTests;

use Ampeco\OmnipayFibank\Ecomm;
use Ampeco\OmnipayFibank\Exceptions\EcommException;
use Ampeco\OmnipayFibank\FibankConfiguration;
use Ampeco\OmnipayFibank\Gateway;
use Mockery;
use PHPUnit\Framework\TestCase;

class OmnipayFibankTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_request_to_create_card()
    {
        $expire = date('Y-m-d', strtotime('+10 years'));
        $ecomm = Mockery::mock(Ecomm::class, function (\Mockery\MockInterface $mock) use ($expire) {
            $mock->shouldReceive('setV1')->once();
            $mock->shouldReceive('setTestMode')->once();
            $mock->shouldReceive('getRedirectUrl')->once()->andReturn('https://mdpay-test.fibank.bg/ClientHandler?trans_id=' . urlencode('bAt6JLX52DUbibbzD9gDFl5Ppr4='));
            $mock->shouldReceive('setMerchantCertificate')->with('')->once();
            $mock->shouldReceive('setMerchantCertificatePassword')->with('')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificate')->with('')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificatePassword')->with('')->once();
            $mock->shouldReceive('setClientIpAddr')->with('10.20.30.40')->once();
            $mock->shouldReceive('setCurrencyCode')->with(975)->once();
            $mock->shouldReceive('createRecurringPayment')->with(
                150,
                'Register a new payment method. The amount will be credited to your account',
                $expire,
                null,
            )->once()
                ->andReturn(['TRANSACTION_ID' => 'bAt6JLX52DUbibbzD9gDFl5Ppr4=']);
        });

        $gateway = new Gateway(null, null, $ecomm);
        $gateway->setTestMode(true);
        $gateway->setCreateCardAmount(1.5);
        $gateway->setCreateCardCurrency('BGN');

        $method = $gateway->createCard([
            'clientIp' => '10.20.30.40',
            'expiry' => $expire,
            'description' => 'Register a new payment method. The amount will be credited to your account',
        ])->send();

        $this->assertTrue($method->isSuccessful(), json_encode($method));

        $this->assertEquals('bAt6JLX52DUbibbzD9gDFl5Ppr4=', $method->getTransactionId());
    }

    /**
     * @test
     */
    public function it_can_check_a_request_to_create_card()
    {
        $ecomm = Mockery::mock(Ecomm::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('setV1')->once();
            $mock->shouldReceive('getRedirectUrl')->once();
            $mock->shouldReceive('setTestMode')->once();
            $mock->shouldReceive('setMerchantCertificate')->with('CERT')->once();
            $mock->shouldReceive('setMerchantCertificatePassword')->with('PWD')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificate')->with('')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificatePassword')->with('')->once();
            $mock->shouldReceive('setClientIpAddr')->with('10.20.30.40')->once();
            $mock->shouldReceive('setCurrencyCode')->with(null)->once();
            $mock->shouldReceive('checkTransactionStatus')->with(
                'bAt6JLX52DUbibbzD9gDFl5Ppr4=', false
            )->once()
                ->andReturn([
                    'RESULT' => 'OK',
                    'RESULT_CODE' => '000',
                    '3DSECURE' => 'AUTHENTICATED',
                    'RRN' => '611111407831',
                    'APPROVAL_CODE' => 'B30361',
                    'CARD_NUMBER' => '4***********6789',
                    'RECC_PMNT_ID' => 'recurring_test_reference1234',
                    'RECC_PMNT_EXPIRY' => '0118',
                ]);
        });

        $gateway = new Gateway(null, null, $ecomm);
        $gateway->setTestMode(true);
        $gateway->setMerchantCertificate('CERT');
        $gateway->setMerchantCertificatePassword('PWD');
        $result = $gateway->transactionResult([
            'transactionId' => 'bAt6JLX52DUbibbzD9gDFl5Ppr4=',
            'clientIp' => '10.20.30.40',
        ])->send();

        $this->assertEquals('recurring_test_reference1234', $result->getCardReference());
        $this->assertJsonStringEqualsJsonString('{"cardType":"Visa","last4":"6789", "imageUrl":"", "expirationMonth": 1,"expirationYear": 2018}',
            json_encode($result->getPaymentMethod()));
    }

    /**
     * @test
     */
    public function it_handles_errors_on_card_add()
    {
        $ecomm = Mockery::mock(Ecomm::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('setV1')->once();
            $mock->shouldReceive('getRedirectUrl')->once();
            $mock->shouldReceive('setTestMode')->once();
            $mock->shouldReceive('setMerchantCertificate')->with('CERT')->once();
            $mock->shouldReceive('setMerchantCertificatePassword')->with('PWD')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificate')->with('')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificatePassword')->with('')->once();
            $mock->shouldReceive('setClientIpAddr')->with('10.20.30.40')->once();
            $mock->shouldReceive('setCurrencyCode')->with(null)->once();
            $mock->shouldReceive('checkTransactionStatus')->with(
                'bAt6JLX52DUbibbzD9gDFl5Ppr4=', false
            )->once()
                ->andReturn([
                    'RESULT' => 'FAILED',
                    'RESULT_CODE' => '106',
                    '3DSECURE' => 'DECLINED',
                ]);
        });

        $gateway = new Gateway(null, null, $ecomm);
        $gateway->setTestMode(true);
        $gateway->setMerchantCertificate('CERT');
        $gateway->setMerchantCertificatePassword('PWD');
        $result = $gateway->transactionResult([
            'transactionId' => 'bAt6JLX52DUbibbzD9gDFl5Ppr4=',
            'clientIp' => '10.20.30.40',
        ])->send();

        $this->assertFalse($result->isSuccessful());

        $this->assertEquals('106', $result->getCode());
        $this->assertEquals('Decline, allowable PIN tries exceeded', $result->getMessage());

        $this->assertNull($result->getCardReference());
        $this->assertNull($result->getPaymentMethod());
    }

    /**
     * @test
     */
    public function it_handles_network_errors_on_card_add()
    {
        $ecomm = Mockery::mock(Ecomm::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('setV1')->once();
            $mock->shouldReceive('setTestMode')->once();
            $mock->shouldReceive('setMerchantCertificate')->with('CERT')->once();
            $mock->shouldReceive('setMerchantCertificatePassword')->with('PWD')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificate')->with('')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificatePassword')->with('')->once();
            $mock->shouldReceive('setClientIpAddr')->with('10.20.30.40')->once();
            $mock->shouldReceive('setCurrencyCode')->with(null)->once();
            $mock->shouldReceive('checkTransactionStatus')->with(
                'bAt6JLX52DUbibbzD9gDFl5Ppr4=', false
            )->once()
                ->andThrow(new EcommException('Cannot connect to server', -1));
        });

        $gateway = new Gateway(null, null, $ecomm);
        $gateway->setTestMode(true);
        $gateway->setMerchantCertificate('CERT');
        $gateway->setMerchantCertificatePassword('PWD');
        $this->expectExceptionMessage('Cannot connect to server');
        $gateway->transactionResult([
            'transactionId' => 'bAt6JLX52DUbibbzD9gDFl5Ppr4=',
            'clientIp' => '10.20.30.40',
        ])->send();
    }

    /**
     * @test
     */
    public function it_can_request_to_delete_card()
    {
        $ecomm = Mockery::mock(Ecomm::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('setV1')->once();
            $mock->shouldReceive('setTestMode')->once();
            $mock->shouldReceive('setMerchantCertificate')->with('CERT')->once();
            $mock->shouldReceive('setMerchantCertificatePassword')->with('PWD')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificate')->with('')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificatePassword')->with('')->once();
            $mock->shouldReceive('setClientIpAddr')->with(null)->once();
            $mock->shouldReceive('setCurrencyCode')->with(null)->once();
            $mock->shouldReceive('deleteRecurringPayment')->with(
                'recurring_test_reference1234'
            )->once()
                ->andReturn(['RESULT' => 'OK']);
        });

        $gateway = new Gateway(null, null, $ecomm);
        $gateway->setTestMode(true);
        $gateway->setMerchantCertificate('CERT');
        $gateway->setMerchantCertificatePassword('PWD');
        $method = $gateway->deleteCard([
            'cardReference' => 'recurring_test_reference1234',
        ])->send();

        $this->assertTrue($method->isSuccessful());

        $ecomm = Mockery::mock(Ecomm::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('setV1')->once();
            $mock->shouldReceive('setTestMode')->once();
            $mock->shouldReceive('setMerchantCertificate')->with('CERT')->once();
            $mock->shouldReceive('setMerchantCertificatePassword')->with('PWD')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificate')->with('')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificatePassword')->with('')->once();
            $mock->shouldReceive('setClientIpAddr')->with(null)->once();
            $mock->shouldReceive('setCurrencyCode')->with(null)->once();
            $mock->shouldReceive('deleteRecurringPayment')->with(
                'recurring_test_reference1234'
            )->once()
                ->andThrow(new EcommException('Some failure'));
        });

        $gateway = new Gateway(null, null, $ecomm);
        $gateway->setTestMode(true);
        $gateway->setMerchantCertificate('CERT');
        $gateway->setMerchantCertificatePassword('PWD');
        $this->expectExceptionMessage('Some failure');
        $method = $gateway->deleteCard([
            'cardReference' => 'recurring_test_reference1234',
        ])->send();
    }

    /**
     * @test
     */
    public function it_can_make_a_purchase_with_a_saved_card()
    {
        $ecomm = Mockery::mock(Ecomm::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('setV1')->once();
            $mock->shouldReceive('setTestMode')->once();
            $mock->shouldReceive('setMerchantCertificate')->with('CERT')->once();
            $mock->shouldReceive('setMerchantCertificatePassword')->with('PWD')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificate')->with('')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificatePassword')->with('')->once();
            $mock->shouldReceive('getRedirectUrl')->once()->andReturn('https://mdpay-test.fibank.bg/ClientHandler?trans_id=' . urlencode('trfG/yvwuFsYXRY5uLgKWBLQvxM='));
            $mock->shouldReceive('setClientIpAddr')->with(null)->once();
            $mock->shouldReceive('setCurrencyCode')->with(975)->once();
            $mock->shouldReceive('purchaseRecurringPayment')->with(
                1000, 'Purchase #01234', 'recurring_test_reference1234', null
            )->once()
                ->andReturn([
                    'TRANSACTION_ID' => 'trfG/yvwuFsYXRY5uLgKWBLQvxM=',
                    'RESULT' => 'OK',
                    'RESULT_CODE' => '000',
                    'RRN' => '611111407827',
                    'APPROVAL_CODE' => '783052',
                ]);
        });

        $gateway = new Gateway(null, null, $ecomm);
        $gateway->setTestMode(true);
        $gateway->setMerchantCertificate('CERT');
        $gateway->setMerchantCertificatePassword('PWD');
        $response = $gateway->purchase([
            'cardReference' => 'recurring_test_reference1234',
            'amount' => '10',
            'currency' => 'BGN',
            'description' => 'Purchase #01234',
        ])->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('trfG/yvwuFsYXRY5uLgKWBLQvxM=', $response->getTransactionReference());
    }

    /**
     * @test
     */
    public function it_displays_correct_messages_on_2xx_failures()
    {
        $ecomm = Mockery::mock(Ecomm::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('setV1')->once();
            $mock->allows()->setTestMode()->once();
            $mock->allows()->setMerchantCertificate('CERT')->once();
            $mock->allows()->setMerchantCertificatePassword('PWD')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificate')->with('')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificatePassword')->with('')->once();
            $mock->allows()->setClientIpAddr(null)->once();
            $mock->allows()->setCurrencyCode(975)->once();
            $mock->allows()->purchaseRecurringPayment(
                1000, 'Purchase #01234', 'recurring_test_reference1234', null
            )->once()->andReturn([
                'RESULT' => 'FAILED',
                'RESULT_CODE' => '201',
            ]);
        });

        $gateway = new Gateway(null, null, $ecomm);
        $gateway->setTestMode(true);
        $gateway->setMerchantCertificate('CERT');
        $gateway->setMerchantCertificatePassword('PWD');
        $response = $gateway->purchase([
            'cardReference' => 'recurring_test_reference1234',
            'amount' => '10',
            'currency' => 'BGN',
            'description' => 'Purchase #01234',
        ])->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertEquals('Regular payment has been deleted', $response->getMessage());
        $this->assertEquals('201', $response->getCode());
    }

    /**
     * @test
     */
    public function it_can_refund_transaction_with_full_amount()
    {
        $ecomm = Mockery::mock(Ecomm::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('setV1')->once();
            $mock->allows()->setTestMode()->once();
            $mock->allows()->setMerchantCertificate('CERT')->once();
            $mock->allows()->setMerchantCertificatePassword('PWD')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificate')->with('')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificatePassword')->with('')->once();
            $mock->allows()->setClientIpAddr(null)->once();
            $mock->allows()->setCurrencyCode(null)->once();
            $mock->allows()->refundTransaction(
                'trfG/yvwuFsYXRY5uLgKWBLQvxM=', null
            )->once()->andReturn([
                'RESULT' => 'OK',
                'RESULT_CODE' => '000',
                'REFUND_TRANS_ID' => '76315716523785127835',
            ]);
        });

        $gateway = new Gateway(null, null, $ecomm);
        $gateway->setTestMode(true);
        $gateway->setMerchantCertificate('CERT');
        $gateway->setMerchantCertificatePassword('PWD');
        $response = $gateway->refund([
            'transactionId' => 'trfG/yvwuFsYXRY5uLgKWBLQvxM=',
        ])->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('Approved', $response->getMessage());
        $this->assertEquals('000', $response->getCode());
    }

    /**
     * @test
     */
    public function it_can_refund_transaction_with_partial_amount()
    {
        $ecomm = Mockery::mock(Ecomm::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldReceive('setV1')->once();
            $mock->allows()->setTestMode()->once();
            $mock->allows()->setMerchantCertificate('CERT')->once();
            $mock->allows()->setMerchantCertificatePassword('PWD')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificate')->with('')->once();
            $mock->shouldReceive('setMerchantPreAuthorizeCertificatePassword')->with('')->once();
            $mock->allows()->setClientIpAddr(null)->once();
            $mock->allows()->setCurrencyCode(975)->once();
            $mock->allows()->refundTransaction(
                'trfG/yvwuFsYXRY5uLgKWBLQvxM=', 500
            )->once()->andReturn([
                'RESULT' => 'OK',
                'RESULT_CODE' => '000',
                'REFUND_TRANS_ID' => '76315716523785127835',
            ]);
        });

        $gateway = new Gateway(null, null, $ecomm);
        $gateway->setTestMode(true);
        $gateway->setMerchantCertificate('CERT');
        $gateway->setMerchantCertificatePassword('PWD');
        $response = $gateway->refund([
            'transactionId' => 'trfG/yvwuFsYXRY5uLgKWBLQvxM=',
            'amount' => 5,
            'currency' => 'BGN',
        ])->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('Approved', $response->getMessage());
        $this->assertEquals('000', $response->getCode());
    }

    /**
     * @test
     */
    public function it_uses_configuration()
    {
        FibankConfiguration::currency('BGN');
        FibankConfiguration::merchantCertificate('CERT');
        FibankConfiguration::merchantCertificatePassword('PASS');

        $actual = FibankConfiguration::ecomm();

        $expected = new Ecomm();
        $expected->setCurrencyCode(975);
        $expected->setMerchantCertificate('CERT');
        $expected->setMerchantCertificatePassword('PASS');

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_calls_send_request_with_pre_auth_terminal_when_authorization_request()
    {
        $ecomm = Mockery::mock(Ecomm::class, function (\Mockery\MockInterface $mock) {
            $mock->shouldAllowMockingProtectedMethods();
            $mock->shouldReceive('setV1')->once();
            $mock->allows()->setTestMode()->once();
            $mock->allows()->setMerchantCertificate('CERT')->once();
            $mock->allows()->setMerchantCertificatePassword('PWD')->once();
            $mock->allows()->setMerchantPreAuthorizeCertificate('CERT2')->once();
            $mock->allows()->setMerchantPreAuthorizeCertificatePassword('PWD2')->once();
            $mock->allows()->setClientIpAddr(null)->once();
            $mock->allows()->setCurrencyCode(975)->once();
            $mock->shouldReceive('sendRequest')->withArgs([[
                'command' => 'f',
                'amount' => 1000,
                'currency' => null,
                'client_ip_addr' => null,
                'description' => 'Test',
                'language' => null,
                'msg_type' => 'DMS',
                'biller_client_id' => '12345',
                'oneclick' => 'Y',
                'template_type' => 'DMS',
            ], true])->once()->andReturn([
                'RESULT' => 'OK',
                'RESULT_CODE' => '000',
                'TRANSACTION_ID' => '76315716523785127835',
            ]);
        })->makePartial();

        $gateway = new Gateway(null, null, $ecomm);
        $gateway->setTestMode(true);
        $gateway->setMerchantCertificate('CERT');
        $gateway->setMerchantCertificatePassword('PWD');
        $gateway->setMerchantPreAuthorizeCertificate('CERT2');
        $gateway->setMerchantPreAuthorizeCertificatePassword('PWD2');
        $response = $gateway->authorize([
            'amount' => 10,
            'description' => 'Test',
            'cardReference' => '12345',
            'currency' => 'BGN',
        ])->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('Approved', $response->getMessage());
        $this->assertEquals('000', $response->getCode());
    }

    public function tearDown(): void
    {
        Mockery::close();
    }
}
