<?php

/**
 * This file is part of the official Amazon Payments Advanced extension
 * for Magento (c) creativestyle GmbH <amazon@creativestyle.de>
 * All rights reserved
 *
 * Reuse or modification of this source code is not allowed
 * without written permission from creativestyle GmbH
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 * @copyright  Copyright (c) 2014 - 2015 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */
abstract class Creativestyle_AmazonPayments_Model_Payment_Abstract extends Mage_Payment_Model_Method_Abstract {

    const ACTION_MANUAL                         = 'manual';
    const ACTION_AUTHORIZE                      = 'authorize';
    const ACTION_AUTHORIZE_CAPTURE              = 'authorize_capture';
    const ACTION_ERP                            = 'erp';

    const TRANSACTION_STATE_DRAFT               = 'Draft';
    const TRANSACTION_STATE_PENDING             = 'Pending';
    const TRANSACTION_STATE_OPEN                = 'Open';
    const TRANSACTION_STATE_SUSPENDED           = 'Suspended';
    const TRANSACTION_STATE_DECLINED            = 'Declined';
    const TRANSACTION_STATE_COMPLETED           = 'Completed';
    const TRANSACTION_STATE_CANCELED            = 'Canceled';
    const TRANSACTION_STATE_CLOSED              = 'Closed';

    const TRANSACTION_REASON_INVALID_PAYMENT    = 'InvalidPaymentMethod';
    const TRANSACTION_REASON_TIMEOUT            = 'TransactionTimedOut';
    const TRANSACTION_REASON_AMAZON_REJECTED    = 'AmazonRejected';

    const TRANSACTION_STATE_KEY                 = 'State';
    const TRANSACTION_REASON_KEY                = 'ReasonCode';

    const CHECK_USE_FOR_COUNTRY                 = 1;
    const CHECK_USE_FOR_CURRENCY                = 2;
    const CHECK_USE_CHECKOUT                    = 4;
    const CHECK_USE_FOR_MULTISHIPPING           = 8;
    const CHECK_USE_INTERNAL                    = 16;
    const CHECK_ORDER_TOTAL_MIN_MAX             = 32;
    const CHECK_RECURRING_PROFILES              = 64;
    const CHECK_ZERO_TOTAL                      = 128;

    protected $_code                            = 'amazonpayments_abstract';
    protected $_infoBlockType                   = 'amazonpayments/payment_info';

    /**
     * Pay with Amazon method features
     *
     * @var bool
     */
    protected $_isGateway                   = false;
    protected $_canOrder                    = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = false;
    protected $_canCaptureOnce              = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;
    protected $_canUseInternal              = false;
    protected $_canUseCheckout              = false;
    protected $_canUseForMultishipping      = false;
    protected $_isInitializeNeeded          = true;
    protected $_canFetchTransactionInfo     = true;
    protected $_canReviewPayment            = false;
    protected $_canCreateBillingAgreement   = false;
    protected $_canManageRecurringProfiles  = true;

    /**
     * Return Amazon Payments config model instance
     *
     * @return Creativestyle_AmazonPayments_Model_Config
     */
    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    /**
     * Return Amazon Payments processor instance
     *
     * @return Creativestyle_AmazonPayments_Model_Processor
     */
    protected function _getPaymentProcessor() {
        return Mage::getSingleton('amazonpayments/processor');
    }

    /**
     * @param Varien_Object $payment
     *
     * @return Creativestyle_AmazonPayments_Model_Payment_Abstract
     */
    protected function _initInfoInstance($payment) {
        if (!$this->hasInfoInstance()) {
            $this->setInfoInstance($payment);
        }
        if ($payment->getOrder() && null === $this->getStore()) {
            $this->setStore($payment->getOrder()->getStoreId());
        }
        return $this;
    }

    /**
     * @param Varien_Object $stateObject
     * @param Varien_Object $order
     *
     * @return Creativestyle_AmazonPayments_Model_Payment_Abstract
     */
    protected function _initStateObject(&$stateObject = null, $order = null) {
        if (null === $stateObject) {
            $stateObject = new Varien_Object();
        }
        $stateObject->setData(array(
            'state' => $order ? $order->getState() : Mage_Sales_Model_Order::STATE_NEW,
            'status' => $order ? $order->getStatus() : $this->_getConfig()->getNewOrderStatus($this->getStore()),
            'is_notified' => Mage_Sales_Model_Order_Status_History::CUSTOMER_NOTIFICATION_NOT_APPLICABLE
        ));
        return $this;
    }

    protected function _getPaymentSequenceId() {
        $sequenceNumber = $this->getInfoInstance()->getAdditionalInformation('amazon_sequence_number');
        $sequenceNumber = is_null($sequenceNumber) ? 1 : ++$sequenceNumber;
        $this->getInfoInstance()->setAdditionalInformation('amazon_sequence_number', $sequenceNumber);
        return sprintf('%s-%s', $this->getInfoInstance()->getOrder()->getExtOrderId(), $sequenceNumber);
    }

    /**
     * @param array $transactionStatus
     * @param array $allowedTransactionStates
     *
     * @return bool|array
     */
    protected function _validateTransactionStatus($transactionStatus, $allowedTransactionStates) {
        if (!is_array($transactionStatus)) return false;
        if (!array_key_exists(self::TRANSACTION_STATE_KEY, $transactionStatus)) return false;
        if (!in_array($transactionStatus[self::TRANSACTION_STATE_KEY], $allowedTransactionStates)) {
            return false;
        }
        return $transactionStatus;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param array $transactionStatus
     * @param Varien_Object $stateObject
     * @param float $amount
     * @param bool $initialRequest
     *
     * @return Creativestyle_AmazonPayments_Model_Payment_Abstract
     */
    protected function _mapTransactionStatus($transaction, $transactionStatus, $stateObject, $amount, $initialRequest = false) {
        switch ($transaction->getTxnType()) {
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER:
                switch ($transactionStatus[self::TRANSACTION_STATE_KEY]) {
                    case self::TRANSACTION_STATE_OPEN:
                        $this->_initStateObject($stateObject);
                        break; // self::TRANSACTION_STATE_OPEN

                    case self::TRANSACTION_STATE_CANCELED:
                    case self::TRANSACTION_STATE_CLOSED:
                        $this->getInfoInstance()->getOrder()->addRelatedObject($transaction->setIsClosed(true));
                        break; // self::TRANSACTION_STATE_CANCELED / self::TRANSACTION_STATE_CLOSED
                }
                $message = $initialRequest ?
                    'An order of %s has been sent to Amazon Payments (%s). The current status is %s.' :
                    'An order of %s has been processed by Amazon Payments (%s). The new status is %s.';
                break; // Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER

            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH:
                switch ($transactionStatus[self::TRANSACTION_STATE_KEY]) {
                    case self::TRANSACTION_STATE_OPEN:
                        $stateObject->setData(array(
                            'state' => Mage_Sales_Model_Order::STATE_PROCESSING,
                            'status' => $this->_getConfig()->getAuthorizedOrderStatus($this->getStore())
                        ));
                        break; // self::TRANSACTION_STATE_OPEN

                    case self::TRANSACTION_STATE_DECLINED:
                        $stateObject->setData(array(
                            'hold_before_state' => $stateObject->getState(),
                            'hold_before_status' => $stateObject->getStatus(),
                            'state' => Mage_Sales_Model_Order::STATE_HOLDED,
                            'status' => $this->_getConfig()->getHoldedOrderStatus($this->getStore())
                        ));
                        break; // self::TRANSACTION_STATE_DECLINED
                }
                $message = $initialRequest ?
                    'An authorize request for %s has been submitted to Amazon Payments (%s). The current status is %s.' :
                    'An authorization of %s has been processed by Amazon Payments (%s). The new status is %s.';
                break; // Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH

            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE:
                switch ($transactionStatus[self::TRANSACTION_STATE_KEY]) {
                    case self::TRANSACTION_STATE_DECLINED:
                        $stateObject->setData(array(
                            'hold_before_state' => $stateObject->getState(),
                            'hold_before_status' => $stateObject->getStatus(),
                            'state' => Mage_Sales_Model_Order::STATE_HOLDED,
                            'status' => $this->_getConfig()->getHoldedOrderStatus($this->getStore())
                        ));
                        break; // self::TRANSACTION_STATE_DECLINED
                    case self::TRANSACTION_STATE_COMPLETED:
                        $this->getInfoInstance()->getOrder()->addRelatedObject($transaction->setIsClosed(true));
                        break; // self::TRANSACTION_STATE_COMPLETED
                }
                $message = $initialRequest ?
                    'A capture request for %s has been submitted to Amazon Payments (%s). The current status is %s.' :
                    'A capture of %s has been processed by Amazon Payments (%s). The new status is %s.';
                break; // Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE

            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND:
                $message = $initialRequest ?
                    'A refund request for %s has been submitted to Amazon Payments (%s). The current status is %s.' :
                    'A refund of %s has been processed by Amazon Payments (%s). The new status is %s.';
                break; // Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND

            default:
                return $this;
        }

        $stateObject->setMessage(Mage::helper('amazonpayments')->__($message,
            $this->getInfoInstance()->getOrder()->getBaseCurrency()->formatTxt($amount),
            $transaction->getTxnId(),
            sprintf('<strong>%s</strong>', strtoupper($transactionStatus[self::TRANSACTION_STATE_KEY]))
        ));

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param array $transactionStatus
     * @param Varien_Object $stateObject
     *
     * @return Creativestyle_AmazonPayments_Model_Payment_Abstract
     */
    protected function _sendTransactionEmails($transaction, $transactionStatus, $stateObject) {
        switch ($transaction->getTxnType()) {
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH:
                switch ($transactionStatus[self::TRANSACTION_STATE_KEY]) {
                    case self::TRANSACTION_STATE_OPEN:
                        if ($this->getInfoInstance()->getOrder() && !$this->getInfoInstance()->getOrder()->getEmailSent() && $this->_getConfig()->sendEmailConfirmation($this->getStore())) {
                            $this->getInfoInstance()->getOrder()->sendNewOrderEmail();
                            $stateObject->setIsNotified(true);
                        }
                        break;
                    case self::TRANSACTION_STATE_DECLINED:
                        if ($this->getInfoInstance()->getOrder() && $transactionStatus[self::TRANSACTION_REASON_KEY] == 'InvalidPaymentMethod') {
                            Mage::helper('amazonpayments')->sendAuthorizationDeclinedEmail($this->getInfoInstance());
                            $stateObject->setIsNotified(true);
                        }
                        break;
                }
                break; // Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH

            default:
                return $this;
        }

        return $this;
    }

    /**
     * @param Varien_Object $stateObject
     *
     * @return Creativestyle_AmazonPayments_Model_Payment_Abstract
     */
    protected function _updateOrderStatus($stateObject) {
        $this->getInfoInstance()->getOrder()
            ->setHoldBeforeState($stateObject->getHoldBeforeState() ? $stateObject->getHoldBeforeState() : null)
            ->setHoldBeforeStatus($stateObject->getHoldBeforeStatus() ? $stateObject->getHoldBeforeStatus() : null)
            ->setState(
                $stateObject->getState(),
                $stateObject->getStatus(),
                $stateObject->getMessage(),
                $stateObject->getIsNotified()
            );
        return $this;
    }

    public function saveOrder($order) {
        if ($order->hasDataChanges()) {
            $order->save();
        }
        return $this;
    }

    /**
     * Check authorise availability
     *
     * @return bool
     */
    public function canAuthorize() {
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canAuthorize();
    }

    /**
     * Check capture availability
     *
     * @return bool
     */
    public function canCapture() {
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canCapture();
    }

    /**
     * Check partial capture availability
     *
     * @return bool
     */
    public function canCapturePartial() {
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canCapturePartial();
    }

    /**
     * Check whether capture can be performed once and no further capture possible
     *
     * @return bool
     */
    public function canCaptureOnce() {
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canCaptureOnce();
    }

    /**
     * Check refund availability
     *
     * @return bool
     */
    public function canRefund() {
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canRefund();
    }

    /**
     * Check partial refund availability for invoice
     *
     * @return bool
     */
    public function canRefundPartialPerInvoice() {
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canRefundPartialPerInvoice();
    }

    /**
     * Check void availability
     *
     * @param   Varien_Object $payment
     * @return  bool
     */
    public function canVoid(Varien_Object $payment) {
        $this->_initInfoInstance($payment);
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canVoid($payment);
    }

    /**
     * Can be edit order (renew order)
     *
     * @return bool
     */
    public function canEdit() {
        return false;
    }

    /**
     * Check fetch transaction info availability
     *
     * @return bool
     */
    public function canFetchTransactionInfo() {
        if (!$this->_getConfig()->isPaymentProcessingAllowed($this->getStore())) {
            return false;
        }
        return parent::canFetchTransactionInfo();
    }

    /**
     * Fetch transaction info
     *
     * @param Mage_Payment_Model_Info $payment
     * @param string $transactionId
     * @param bool $saveOrder
     *
     * @return array
     */
    public function fetchTransactionInfo(Mage_Payment_Model_Info $payment, $transactionId, $shouldSave = true) {
        $this->_initInfoInstance($payment);
        if ($transaction = $payment->lookupTransaction($transactionId)) {
            $transactionInfo = $this->_getPaymentProcessor()->importTransactionDetails($payment, $transaction);
            if ($transactionInfo) {
                $this->_initStateObject($stateObject, $payment->getOrder())
                     ->_mapTransactionStatus($transaction, $transactionInfo, $stateObject, $payment->getOrder()->getBaseTotalDue())
                     ->_sendTransactionEmails($transaction, $transactionInfo, $stateObject)
                     ->_updateOrderStatus($stateObject);
                if ($shouldSave) $this->saveOrder($payment->getOrder());
            }
            return $transactionInfo;
        }
        throw new Creativestyle_AmazonPayments_Exception('Transaction not found');
    }

    /**
     * Check method for processing with base currency
     *
     * @param string $currencyCode
     * @return boolean
     */
    public function canUseForCurrency($currencyCode) {
        return true;
    }

    /**
     * Payment order
     *
     * @param float $amount
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     *
     * @return array|null
     */
    protected function _order($amount, &$transaction = null) {
        if (!$this->canOrder()) {
            throw new Creativestyle_AmazonPayments_Exception('Order action is not available');
        }
        if ($this->getInfoInstance()->getSkipOrderReferenceProcessing()) {
            $this->_getPaymentProcessor()->orderConfirm($this->getInfoInstance(), $this->getInfoInstance()->getTransactionId());
        } else {
            $this->_getPaymentProcessor()->order($this->getInfoInstance(), $amount, $this->getInfoInstance()->getTransactionId());
        }
        $transaction = $this->getInfoInstance()->setIsTransactionClosed(false)
            ->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
        return $this->_getPaymentProcessor()->importTransactionDetails($this->getInfoInstance(), $transaction);
    }

    /**
     * Payment authorize
     *
     * @param float $amount
     * @param string $parentTransactionId
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param bool $captureNow
     *
     * @return array|null
     */
    protected function _authorize($amount, $parentTransactionId, &$transaction = null, $captureNow = false) {
        if (!$this->canAuthorize()) {
            throw new Creativestyle_AmazonPayments_Exception('Authorize action is not available');
        }
        $authorizationDetails = $this->_getPaymentProcessor()->authorize(
            $this->getInfoInstance(),
            $amount,
            $this->_getPaymentSequenceId(),
            $parentTransactionId,
            $captureNow
        );
        $transaction = $this->getInfoInstance()->setIsTransactionClosed(false)
            ->setTransactionId($authorizationDetails->getAmazonAuthorizationId())
            ->setParentTransactionId($parentTransactionId)
            ->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        return $this->_getPaymentProcessor()->importTransactionDetails($this->getInfoInstance(), $transaction);
    }

    /**
     * Payment capture
     *
     * @param float $amount
     * @param string $parentTransactionId
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     *
     * @return array|null
     */
    protected function _capture($amount, $parentTransactionId, &$transaction = null) {
        if (!$this->canCapture()) {
            throw new Creativestyle_AmazonPayments_Exception('Capture action is not available');
        }
        $captureDetails = $this->_getPaymentProcessor()->capture(
            $this->getInfoInstance(),
            $amount,
            $this->_getPaymentSequenceId(),
            $parentTransactionId
        );
        $transaction = $this->getInfoInstance()->setIsTransactionClosed(false)
            ->setTransactionId($captureDetails->getAmazonCaptureId())
            ->setParentTransactionId($parentTransactionId)
            ->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
        return $this->_getPaymentProcessor()->importTransactionDetails($this->getInfoInstance(), $transaction, $captureDetails);
    }

    /**
     * Public wrapper for payment order
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Creativestyle_AmazonPayments_Model_Payment_Abstract
     */
    public function order(Varien_Object $payment, $amount) {
        $this->_initInfoInstance($payment);

        $orderReferenceStatus = $this->_validateTransactionStatus(
            $this->_order($amount, $orderReferenceTransaction),
            array(self::TRANSACTION_STATE_OPEN)
        );
        if (!$orderReferenceStatus) {
            throw new Creativestyle_AmazonPayments_Exception('Invalid OrderReference status returned by Amazon Payments API.');
        }

        $this->_initStateObject($stateObject)
             ->_mapTransactionStatus($orderReferenceTransaction, $orderReferenceStatus, $stateObject, $amount, true)
             ->_sendTransactionEmails($orderReferenceTransaction, $orderReferenceStatus, $stateObject)
             ->_updateOrderStatus($stateObject);

        return $this;
    }

    /**
     * Payment authorization public method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Creativestyle_AmazonPayments_Model_Payment_Abstract
     */
    public function authorize(Varien_Object $payment, $amount) {
        $this->_initInfoInstance($payment);

        if ($orderReferenceTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER)) {
            $authorizationStatus = $this->_authorize($amount, $orderReferenceTransaction->getTxnId(), $authorizationTransaction, $this->getConfigData('payment_action') == self::ACTION_AUTHORIZE_CAPTURE);
            if (!$this->_validateTransactionStatus($authorizationStatus, array(self::TRANSACTION_STATE_PENDING, self::TRANSACTION_STATE_OPEN))) {
                if (is_array($authorizationStatus) && array_key_exists(self::TRANSACTION_STATE_KEY, $authorizationStatus)
                    && $authorizationStatus[self::TRANSACTION_STATE_KEY] == self::TRANSACTION_STATE_DECLINED
                    && array_key_exists(self::TRANSACTION_REASON_KEY, $authorizationStatus))
                {
                    switch ($authorizationStatus[self::TRANSACTION_REASON_KEY]) {
                        case self::TRANSACTION_REASON_INVALID_PAYMENT:
                            throw new Creativestyle_AmazonPayments_Exception_InvalidStatus_Recoverable('Invalid Authorization status returned by Amazon Payments API.');
                        case self::TRANSACTION_REASON_AMAZON_REJECTED:
                        case self::TRANSACTION_REASON_TIMEOUT:
                            throw new Creativestyle_AmazonPayments_Exception_InvalidStatus('Invalid Authorization status returned by Amazon Payments API.');
                    }

                }
                throw new Creativestyle_AmazonPayments_Exception('Invalid Authorization status returned by Amazon Payments API.');
            }

            $this->_initStateObject($stateObject, $payment->getOrder())
                 ->_mapTransactionStatus($authorizationTransaction, $authorizationStatus, $stateObject, $amount, true)
                 ->_sendTransactionEmails($authorizationTransaction, $authorizationStatus, $stateObject)
                 ->_updateOrderStatus($stateObject);
        }

        return $this;
    }

    /**
     * Payment capture public method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount) {
        $this->_initInfoInstance($payment);

        if ($authorizationTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH)) {
            $captureStatus = $this->_validateTransactionStatus(
                $this->_capture($amount, $authorizationTransaction->getTxnId(), $captureTransaction),
                array(self::TRANSACTION_STATE_COMPLETED)
            );
            if (!$captureStatus) {
                throw new Creativestyle_AmazonPayments_Exception('Amazon Payments API returned such a Capture status that further payment processing is not allowed.');
            }
            $this->_initStateObject($stateObject, $payment->getOrder())
                 ->_mapTransactionStatus($captureTransaction, $captureStatus, $stateObject, $amount, true)
                 ->_sendTransactionEmails($captureTransaction, $captureStatus, $stateObject)
                 ->_updateOrderStatus($stateObject)
                 ->saveOrder($payment->getOrder());

            // avoid transaction duplicates
            $payment->setSkipTransactionCreation(true);
        }

        return $this;
    }

    /**
     * @todo
     * Set capture transaction ID to invoice for informational purposes
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function processInvoice($invoice, $payment) {
        $invoice->setTransactionId($payment->getLastTransId());
        return $this;
    }

    /**
     * @todo
     * Refund specified amount for payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount) {
        $this->_initInfoInstance($payment);
        if (!$this->canRefund()) {
            throw new Creativestyle_AmazonPayments_Exception('Refund action is not available');
        }
        return $this;
    }

    /**
     * @todo
     * Set transaction ID into creditmemo for informational purposes
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param Mage_Sales_Model_Order_Payment $payment
     * @return Mage_Payment_Model_Method_Abstract
     */
    public function processCreditmemo($creditmemo, $payment) {
        $creditmemo->setTransactionId($payment->getLastTransId());
        return $this;
    }

    /**
     * @todo
     * Cancel payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function cancel(Varien_Object $payment) {
        $this->_initInfoInstance($payment);
        return $this;
    }

    /**
     * @todo
     * Void payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function void(Varien_Object $payment) {
        $this->_initInfoInstance($payment);
        if (!$this->canVoid($payment)) {
            throw new Creativestyle_AmazonPayments_Exception('Void action is not available');
        }
        return $this;
    }

    /**
     * Modified payment configuration retriever
     *
     * @param string $field
     * @param int|string|null|Mage_Core_Model_Store $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null) {
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        switch ($field) {
            case 'payment_action':
                return $this->_getConfig()->getPaymentAction($storeId);
            default:
                return parent::getConfigData($field, $storeId);
        }
    }

    /**
     * Method that will be executed instead of authorize or capture
     * if flag isInitializeNeeded set to true
     *
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return Creativestyle_AmazonPayments_Model_Payment_Abstract
     */
    public function initialize($paymentAction, $stateObject) {
        $payment = $this->getInfoInstance();
        $this->setStore($payment->getOrder()->getStoreId());
        $this->_initStateObject($stateObject);
        switch ($paymentAction) {
            case self::ACTION_MANUAL:

                // OrderReference confirm, throw an exception for invalid statuses
                $orderReferenceStatus = $this->_validateTransactionStatus(
                    $this->_order($this->getInfoInstance()->getOrder()->getBaseTotalDue(), $orderReferenceTransaction),
                    array(self::TRANSACTION_STATE_OPEN)
                );
                if (!$orderReferenceStatus) {
                    throw new Creativestyle_AmazonPayments_Exception('Invalid Order Reference status returned by Amazon Payments API.');
                }

                $this->_mapTransactionStatus($orderReferenceTransaction, $orderReferenceStatus, $stateObject, $this->getInfoInstance()->getOrder()->getBaseTotalDue(), true)
                     ->_sendTransactionEmails($orderReferenceTransaction, $orderReferenceStatus, $stateObject)
                     ->_updateOrderStatus($stateObject);

                break;

            case self::ACTION_AUTHORIZE:
            case self::ACTION_AUTHORIZE_CAPTURE:

                // OrderReference confirm, throw an exception if invalid status is returned
                $orderReferenceStatus = $this->_validateTransactionStatus(
                    $this->_order($this->getInfoInstance()->getOrder()->getBaseTotalDue(), $orderReferenceTransaction),
                    array(self::TRANSACTION_STATE_OPEN)
                );
                if (!$orderReferenceStatus) {
                    throw new Creativestyle_AmazonPayments_Exception('Invalid Order Reference status returned by Amazon Payments API.');
                }

                $this->_mapTransactionStatus($orderReferenceTransaction, $orderReferenceStatus, $stateObject, $this->getInfoInstance()->getOrder()->getBaseTotalDue(), true)
                     ->_sendTransactionEmails($orderReferenceTransaction, $orderReferenceStatus, $stateObject)
                     ->_updateOrderStatus($stateObject);

                // Authorization request, throw an exception if invalid status is returned
                $authorizationStatus = $this->_authorize($this->getInfoInstance()->getOrder()->getBaseTotalDue(), $orderReferenceTransaction->getTxnId(), $authorizationTransaction, $paymentAction == self::ACTION_AUTHORIZE_CAPTURE);
                if (!$this->_validateTransactionStatus($authorizationStatus, array(self::TRANSACTION_STATE_PENDING, self::TRANSACTION_STATE_OPEN))) {
                    if (is_array($authorizationStatus) && array_key_exists(self::TRANSACTION_STATE_KEY, $authorizationStatus)
                        && $authorizationStatus[self::TRANSACTION_STATE_KEY] == self::TRANSACTION_STATE_DECLINED
                        && array_key_exists(self::TRANSACTION_REASON_KEY, $authorizationStatus))
                    {
                        switch ($authorizationStatus[self::TRANSACTION_REASON_KEY]) {
                            case self::TRANSACTION_REASON_INVALID_PAYMENT:
                                throw new Creativestyle_AmazonPayments_Exception_InvalidStatus_Recoverable('1. Invalid Authorization status returned by Amazon Payments API.');
                            case self::TRANSACTION_REASON_AMAZON_REJECTED:
                            case self::TRANSACTION_REASON_TIMEOUT:
                                throw new Creativestyle_AmazonPayments_Exception_InvalidStatus('2. Invalid Authorization status returned by Amazon Payments API.');
                        }
                    }
                    throw new Creativestyle_AmazonPayments_Exception('3. Invalid Authorization status returned by Amazon Payments API.');
                }

                $this->getInfoInstance()->setAmountAuthorized($this->getInfoInstance()->getOrder()->getTotalDue())
                    ->setBaseAmountAuthorized($this->getInfoInstance()->getOrder()->getBaseTotalDue());

                $this->_mapTransactionStatus($authorizationTransaction, $authorizationStatus, $stateObject, $this->getInfoInstance()->getOrder()->getBaseTotalDue(), true)
                     ->_sendTransactionEmails($authorizationTransaction, $authorizationStatus, $stateObject)
                     ->_updateOrderStatus($stateObject);

                // TODO: next steps depending on the transaction status

                break;

        }
        return $this;
    }

    /**
     * Check whether Pay with Amazon is available
     *
     * @param Mage_Sales_Model_Quote
     * @return bool
     */
    public function isAvailable($quote = null) {
        $checkResult = new StdClass;
        $isActive = $this->_getConfig()->isActive($quote ? $quote->getStoreId() : null) & Creativestyle_AmazonPayments_Model_Config::PAY_WITH_AMAZON_ACTIVE;
        if ($quote && !$quote->validateMinimumAmount()) {
            $isActive = false;
        }
        $checkResult->isAvailable = $isActive;
        $checkResult->isDeniedInConfig = !$isActive;
        Mage::dispatchEvent('payment_method_is_active', array(
            'result' => $checkResult,
            'method_instance' => $this,
            'quote' => $quote,
        ));
        return $checkResult->isAvailable;
    }

}
