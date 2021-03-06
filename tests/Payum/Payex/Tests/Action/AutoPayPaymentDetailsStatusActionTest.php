<?php
namespace Payum\Payex\Tests\Action;

use Payum\PaymentInterface;
use Payum\Request\BinaryMaskStatusRequest;
use Payum\Payex\Action\AutoPayPaymentDetailsStatusAction;
use Payum\Payex\Api\OrderApi;
use Payum\Payex\Model\PaymentDetails;
use Payum\Payex\Model\AgreementDetails;

class AutoPayPaymentDetailsStatusActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldImplementActionInterface()
    {
        $rc = new \ReflectionClass('Payum\Payex\Action\AutoPayPaymentDetailsStatusAction');

        $this->assertTrue($rc->isSubclassOf('Payum\Action\ActionInterface'));
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments()
    {
        new AutoPayPaymentDetailsStatusAction;
    }

    /**
     * @test
     */
    public function shouldSupportBinaryMaskStatusRequestWithArrayAsModelIfAutoPaySetToTrue()
    {
        $action = new AutoPayPaymentDetailsStatusAction();

        $this->assertTrue($action->supports(new BinaryMaskStatusRequest(array(
            'autoPay' => true
        ))));
    }

    /**
     * @test
     */
    public function shouldNotSupportBinaryMaskStatusRequestWithArrayAsModelIfAutoPayNotSet()
    {
        $action = new AutoPayPaymentDetailsStatusAction();

        $this->assertFalse($action->supports(new BinaryMaskStatusRequest(array())));
    }

    /**
     * @test
     */
    public function shouldNotSupportBinaryMaskStatusRequestWithArrayAsModelIfAutoPaySetToTrueAndRecurringSetToTrue()
    {
        $action = new AutoPayPaymentDetailsStatusAction();

        $this->assertFalse($action->supports(new BinaryMaskStatusRequest(array(
            'autoPay' => true,
            'recurring' => true,
        ))));
    }

    /**
     * @test
     */
    public function shouldNotSupportBinaryMaskStatusRequestWithArrayAsModelIfAutoPaySetToFalse()
    {
        $action = new AutoPayPaymentDetailsStatusAction();

        $this->assertFalse($action->supports(new BinaryMaskStatusRequest(array(
            'autoPay' => false
        ))));
    }

    /**
     * @test
     */
    public function shouldSupportBinaryMaskStatusRequestWithPaymentDetailsAsModelIfAutoPaySetToTrue()
    {
        $action = new AutoPayPaymentDetailsStatusAction;

        $details = new PaymentDetails;
        $details->setAutoPay(true);

        $this->assertTrue($action->supports(new BinaryMaskStatusRequest($details)));
    }

    /**
     * @test
     */
    public function shouldNotSupportBinaryMaskStatusRequestWithPaymentDetailsAsModelIfAutoPaySetToTrueAndRecurringSetToTrue()
    {
        $action = new AutoPayPaymentDetailsStatusAction;

        $details = new PaymentDetails;
        $details->setAutoPay(true);
        $details->setRecurring(true);

        $this->assertFalse($action->supports(new BinaryMaskStatusRequest($details)));
    }

    /**
     * @test
     */
    public function shouldNotSupportBinaryMaskStatusRequestWithAgreementDetailsAsModel()
    {
        $action = new AutoPayPaymentDetailsStatusAction;

        $details = new AgreementDetails;

        $this->assertFalse($action->supports(new BinaryMaskStatusRequest($details)));
    }

    /**
     * @test
     */
    public function shouldNotSupportAnythingNotBinaryMaskStatusRequest()
    {
        $action = new AutoPayPaymentDetailsStatusAction;

        $this->assertFalse($action->supports(new \stdClass()));
    }

    /**
     * @test
     */
    public function shouldNotSupportBinaryMaskStatusRequestWithNotArrayAccessModel()
    {
        $action = new AutoPayPaymentDetailsStatusAction;

        $this->assertFalse($action->supports(new BinaryMaskStatusRequest(new \stdClass)));
    }

    /**
     * @test
     *
     * @expectedException \Payum\Exception\RequestNotSupportedException
     */
    public function throwIfNotSupportedRequestGivenAsArgumentForExecute()
    {
        $action = new AutoPayPaymentDetailsStatusAction;

        $action->execute(new \stdClass());
    }


    /**
     * @test
     */
    public function shouldMarkNewIfTransactionStatusNotSet()
    {
        $action = new AutoPayPaymentDetailsStatusAction();

        $status = new BinaryMaskStatusRequest(array(
            'autoPay' => true,
        ));

        //guard
        $status->markUnknown();

        $action->execute($status);

        $this->assertTrue($status->isNew());
    }

    /**
     * @test
     */
    public function shouldMarkSuccessIfPurchaseOperationAuthorizeAndTransactionStatusThree()
    {
        $action = new AutoPayPaymentDetailsStatusAction();

        $status = new BinaryMaskStatusRequest(array(
            'purchaseOperation' => OrderApi::PURCHASEOPERATION_AUTHORIZATION,
            'transactionStatus' => OrderApi::TRANSACTIONSTATUS_AUTHORIZE,
            'autoPay' => true,
        ));

        //guard
        $status->markUnknown();

        $action->execute($status);

        $this->assertTrue($status->isSuccess());
    }

    /**
     * @test
     */
    public function shouldMarkSuccessIfPurchaseOperationSaleAndTransactionStatusZero()
    {
        $action = new AutoPayPaymentDetailsStatusAction();

        $status = new BinaryMaskStatusRequest(array(
            'purchaseOperation' => OrderApi::PURCHASEOPERATION_SALE,
            'transactionStatus' => OrderApi::TRANSACTIONSTATUS_SALE,
            'autoPay' => true,
        ));

        //guard
        $status->markUnknown();

        $action->execute($status);

        $this->assertTrue($status->isSuccess());
    }

    /**
     * @test
     */
    public function shouldMarkFailedIfTransactionStatusNeitherZeroOrThree()
    {
        $action = new AutoPayPaymentDetailsStatusAction();

        $status = new BinaryMaskStatusRequest(array(
            'purchaseOperation' => OrderApi::PURCHASEOPERATION_AUTHORIZATION,
            'transactionStatus' => 'foobarbaz',
            'autoPay' => true,
        ));

        //guard
        $status->markUnknown();

        $action->execute($status);

        $this->assertTrue($status->isFailed());
    }
    
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PaymentInterface
     */
    protected function createPaymentMock()
    {
        return $this->getMock('Payum\PaymentInterface');
    }
}