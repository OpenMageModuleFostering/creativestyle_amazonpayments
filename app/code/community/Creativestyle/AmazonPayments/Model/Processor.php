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
 * @copyright  Copyright (c) 2015 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Model_Processor {

    protected function _getApi($store = null) {
        return Mage::getSingleton('amazonpayments/api_advanced')->setStore($store);
    }

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    protected function _getDataMapper() {
        return Mage::getSingleton('amazonpayments/mapper');
    }

    /**
     * Return Amazon transaction name for provided Magento transaction object
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     *
     * @return string|null
     */
    protected function _getAmazonTransactionType($transaction) {
        switch ($transaction->getTxnType()) {
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER:
                return 'OrderReference';
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH:
                return 'Authorization';
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE:
                return 'Capture';
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND:
                return 'Refund';
        }
        return null;
    }

    /**
     * Get transaction details from Amazon Payments API
     *
     * @param string $transactionId
     * @param string $transactionType
     * @param int $store
     *
     * @return OffAmazonPaymentsService_Model
     */
    protected function _getTransactionDetails($transactionId, $transactionType, $store = null) {
        return call_user_func(array($this->_getApi($store), 'get' . $transactionType . 'Details'), $transactionId);
    }

    /**
     * Extract transaction status from the transaction details,
     * fetch transaction details if not provided in the arguments
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param int $store
     * @param OffAmazonPaymentsService_Model|OffAmazonPayments_Model $transactionDetails
     *
     * @return array|null
     */
    protected function _fetchTransactionStatus($transaction, $store = null, $transactionDetails = null) {
        $transactionType = $this->_getAmazonTransactionType($transaction);
        if (null === $transactionDetails) {
            $transactionDetails = $this->_getTransactionDetails($transaction->getTxnId(), $transactionType, $store);
        }
        if (call_user_func(array($transactionDetails, 'isSet' . $transactionType . 'Status'))) {
            return $this->_getTransactionNewStatus(
                $transaction,
                call_user_func(array($transactionDetails, 'get' . $transactionType . 'Status'))
            );
        }
        return null;
    }

    /**
     * Import transaction details to the Magento order and its related objects
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model|OffAmazonPayments_Model $transactionDetails
     *
     * @return array|null
     */
    protected function _importTransactionDetails($payment, $transaction, $transactionDetails = null) {
        $transactionType = $this->_getAmazonTransactionType($transaction);
        if (null === $transactionDetails) {
            $transactionDetails = $this->_getTransactionDetails($transaction->getTxnId(), $transactionType, $payment->getOrder()->getStoreId());
        }
        $newStatus = $this->_fetchTransactionStatus($transaction, $payment->getOrder()->getStoreId(), $transactionDetails);
        if ($newStatus) {
            // $transactionAmountObject = call_user_func(array($transactionDetails, $transactionType == 'OrderReference' ? 'getOrderTotal' : 'get' . $transactionType . 'Amount'));
            // $transactionAmountObject->getAmount()
            $this->updateTransactionStatus($transaction, $newStatus);
            $this->updateOrderData($payment->getOrder(), $transactionDetails);
        }
        return $newStatus;
    }

    /**
     * Check if the transaction status changed, if so return new status array,
     * null otherwise
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_Status|OffAmazonPaymentsNotifications_Model_Status $recentTransactionStatus
     *
     * @return array|null
     */
    protected function _getTransactionNewStatus($transaction, $recentTransactionStatus) {
        if ($recentTransactionStatus->isSetState()) {
            if (Mage::helper('amazonpayments')->getTransactionStatus($transaction) != $recentTransactionStatus->getState()) {
                $status = array('State' => $recentTransactionStatus->getState());
                if ($recentTransactionStatus->isSetReasonCode()) {
                    $status['ReasonCode'] = $recentTransactionStatus->getReasonCode();
                }
                if ($recentTransactionStatus->isSetReasonDescription()) {
                    $status['ReasonDescription'] = $recentTransactionStatus->getReasonDescription();
                }
                return $status;
            }
        }
        return null;
    }

    /**
     * @param Mage_Customer_Model_Address_Abstract $addressObject
     * @param Varien_Object $addressData
     */
    protected function _updateAddress($addressObject, $addressData) {
        if ($addressObject->getFirstname() != $addressData->getFirstname()) {
            $addressObject->setFirstname($addressData->getFirstname());
        }
        if ($addressObject->getLastname() != $addressData->getLastname()) {
            $addressObject->setLastname($addressData->getLastname());
        }
        if ($addressObject->getCompany() != $addressData->getCompany()) {
            $addressObject->setCompany($addressData->getCompany());
        }
        if ($addressObject->getCity() != $addressData->getCity()) {
            $addressObject->setCity($addressData->getCity());
        }
        if ($addressObject->getPostcode() != $addressData->getPostcode()) {
            $addressObject->setPostcode($addressData->getPostcode());
        }
        if ($addressObject->getCountryId() != $addressData->getCountryId()) {
            $addressObject->setCountryId($addressData->getCountryId());
        }
        if ($addressObject->getTelephone() != $addressData->getTelephone()) {
            $addressObject->setTelephone($addressData->getTelephone());
        }
        $streetDiff = array_diff($addressObject->getStreet(), $addressData->getStreet());
        if (!empty($streetDiff)) {
            $addressObject->setStreet($addressData->getStreet());
        }
    }

    /**
     * Transfer order to Amazon Payments gateway
     *
     * @param  Varien_Object $payment
     * @param  float $amount
     * @param  string $transactionSequenceId
     */
    public function order(Varien_Object $payment, $amount, $transactionSequenceId) {
        $this->_getApi($payment->getOrder()->getStoreId())->setOrderReferenceDetails(
            $transactionSequenceId,
            $amount,
            $payment->getOrder()->getBaseCurrencyCode(),
            $payment->getOrder()->getIncrementId()
        );
        $this->orderConfirm($payment, $transactionSequenceId);
    }

    public function orderConfirm(Varien_Object $payment, $transactionSequenceId) {
        $this->_getApi($payment->getOrder()->getStoreId())->confirmOrderReference($transactionSequenceId);
        Creativestyle_AmazonPayments_Model_Simulator::simulate($payment, 'OrderReference');
    }

    /**
     * Authorize order amount on Amazon Payments gateway
     *
     * @param  Varien_Object $payment
     * @param  float $amount
     * @param  string $transactionSequenceId
     * @param  string $parentTransactionId
     * @param  bool $captureNow
     *
     * @return OffAmazonPaymentsService_Model_AuthorizationDetails
     */
    public function authorize(Varien_Object $payment, $amount, $transactionSequenceId, $parentTransactionId, $captureNow = false) {
        return $this->_getApi($payment->getOrder()->getStoreId())->authorize(
            $parentTransactionId,
            $transactionSequenceId,
            $amount,
            $payment->getOrder()->getBaseCurrencyCode(),
            Creativestyle_AmazonPayments_Model_Simulator::simulate($payment, 'Authorization'),
            $captureNow,
            $this->_getConfig()->isAuthorizationSynchronous($payment->getOrder()->getStoreId()) ? 0 : null
        );
    }

    /**
     * Capture order amount on Amazon Payments gateway
     *
     * @param  Varien_Object $payment
     * @param  float $amount
     * @param  string $transactionSequenceId
     * @param  string $parentTransactionId
     *
     * @return OffAmazonPaymentsService_Model_AuthorizationDetails
     */
    public function capture(Varien_Object $payment, $amount, $transactionSequenceId, $parentTransactionId) {
        return $this->_getApi($payment->getOrder()->getStoreId())->capture(
            $parentTransactionId,
            $transactionSequenceId,
            $amount,
            $payment->getOrder()->getBaseCurrencyCode(),
            Creativestyle_AmazonPayments_Model_Simulator::simulate($payment, 'Capture')
        );
    }

    /**
     * Return payment transaction status info array
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     *
     * @return array|null
     */
    public function fetchTransactionDetails($payment, $transaction) {
        return $this->_fetchTransactionStatus($transaction, $payment->getOrder()->getStoreId());
    }

    /**
     * Import payment transaction info and return transaction status info array
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model|OffAmazonPayments_Model $transactionDetails
     *
     * @return array|null
     */
    public function importTransactionDetails($payment, $transaction, $transactionDetails = null) {
        return $this->_importTransactionDetails($payment, $transaction, $transactionDetails);
    }

    /**
     * Update order data based using payment transaction details
     *
     * @param Mage_Sales_Model_Order $order
     * @param OffAmazonPaymentsService_Model|OffAmazonPayments_Model $transactionDetails
     */
    public function updateOrderData($order, $transactionDetails) {
        $orderData = $this->_getDataMapper()->mapTransactionInfo($transactionDetails);
        // check which order data should be updated
        if ($orderData->hasCustomerEmail() && $order->getCustomerEmail() != $orderData->getCustomerEmail()) {
            $order->setCustomerEmail($orderData->getCustomerEmail());
        }
        if ($orderData->hasCustomerFirstname() && $order->getCustomerFirstname() != $orderData->getCustomerFirstname()) {
            $order->setCustomerFirstname($orderData->getCustomerFirstname());
        }
        if ($orderData->hasCustomerLastname() && $order->getCustomerLastname() != $orderData->getCustomerLastname()) {
            $order->setCustomerLastname($orderData->getCustomerLastname());
        }
        if ($orderData->hasBillingAddress()) {
            $this->_updateAddress($order->getBillingAddress(), $orderData->getBillingAddress());
        }
        if ($orderData->hasShippingAddress()) {
            $this->_updateAddress($order->getShippingAddress(), $orderData->getShippingAddress());
        }
    }

    /**
     * Update payment transaction status stored in additional_information field
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param array $transactionStatus
     *
     * @return Mage_Sales_Model_Order_Payment_Transaction
     */
    public function updateTransactionStatus($transaction, $transactionStatus) {
        return $transaction->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $transactionStatus);
    }

}
