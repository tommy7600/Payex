<?php
namespace Payum\Payex\Action;

use Payum\Action\ActionInterface;
use Payum\Bridge\Spl\ArrayObject;
use Payum\Exception\RequestNotSupportedException;
use Payum\Request\StatusRequestInterface;
use Payum\Payex\Api\OrderApi;

class PaymentDetailsStatusAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request StatusRequestInterface */
        if (false == $this->supports($request)) {
            throw RequestNotSupportedException::createActionNotSupported($this, $request);
        }

        $model = ArrayObject::ensureArrayObject($request->getModel());
        
        //TODO: It may be not correct for all cases. This does NOT indicate wether the transaction requested was successful, only wether the request was carried out successfully.
        if ($model['errorCode'] && OrderApi::ERRORCODE_OK != $model['errorCode']) {
            $request->markFailed();
            
            return;
        }

        if (null === $model['orderStatus']) {
            $request->markNew();

            return;
        }

        //A purchase has been done, but check the transactionStatus to see the result
        if (OrderApi::ORDERSTATUS_COMPLETED == $model['orderStatus']) {
            if (OrderApi::TRANSACTIONSTATUS_CANCEL == $model['transactionStatus']) {
                $request->markCanceled();

                return;
            }

            if (OrderApi::TRANSACTIONSTATUS_FAILURE == $model['transactionStatus']) {
                $errorDetails = $model['errorDetails'];
                if (
                    isset($errorDetails['transactionErrorCode']) && 
                    $errorDetails['transactionErrorCode'] == OrderApi::TRANSACTIONERRORCODE_OPERATIONCANCELLEDBYCUSTOMER
                ) {
                    $request->markCanceled();

                    return;
                }
                
                $request->markFailed();

                return;
            }
            
            //If you are running 2-phase transactions, you should check that the node transactionStatus contains 3 (authorize)
            if (OrderApi::PURCHASEOPERATION_AUTHORIZATION == $model['purchaseOperation']) {
                if (OrderApi::TRANSACTIONSTATUS_AUTHORIZE == $model['transactionStatus']) {
                    $request->markSuccess();
    
                    return;
                }

                //Anything else indicates that the transaction has failed or is still processing
                $request->markFailed();
            
                return;
            }
            
            //If you are running 1-phase transactions, you should check that the node transactionStatus contains 0 (sale)
            if (OrderApi::PURCHASEOPERATION_SALE == $model['purchaseOperation']) {
                if (is_numeric($model['transactionStatus']) && OrderApi::TRANSACTIONSTATUS_SALE == $model['transactionStatus']) {
                    $request->markSuccess();

                    return;
                }

                //Anything else indicates that the transaction has failed or is still processing
                $request->markFailed();

                return;
            }
            
            $request->markUnknown();
            
            return;
        }

        if (OrderApi::ORDERSTATUS_PROCESSING == $model['orderStatus']) {
            $request->markPending();

            return;
        }

        //PxOrder.Complete can return orderStatus 1 for 2 weeks after PxOrder.Initialize is called. Afterwards the orderStatus will be set to 2
        if (OrderApi::ORDERSTATUS_NOT_FOUND == $model['orderStatus']) {
            $request->markExpired();

            return;
        }

        $request->markUnknown();
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return 
            $request instanceof StatusRequestInterface &&
            $request->getModel() instanceof \ArrayAccess &&
            //Make sure it is payment. Apparently an agreement does not have this field.
            $request->getModel()->offsetExists('orderId')
        ;
    }
}