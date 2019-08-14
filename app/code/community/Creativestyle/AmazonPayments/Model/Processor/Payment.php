<?php
/**
 * This file is part of the official Amazon Pay and Login with Amazon extension
 * for Magento 1.x
 *
 * (c) 2015 - 2017 creativestyle GmbH. All Rights reserved
 *
 * Distribution of the derivatives reusing, transforming or being built upon
 * this software, is not allowed without explicit written permission granted
 * by creativestyle GmbH
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 * @copyright  2015 - 2017 creativestyle GmbH
 * @author     Marek Zabrowarny <ticket@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Model_Processor_Payment
{

    /**
     * Payment info instance
     *
     * @var Mage_Sales_Model_Order_Payment|null
     */
    protected $_payment = null;

    /**
     * Payment store ID
     *
     * @var int|null
     */
    protected $_storeId = null;

    /**
     * Returns API adapter instance
     *
     * @return Creativestyle_AmazonPayments_Model_Api_Advanced
     */
    protected function _getApi()
    {
        return Mage::getSingleton('amazonpayments/api_advanced')->setStore($this->getStoreId());
    }

    /**
     * Process transaction simulation request
     *
     * @param string $transactionType
     * @return string
     */
    protected function _processTransactionStateSimulation($transactionType)
    {
        return Creativestyle_AmazonPayments_Model_Simulator::simulate($this->getPayment(), $transactionType);
    }

    /**
     * Returns payment info instance
     *
     * @return Mage_Sales_Model_Order_Payment
     * @throws Creativestyle_AmazonPayments_Exception
     */
    public function getPayment()
    {
        if (null === $this->_payment) {
            throw new Creativestyle_AmazonPayments_Exception('Payment info object is not set');
        }

        return $this->_payment;
    }

    /**
     * Sets payment info instance for the processing
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return $this
     */
    public function setPayment(Mage_Sales_Model_Order_Payment $payment)
    {
        $this->_payment = $payment;
        return $this;
    }

    /**
     * Returns order's store ID
     *
     * @return int|null
     */
    public function getStoreId()
    {
        return $this->_storeId;
    }

    /**
     * Sets store ID for processing payment
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
        return $this;
    }

    /**
     * @param float $amount
     * @param string $currency
     * @param string $transactionReferenceId
     * @param string $orderId
     * @param string|null $storeName
     * @return OffAmazonPaymentsService_Model_OrderReferenceDetails
     */
    public function setOrderDetails($amount, $currency, $transactionReferenceId, $orderId, $storeName = null)
    {
        $transactionDetails = $this->_getApi()->setOrderReferenceDetails(
            $amount,
            $currency,
            $transactionReferenceId,
            $orderId,
            $storeName
        );
        return $transactionDetails;
    }

    /**
     * @param string $transactionReferenceId
     */
    public function order($transactionReferenceId)
    {
        $this->_getApi()->confirmOrderReference($transactionReferenceId);
        $this->_processTransactionStateSimulation('OrderReference');
    }

    /**
     * @param string $transactionId
     * @param string|null $closureReason
     */
    public function closeOrderReference($transactionId, $closureReason = null)
    {
        $this->_getApi()->closeOrderReference($transactionId, $closureReason);
    }

    /**
     * Authorize order amount on Amazon Payments gateway
     *
     * @param float $amount
     * @param string $currency
     * @param string $transactionReferenceId
     * @param string $parentTransactionId
     * @param bool $isSync
     * @param bool $captureNow
     * @return OffAmazonPaymentsService_Model_AuthorizationDetails
     */
    public function authorize(
        $amount,
        $currency,
        $transactionReferenceId,
        $parentTransactionId,
        $isSync = true,
        $captureNow = false
    ) {
        return $this->_getApi()->authorize(
            $amount,
            $currency,
            $transactionReferenceId,
            $parentTransactionId,
            $isSync ? 0 : null,
            $captureNow,
            $this->_processTransactionStateSimulation('Authorization')
        );
    }

    /**
     * Capture order amount on Amazon Payments gateway
     *
     * @param float $amount
     * @param string $currency
     * @param string $transactionReferenceId
     * @param string $parentTransactionId
     * @return OffAmazonPaymentsService_Model_CaptureDetails
     */
    public function capture($amount, $currency, $transactionReferenceId, $parentTransactionId)
    {
        return $this->_getApi()->capture(
            $amount,
            $currency,
            $transactionReferenceId,
            $parentTransactionId,
            $this->_processTransactionStateSimulation('Capture')
        );
    }

    /**
     * Refund amount on Amazon Payments gateway
     *
     * @param float $amount
     * @param string $currency
     * @param string $transactionReferenceId
     * @param string $parentTransactionId
     * @return OffAmazonPaymentsService_Model_RefundDetails
     */
    public function refund($amount, $currency, $transactionReferenceId, $parentTransactionId)
    {
        return $this->_getApi()->refund(
            $amount,
            $currency,
            $transactionReferenceId,
            $parentTransactionId,
            $this->_processTransactionStateSimulation('Refund')
        );
    }

    /**
     * Cancel order reference on Amazon Payments gateway
     */
    public function cancelOrderReference()
    {
        $orderTransaction = $this->getPayment()
            ->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
        if ($orderTransaction && !$orderTransaction->getIsClosed()) {
            $this->_getApi()->cancelOrderReference($orderTransaction->getTxnId());
            $orderTransaction->setIsClosed(true)->save();
        }
    }
}
