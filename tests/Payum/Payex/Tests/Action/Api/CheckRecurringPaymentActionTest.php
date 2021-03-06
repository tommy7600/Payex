<?php
namespace Payum\Payex\Tests\Action\Api;

use Payum\Payex\Action\Api\CheckRecurringPaymentAction;
use Payum\Payex\Api\RecurringApi;
use Payum\Payex\Request\Api\CheckRecurringPaymentRequest;
use Payum\Payex\Model\PaymentDetails;

class CheckRecurringPaymentActionTest extends \PHPUnit_Framework_TestCase
{
    protected $requiredFields = array(
        'agreementRef' => 'aRef',
    );
    
    public function provideRequiredFields()
    {
        $fields = array();
        
        foreach ($this->requiredFields as $name => $value) {
            $fields[] = array($name);
        }

        return $fields;
    }
    
    /**
     * @test
     */
    public function shouldImplementActionInterface()
    {
        $rc = new \ReflectionClass('Payum\Payex\Action\Api\CheckRecurringPaymentAction');

        $this->assertTrue($rc->isSubclassOf('Payum\Action\ActionInterface'));
    }

    /**
     * @test
     */
    public function shouldImplementApiAwareInterface()
    {
        $rc = new \ReflectionClass('Payum\Payex\Action\Api\CheckRecurringPaymentAction');

        $this->assertTrue($rc->isSubclassOf('Payum\ApiAwareInterface'));
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments()
    {
        new CheckRecurringPaymentAction;
    }

    /**
     * @test
     */
    public function shouldAllowSetRecurringApiAsApi()
    {
        $recurringApi = $this->getMock('Payum\Payex\Api\RecurringApi', array(), array(), '', false);
        
        $action = new CheckRecurringPaymentAction;

        $action->setApi($recurringApi);
        
        $this->assertAttributeSame($recurringApi, 'api', $action);
    }

    /**
     * @test
     * 
     * @expectedException \Payum\Exception\UnsupportedApiException
     * @expectedExceptionMessage Expected api must be instance of RecurringApi.
     */
    public function throwOnTryingSetNotRecurringApiAsApi()
    {
        $action = new CheckRecurringPaymentAction;

        $action->setApi(new \stdClass);
    }

    /**
     * @test
     */
    public function shouldSupportCheckRecurringPaymentRequestWithArrayAccessAsModel()
    {
        $action = new CheckRecurringPaymentAction();

        $this->assertTrue($action->supports(new CheckRecurringPaymentRequest($this->getMock('ArrayAccess'))));
    }

    /**
     * @test
     */
    public function shouldSupportCheckRecurringPaymentRequestWithPaymentDetailsAsModel()
    {
        $action = new CheckRecurringPaymentAction;
        
        $this->assertTrue($action->supports(new CheckRecurringPaymentRequest(new PaymentDetails)));
    }

    /**
     * @test
     */
    public function shouldNotSupportAnythingNotCheckRecurringPaymentRequest()
    {
        $action = new CheckRecurringPaymentAction;

        $this->assertFalse($action->supports(new \stdClass()));
    }

    /**
     * @test
     */
    public function shouldNotSupportCheckRecurringPaymentRequestWithNotArrayAccessModel()
    {
        $action = new CheckRecurringPaymentAction;

        $this->assertFalse($action->supports(new CheckRecurringPaymentRequest(new \stdClass)));
    }

    /**
     * @test
     *
     * @expectedException \Payum\Exception\RequestNotSupportedException
     */
    public function throwIfNotSupportedRequestGivenAsArgumentForExecute()
    {
        $action = new CheckRecurringPaymentAction($this->createApiMock());

        $action->execute(new \stdClass());
    }

    /**
     * @test
     * 
     * @dataProvider provideRequiredFields
     * 
     * @expectedException \Payum\Exception\LogicException
     */
    public function throwIfTryInitializeWithRequiredFieldNotPresent($requiredField)
    {
        unset($this->requiredFields[$requiredField]);

        $action = new CheckRecurringPaymentAction();

        $action->execute(new CheckRecurringPaymentRequest($this->requiredFields));
    }

    /**
     * @test
     */
    public function shouldCheckRecurringPayment()
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('check')
            ->with($this->requiredFields)
            ->will($this->returnValue(array(
                'recurringStatus' => RecurringApi::RECURRINGSTATUS_STOPPEDBYCLIENT,
            )));
        ;

        $action = new CheckRecurringPaymentAction();
        $action->setApi($apiMock);

        $request = new CheckRecurringPaymentRequest($this->requiredFields);
        
        $action->execute($request);

        $model = $request->getModel();
        $this->assertEquals(RecurringApi::RECURRINGSTATUS_STOPPEDBYCLIENT, $model['recurringStatus']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Payum\Payex\Api\RecurringApi
     */
    protected function createApiMock()
    {
        return $this->getMock('Payum\Payex\Api\RecurringApi', array(), array(), '', false);
    }
}