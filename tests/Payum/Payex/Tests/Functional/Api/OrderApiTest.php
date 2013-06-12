<?php
namespace Payum\Payex\Tests\Functional\Api;

use Payum\Payex\Api\OrderApi;
use Payum\Payex\Api\SoapClientFactory;

class OrderApiTest extends \PHPUnit_Framework_TestCase 
{
    /**
     * @var OrderApi
     */
    protected $pxOrder;
    
    public static function setUpBeforeClass()
    {
        if (false == isset($GLOBALS['__PAYUM_PAYEX_ACCOUNT_NUMBER'])) {
            throw new \PHPUnit_Framework_SkippedTestError('Please configure __PAYUM_PAYEX_ACCOUNT_NUMBER in your phpunit.xml');
        }
        if (false == isset($GLOBALS['__PAYUM_PAYEX_ENCRYPTION_KEY'])) {
            throw new \PHPUnit_Framework_SkippedTestError('Please configure __PAYUM_PAYEX_ENCRYPTION_KEY in your phpunit.xml');
        }
    }
    
    public function setUp()
    {
        $this->pxOrder = new OrderApi(
            new SoapClientFactory,
            array(
                'encryptionKey' => $GLOBALS['__PAYUM_PAYEX_ENCRYPTION_KEY'],
                'accountNumber' => $GLOBALS['__PAYUM_PAYEX_ACCOUNT_NUMBER'],
                'sandbox' => true
            )
        );
    }

    /**
     * @test
     *
     * @expectedException \SoapFault
     * @expectedExceptionMessage SOAP-ERROR: Encoding: object has no 'price' property
     */
    public function throwIfTryInitializeWithoutPrice()
    {
        $this->pxOrder->initialize(array());
    }


    /**
     * @test
     *
     * @expectedException \SoapFault
     * @expectedExceptionMessage SOAP-ERROR: Encoding: object has no 'vat' property
     */
    public function throwIfTryInitializeWithoutVat()
    {
        $this->pxOrder->initialize(array(
            'price' => 1000,
        ));
    }

    /**
     * @test
     */
    public function shouldFailedInitializeIfRequiredParametersMissing()
    {
        $result = $this->pxOrder->initialize(array(
            'price' => 1000,
            'priceArgList' => '',
            'vat' => 0,
            'currency' => 'NOK',
        ));

        $this->assertInternalType('array', $result);
        $this->assertArrayNotHasKey('orderRef', $result);
        $this->assertArrayNotHasKey('sessionRef', $result);
        $this->assertArrayNotHasKey('redirectUrl', $result);
        
        $this->assertInternalType('array', $result['status']);

        $this->assertArrayHasKey('code', $result['status']);
        $this->assertNotEmpty($result['status']['code']);
        $this->assertNotEquals('OK', $result['status']['code']);

        $this->assertArrayHasKey('description', $result['status']);
        $this->assertNotEmpty($result['status']['description']);
        $this->assertNotEquals('OK', $result['status']['description']);

        $this->assertArrayHasKey('errorCode', $result['status']);
        $this->assertNotEmpty($result['status']['errorCode']);
        $this->assertNotEquals('OK', $result['status']['errorCode']);
    }
    
    /**
     * @test
     */
    public function shouldSuccessfullyInitializeIfAllRequiredParametersSet()
    {
        $result = $this->pxOrder->initialize(array(
            'price' => 1000,
            'priceArgList' => '',
            'vat' => 0,
            'currency' => 'NOK',
            'orderID' => 123,
            'productNumber' => 123,
            'purchaseOperation' => 'AUTHORIZATION',
            'view' => 'CC',
            'description' => 'a description',
            'additionalValues' => '',
            'returnUrl' => 'http://example.com/a_return_url',
            'cancelUrl' => 'http://example.com/a_cancel_url',
            'externalID' => '',
            'clientIPAddress' => '127.0.0.1',
            'clientIdentifier' => 'USER-AGENT=cli-php',
            'agreementRef' => '',
            'clientLanguage' => 'en-US',
        ));

        $this->assertInternalType('array', $result);
        
        $this->assertArrayHasKey('status', $result);
        $this->assertNotEmpty($result['status']);

        $this->assertArrayHasKey('orderRef', $result);
        $this->assertNotEmpty($result['orderRef']);

        $this->assertArrayHasKey('sessionRef', $result);
        $this->assertNotEmpty($result['sessionRef']);

        $this->assertArrayHasKey('redirectUrl', $result);
        $this->assertNotEmpty($result['redirectUrl']);

        $this->assertInternalType('array', $result['status']);
        
        $this->assertArrayHasKey('code', $result['status']);
        $this->assertSame('OK', $result['status']['code']);

        $this->assertArrayHasKey('description', $result['status']);
        $this->assertSame('OK', $result['status']['description']);

        $this->assertArrayHasKey('errorCode', $result['status']);
        $this->assertSame('OK', $result['status']['errorCode']);   
    }

    /**
     * @test
     */
    public function shouldFailedCompleteIfRequiredParametersMissing()
    {
        $result = $this->pxOrder->complete(array());

        $this->assertInternalType('array', $result);
        $this->assertArrayNotHasKey('transactionStatus', $result);
        $this->assertArrayNotHasKey('transactionNumber', $result);
        $this->assertArrayNotHasKey('orderStatus', $result);

        $this->assertInternalType('array', $result['status']);

        $this->assertArrayHasKey('code', $result['status']);
        $this->assertNotEmpty($result['status']['code']);
        $this->assertNotEquals('OK', $result['status']['code']);

        $this->assertArrayHasKey('description', $result['status']);
        $this->assertNotEmpty($result['status']['description']);
        $this->assertNotEquals('OK', $result['status']['description']);

        $this->assertArrayHasKey('errorCode', $result['status']);
        $this->assertNotEmpty($result['status']['errorCode']);
        $this->assertNotEquals('OK', $result['status']['errorCode']);
    }
}