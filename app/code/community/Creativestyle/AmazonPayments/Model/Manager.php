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
 * @copyright  Copyright (c) 2014 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Model_Manager {


    // **********************************************************************
    // Object instances geters

    protected function _getApi() {
        return Mage::getSingleton('amazonpayments/api_advanced');
    }

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }



    // **********************************************************************
    // General helpers

    protected function _sanitizeReferenceId($referenceId) {
        return substr($referenceId, 0, strrpos($referenceId, '-'));
    }

    protected function _lookupPayment($referenceId) {
        $order = Mage::getModel('sales/order')->loadByAttribute('ext_order_id', $referenceId);
        if (is_object($order) && $order->getId()) return $order->getPayment();
        return null;
    }

    /**
     * Check if addresses differ, return false otherwise
     *
     * @param Mage_Customer_Model_Address_Abstract $address1st
     * @param array $address2nd
     * @return bool
     */
    protected function _compareAddresses($address1st, $address2nd) {

        // if 2nd address is incomplete do not compare
        if (!isset($address2nd['firstname'])
            || !isset($address2nd['lastname'])
            || !isset($address2nd['city'])
            || !isset($address2nd['postcode'])
            || !isset($address2nd['country_id'])
            || !isset($address2nd['street']))
        {
            return false;
        }

        // compare both addresses, but streets due their array nature in a separate call
        $streetDiff = array_diff($address2nd['street'], $address1st->getStreet());
        return (($address1st->getFirstname() != $address2nd['firstname'])
            || ($address1st->getLastname() != $address2nd['lastname'])
            || ($address1st->getCity() != $address2nd['city'])
            || ($address1st->getPostcode() != $address2nd['postcode'])
            || ($address1st->getCountryId() != $address2nd['country_id'])
            || (!empty($streetDiff)));
    }

    /**
     * @param Mage_Customer_Model_Address_Abstract $address
     * @param array $newAddress
     * @return bool
     */
    protected function _updateOrderAddress(Mage_Customer_Model_Address_Abstract $address, $newAddress) {
        if ($this->_compareAddresses($address, $newAddress)) {
            if (isset($newAddress['firstname'])) {
                $address->setFirstname($newAddress['firstname']);
            }
            if (isset($newAddress['lastname'])) {
                $address->setLastname($newAddress['lastname']);
            }
            if (isset($newAddress['street'])) {
                $address->setStreet($newAddress['street']);
            }
            if (isset($newAddress['city'])) {
                $address->setCity($newAddress['city']);
            }
            if (isset($newAddress['postcode'])) {
                $address->setPostcode($newAddress['postcode']);
            }
            if (isset($newAddress['country_id'])) {
                $address->setCountryId($newAddress['country_id']);
            }
            if (isset($newAddress['telephone'])) {
                $address->setTelephone($newAddress['telephone']);
            }
            return true;
        }
        return false;
    }

    /**
     * Check if emails differ, return false otherwise
     *
     * @param string $email1st
     * @param string $email2nd
     * @return bool
     */
    protected function _compareEmails($email1st, $email2nd) {
        return trim($email1st) != trim($email2nd);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string $newEmail
     * @return bool
     */
    protected function _updateCustomerEmail(Mage_Sales_Model_Order $order, $newEmail) {
        if ($this->_compareEmails($order->getCustomerEmail(), $newEmail)) {
            $order->setCustomerEmail(trim($newEmail));
            return true;
        }
        return false;
    }


    /**
     * Check if names differ, return false otherwise
     *
     * @param string $name1st
     * @param string $name2nd
     * @return bool
     */
    protected function _compareNames($name1st, $name2nd) {
        return trim($name1st) != trim($name2nd);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param array $newEmail
     * @return bool
     */
    protected function _updateCustomerName(Mage_Sales_Model_Order $order, $newName) {
        $customerNameUpdated = false;
        if (isset($newName['firstname']) && isset($newName['lastname'])) {
            if ($this->_compareNames($order->getCustomerFirstname(), $newName['firstname'])) {
                $order->setCustomerFirstname($newName['firstname']);
                $customerNameUpdated = true;
            }
            if ($this->_compareNames($order->getCustomerLastname(), $newName['lastname'])) {
                $order->setCustomerLastname($newName['lastname']);
                $customerNameUpdated = true;
            }
        }
        return $customerNameUpdated;
    }

    /**
     * Converts Amazon address object to the array
     *
     * @return OffAmazonPaymentsService_Model_Address $amazonAddress
     * @return array
     */
    protected function _convertAmazonAddressToArray($amazonAddress) {
        $address = array('street' => array());
        if ($amazonAddress->isSetName()) {
            $recipientName = explode(' ', trim($amazonAddress->getName()));
            if (count($recipientName) > 1) {
                $address['firstname'] = reset($recipientName);
                $address['lastname'] = trim(str_replace($address['firstname'], "", $amazonAddress->getName()));
            } else {
                $address['firstname'] = Mage::helper('amazonpayments')->__('n/a');
                $address['lastname'] = reset($recipientName);
            }
        }
        if ($amazonAddress->isSetAddressLine1()) {
            $address['street'][] = $amazonAddress->getAddressLine1();
        }
        if ($amazonAddress->isSetAddressLine2()) {
            $address['street'][] = $amazonAddress->getAddressLine2();
        }
        if ($amazonAddress->isSetAddressLine3()) {
            $address['street'][] = $amazonAddress->getAddressLine3();
        }
        if ($amazonAddress->isSetCity()) {
            $address['city'] = $amazonAddress->getCity();
        }
        if ($amazonAddress->isSetPostalCode()) {
            $address['postcode'] = $amazonAddress->getPostalCode();
        }
        if ($amazonAddress->isSetCountryCode()) {
            $address['country_id'] = $amazonAddress->getCountryCode();
        }
        if ($amazonAddress->isSetPhone()) {
            $address['telephone'] = $amazonAddress->getPhone();
        }
        return $address;
    }



    // **********************************************************************
    // General handling routines

    /**
     * Update state of Magento transaction object
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_Status|OffAmazonPaymentsNotifications_Model_Status $transactionStatus
     * @return Mage_Sales_Model_Order_Payment_Transaction
     */
    protected function _updateTransactionStatus($transaction, $transactionStatus) {
        if ($transactionStatus->isSetState()) {
            $statusArray = array('State' => $transactionStatus->getState());
            if ($transactionStatus->isSetReasonCode()) {
                $statusArray['ReasonCode'] = $transactionStatus->getReasonCode();
            }
            if ($transactionStatus->isSetReasonDescription()) {
                $statusArray['ReasonDescription'] = $transactionStatus->getReasonDescription();
            }
            $transaction->setAdditionalInformation('state', strtolower($transactionStatus->getState()))
                ->setAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $statusArray);
        }
        return $transaction;
    }

    /**
     * 
     */
    protected function _addHistoryComment($order, $transaction, $amount, $state) {
        switch ($transaction->getTxnType()) {
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER:
                $message = 'An order of %s has been processed by Amazon Payments (%s). The new status is %s.';
                break;
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH:
                $message = 'An authorization of %s has been processed by Amazon Payments (%s). The new status is %s.';
                break;
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE:
                $message = 'A capture of %s has been processed by Amazon Payments (%s). The new status is %s.';
                break;
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND:
                $message = 'A refund of %s has been processed by Amazon Payments (%s). The new status is %s.';
                break;
            default:
                throw new Creativestyle_AmazonPayments_Exception('Cannot add a history comment for unsupported transaction type.');
        }

        return $order->addStatusHistoryComment(Mage::helper('amazonpayments')->__(
                $message,
                $order->getStore()->convertPrice($amount, true, false),
                $transaction->getTxnId(),
                strtoupper($state)
            ), true
        )->setIsCustomerNotified(Mage_Sales_Model_Order_Status_History::CUSTOMER_NOTIFICATION_NOT_APPLICABLE);
    }




    // **********************************************************************
    // Order Reference handling routines

    /**
     * Handle & process open Amazon's Order Reference object
     *
     * @todo $orderUpdated variable obsolete, remove it and add coments when order data changes
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_OrderReferenceDetails|OffAmazonPaymentsNotifications_Model_OrderReference $orderReferenceDetails
     */
    protected function _handleOpenOrderReference($payment, $transaction, $orderReferenceDetails) {

        $order = $payment->getOrder();
        $orderUpdated = false;

        // lookup for the transaction if not provided explicitly
        if (null === $transaction) {
            $transaction = $payment->lookupTransaction(
                $orderReferenceDetails->getAmazonOrderReferenceId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER
            );
        }

        if ($transaction && $orderReferenceDetails->isSetOrderReferenceStatus()) {
            $this->_updateTransactionStatus($transaction, $orderReferenceDetails->getOrderReferenceStatus());
        }

        // depending on the data source, some fields may be not available, process below section
        // only for responses to GetOrderReferenceDetails calls, skip for OrderReference notifications
        if ($orderReferenceDetails instanceof OffAmazonPaymentsService_Model_OrderReferenceDetails) {
            if ($orderReferenceDetails->isSetBuyer()) {
                if ($orderReferenceDetails->getBuyer()->isSetEmail()) {
                    $orderUpdated = $this->_updateCustomerEmail($order, $orderReferenceDetails->getBuyer()->getEmail()) || $orderUpdated;
                }
            }

            if ($orderReferenceDetails->isSetDestination()) {
                if ($orderReferenceDetails->getDestination()->isSetPhysicalDestination()) {
                    $shippingAddress = $this->_convertAmazonAddressToArray($orderReferenceDetails->getDestination()->getPhysicalDestination());
                    if (isset($shippingAddress['firstname']) && isset($shippingAddress['lastname'])) {
                        $customerName = array(
                            'firstname' => $shippingAddress['firstname'],
                            'lastname' => $shippingAddress['lastname']
                        );
                        $orderUpdated = $this->_updateCustomerName($order, $customerName) || $orderUpdated;
                    }
                    $orderUpdated = $this->_updateOrderAddress($order->getBillingAddress(), $shippingAddress) || $orderUpdated;
                    $orderUpdated = $this->_updateOrderAddress($order->getShippingAddress(), $shippingAddress) || $orderUpdated;
                }
            }
        }

        $this->_addHistoryComment($order, $transaction, $orderReferenceDetails->getOrderTotal()->getAmount(), $orderReferenceDetails->getOrderReferenceStatus()->getState());

/*
        $transactionSave = Mage::getModel('core/resource_transaction');
        $transactionSave->addObject($transaction);
        $transactionSave->addObject($order);
        $transactionSave->addCommitCallback(array($order, 'save'));
*/
        // check if authorization should be re-submitted
        $authTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        if ($authTransaction && $authTransaction->getIsClosed() && ($order->getBaseTotalDue() > 0)) {
            $payment->authorize(true, $order->getBaseTotalDue())->save();
//            $transactionSave->addObject($payment);
        }

        $order->save();
    }

    /**
     * Handle & process suspended Amazon's Order Reference object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_OrderReferenceDetails|OffAmazonPaymentsNotifications_Model_OrderReference $orderReferenceDetails
     */
    protected function _handleSuspendedOrderReference($payment, $transaction, $orderReferenceDetails) {
        if ($orderReferenceDetails->isSetOrderReferenceStatus()) {
            $this->_updateTransactionStatus($transaction, $orderReferenceDetails->getOrderReferenceStatus())->save();
        }

        $this->_addHistoryComment($payment->getOrder(), $transaction, $orderReferenceDetails->getOrderTotal()->getAmount(), $orderReferenceDetails->getOrderReferenceStatus()->getState())->save();
    }

    /**
     * Handle & process canceled Amazon's Order Reference object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_OrderReferenceDetails|OffAmazonPaymentsNotifications_Model_OrderReference $orderReferenceDetails
     */
    protected function _handleCanceledOrderReference($payment, $transaction, $orderReferenceDetails) {
        if ($orderReferenceDetails->isSetOrderReferenceStatus()) {
            $this->_updateTransactionStatus($transaction, $orderReferenceDetails->getOrderReferenceStatus())->setIsClosed(true)->save();
        }

        $this->_addHistoryComment($payment->getOrder(), $transaction, $orderReferenceDetails->getOrderTotal()->getAmount(), $orderReferenceDetails->getOrderReferenceStatus()->getState())->save();
    }

    /**
     * Handle & process closed Amazon's Order Reference object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_OrderReferenceDetails|OffAmazonPaymentsNotifications_Model_OrderReference $orderReferenceDetails
     */
    protected function _handleClosedOrderReference($payment, $transaction, $orderReferenceDetails) {
        if ($orderReferenceDetails->isSetOrderReferenceStatus()) {
            $this->_updateTransactionStatus($transaction, $orderReferenceDetails->getOrderReferenceStatus())->setIsClosed(true)->save();
        }

        $this->_addHistoryComment($payment->getOrder(), $transaction, $orderReferenceDetails->getOrderTotal()->getAmount(), $orderReferenceDetails->getOrderReferenceStatus()->getState())->save();
    }



    // **********************************************************************
    // Authorization handling routines

    /**
     * Handle & process pending Amazon's Authorization object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_AuthorizationDetails|OffAmazonPaymentsNotifications_Model_AuthorizationDetails $authorizationDetails
     */
    protected function _handlePendingAuthorization($payment, $transaction, $authorizationDetails) {
        if ($authorizationDetails->isSetAuthorizationStatus()) {
            $this->_updateTransactionStatus($transaction, $authorizationDetails->getAuthorizationStatus())->save();
        }
    }

    /**
     * Handle & process open Amazon's Authorization object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_AuthorizationDetails|OffAmazonPaymentsNotifications_Model_AuthorizationDetails $authorizationDetails
     */
    protected function _handleOpenAuthorization($payment, $transaction, $authorizationDetails) {

        $order = $payment->getOrder();
        $orderUpdated = false;

        if ($authorizationDetails->isSetAuthorizationStatus()) {
            $this->_updateTransactionStatus($transaction, $authorizationDetails->getAuthorizationStatus());
        }

        if ($authorizationDetails->isSetAuthorizationBillingAddress()) {
            $billingAddress = $this->_convertAmazonAddressToArray($authorizationDetails->getAuthorizationBillingAddress());
            if (isset($billingAddress['firstname']) && isset($billingAddress['lastname'])) {
                $customerName = array(
                    'firstname' => $billingAddress['firstname'],
                    'lastname' => $billingAddress['lastname']
                );
                $orderUpdated = $this->_updateCustomerName($order, $customerName) || $orderUpdated;
            }

            $orderUpdated = $this->_updateCustomerName($order, $customerName) || $orderUpdated;
            $orderUpdated = $this->_updateOrderAddress($order->getBillingAddress(), $billingAddress) || $orderUpdated;
        }

        if ($order->getStatus() != $this->_getConfig()->getAuthorizedOrderStatus()) {
            $order->setState(
                Mage_Sales_Model_Order::STATE_PROCESSING,
                $this->_getConfig()->getAuthorizedOrderStatus(),
                Mage::helper('amazonpayments')->__('An authorization of %s has been processed by Amazon Payments (%s). The new status is %s.',
                    $order->getStore()->convertPrice($authorizationDetails->getAuthorizationAmount()->getAmount(), true, false),
                    $authorizationDetails->getAmazonAuthorizationId(),
                    strtoupper($authorizationDetails->getAuthorizationStatus()->getState())
                ), Mage_Sales_Model_Order_Status_History::CUSTOMER_NOTIFICATION_NOT_APPLICABLE
            );
            $orderUpdated = true;
        }

        $transactionSave = Mage::getModel('core/resource_transaction');
        $transactionSave->addObject($transaction);
        if ($orderUpdated) {
            $transactionSave->addObject($order);
            $transactionSave->addCommitCallback(array($order, 'save'));
        }

        if ($this->_getConfig()->captureImmediately() && $order->canInvoice()) {
            $invoice = $order->prepareInvoice()
                ->register()
                ->capture();
            $invoice->setTransactionId($authorizationDetails->getAmazonAuthorizationId());
            $transactionSave->addObject($invoice);
        }

        $transactionSave->save();

        if (!$order->getEmailSent() && $this->_getConfig()->sendEmailConfirmation()) {
            $order->sendNewOrderEmail();
        }

    }

    /**
     * Handle & process declined Amazon's Authorization object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_AuthorizationDetails|OffAmazonPaymentsNotifications_Model_AuthorizationDetails $authorizationDetails
     */
    protected function _handleDeclinedAuthorization($payment, $transaction, $authorizationDetails) {
        if ($authorizationDetails->isSetAuthorizationStatus()) {
            $this->_updateTransactionStatus($transaction, $authorizationDetails->getAuthorizationStatus())->setIsClosed(true)->save();
            if ($authorizationDetails->getAuthorizationStatus()->getReasonCode() == 'InvalidPaymentMethod') {
                Mage::helper('amazonpayments')->sendAuthorizationDeclinedEmail($payment, $authorizationDetails);
            }
        }
        $this->_addHistoryComment($payment->getOrder(), $transaction, $authorizationDetails->getAuthorizationAmount()->getAmount(), $authorizationDetails->getAuthorizationStatus()->getState())->setIsCustomerNotified(true)->save();
    }

    /**
     * Handle & process closed Amazon's Authorization object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_AuthorizationDetails|OffAmazonPaymentsNotifications_Model_AuthorizationDetails $authorizationDetails
     */
    protected function _handleClosedAuthorization($payment, $transaction, $authorizationDetails) {
        if ($authorizationDetails->isSetAuthorizationStatus()) {
            $this->_updateTransactionStatus($transaction, $authorizationDetails->getAuthorizationStatus())->setIsClosed(true)->save();
        }

        $this->_addHistoryComment($payment->getOrder(), $transaction, $authorizationDetails->getAuthorizationAmount()->getAmount(), $authorizationDetails->getAuthorizationStatus()->getState())->save();
    }



    // **********************************************************************
    // Capture handling routines

    /**
     * Handle & process pending Amazon's Capture object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_CaptureDetails|OffAmazonPaymentsNotifications_Model_CaptureDetails $captureDetails
     */
    protected function _handlePendingCapture($payment, $transaction, $captureDetails) {
        if ($captureDetails->isSetCaptureStatus()) {
            $this->_updateTransactionStatus($transaction, $captureDetails->getCaptureStatus())->save();
        }
    }

    /**
     * Handle & process declined Amazon's Capture object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_CaptureDetails|OffAmazonPaymentsNotifications_Model_CaptureDetails $captureDetails
     */
    protected function _handleDeclinedCapture($payment, $transaction, $captureDetails) {
        if ($captureDetails->isSetCaptureStatus()) {
            $this->_updateTransactionStatus($transaction, $captureDetails->getCaptureStatus())->setIsClosed(true)->save();
        }
        $this->_addHistoryComment($payment->getOrder(), $transaction, $captureDetails->getCaptureAmount()->getAmount(), $captureDetails->getCaptureStatus()->getState())->save();
    }

    /**
     * Handle & process completed Amazon's Capture object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_CaptureDetails|OffAmazonPaymentsNotifications_Model_CaptureDetails $captureDetails
     */
    protected function _handleCompletedCapture($payment, $transaction, $captureDetails) {
        if ($captureDetails->isSetCaptureStatus()) {
            $this->_updateTransactionStatus($transaction, $captureDetails->getCaptureStatus())->setIsClosed(true)->save();
        }
        $this->_addHistoryComment($payment->getOrder(), $transaction, $captureDetails->getCaptureAmount()->getAmount(), $captureDetails->getCaptureStatus()->getState())->save();
    }

    /**
     * Handle & process closed Amazon's Capture object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_CaptureDetails|OffAmazonPaymentsNotifications_Model_CaptureDetails $captureDetails
     */
    protected function _handleClosedCapture($payment, $transaction, $captureDetails) {
        if ($captureDetails->isSetCaptureStatus()) {
            $this->_updateTransactionStatus($transaction, $captureDetails->getCaptureStatus())->setIsClosed(true)->save();
        }
        $this->_addHistoryComment($payment->getOrder(), $transaction, $captureDetails->getCaptureAmount()->getAmount(), $captureDetails->getCaptureStatus()->getState())->save();
    }



    // **********************************************************************
    // Refund handling routines

    /**
     * Handle & process pending Amazon's Refund object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_RefundDetails|OffAmazonPaymentsNotifications_Model_RefundDetails $refundDetails
     */
    protected function _handlePendingRefund($payment, $transaction, $refundDetails) {
        if ($refundDetails->isSetRefundStatus()) {
            $this->_updateTransactionStatus($transaction, $refundDetails->getRefundStatus())->save();
        }
    }

    /**
     * Handle & process declined Amazon's Refund object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_RefundDetails|OffAmazonPaymentsNotifications_Model_RefundDetails $refundDetails
     */
    protected function _handleDeclinedRefund($payment, $transaction, $refundDetails) {
        if ($refundDetails->isSetRefundStatus()) {
            $this->_updateTransactionStatus($transaction, $refundDetails->getRefundStatus())->setIsClosed(true)->save();
        }
        $this->_addHistoryComment($payment->getOrder(), $transaction, $refundDetails->getRefundAmount()->getAmount(), $refundDetails->getRefundStatus()->getState())->save();
    }

    /**
     * Handle & process completed Amazon's Refund object
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param OffAmazonPaymentsService_Model_RefundDetails|OffAmazonPaymentsNotifications_Model_RefundDetails $refundDetails
     */
    protected function _handleCompletedRefund($payment, $transaction, $refundDetails) {
        if ($refundDetails->isSetRefundStatus()) {
            $this->_updateTransactionStatus($transaction, $refundDetails->getRefundStatus())->setIsClosed(true)->save();
        }
        $this->_addHistoryComment($payment->getOrder(), $transaction, $refundDetails->getRefundAmount()->getAmount(), $refundDetails->getRefundStatus()->getState())->save();
    }



    // **********************************************************************
    // Public interface

    /**
     * Imports payment transaction details
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @return null
     */
    public function importTransactionDetails($payment, $transaction) {
        switch ($transaction->getTxnType()) {
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER:
                $this->importOrderReferenceDetails(
                    $this->_getApi()->getOrderReferenceDetails($transaction->getTxnId()),
                    $payment,
                    $transaction
                );
                break;
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH:
                $this->importAuthorizationDetails(
                    $this->_getApi()->getAuthorizationDetails($transaction->getTxnId()),
                    $payment,
                    $transaction,
                    false
                );
                break;
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE:
                $this->importCaptureDetails(
                    $this->_getApi()->getCaptureDetails($transaction->getTxnId()),
                    $payment,
                    $transaction
                );
                break;
            case Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND:
                $this->importRefundDetails(
                    $this->_getApi()->getRefundDetails($transaction->getTxnId()),
                    $payment,
                    $transaction
                );
                break;
        }
        return null;
    }

    /**
     * Import Amazon's Order Reference object data to Magento entities
     *
     * @param OffAmazonPaymentsService_Model_OrderReferenceDetails|OffAmazonPaymentsNotifications_Model_OrderReference $orderReferenceDetails
     * @param Mage_Sales_Model_Order_Payment $payment
     */
    public function importOrderReferenceDetails($orderReferenceDetails, $payment = null, $transaction = null) {

        // lookup for the payment if not provided explicitly
        if (null === $payment && $orderReferenceDetails->isSetAmazonOrderReferenceId()) {
            $payment = $this->_lookupPayment($orderReferenceDetails->getAmazonOrderReferenceId());
        }

        // do nothing if payment couldn't be found
        if (null !== $payment) {
            // lookup for the transaction if not provided explicitly
            if (null === $transaction) {
                $transaction = $payment->lookupTransaction(
                    $orderReferenceDetails->getAmazonOrderReferenceId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER
                );
            }
            if ($transaction && $orderReferenceDetails->isSetOrderReferenceStatus()) {
                $orderReferenceStatus = $orderReferenceDetails->getOrderReferenceStatus();
                if ($orderReferenceStatus->isSetState()) {
                    switch (strtolower($orderReferenceStatus->getState())) {
                        case 'open':
                            $this->_handleOpenOrderReference($payment, $transaction, $orderReferenceDetails);
                            break;
                        case 'suspended':
                            $this->_handleSuspendedOrderReference($payment, $transaction, $orderReferenceDetails);
                            break;
                        case 'canceled':
                            $this->_handleCanceledOrderReference($payment, $transaction, $orderReferenceDetails);
                            break;
                        case 'closed':
                            $this->_handleClosedOrderReference($payment, $transaction, $orderReferenceDetails);
                            break;
                    }
                }
            }
        }
    }

    /**
     * Import Amazon's Authorization object data to Magento entities
     *
     * @param OffAmazonPaymentsService_Model_AuthorizationDetails|OffAmazonPaymentsNotifications_Model_AuthorizationDetails $authorizationDetails
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @param bool $refetchDetails
     */
    public function importAuthorizationDetails($authorizationDetails, $payment = null, $transaction = null, $refetchDetails = true) {

        // lookup for the payment if not provided explicitly
        if (null === $payment && $authorizationDetails->isSetAuthorizationReferenceId()) {
            $referenceId = $this->_sanitizeReferenceId($authorizationDetails->getAuthorizationReferenceId());
            $payment = $this->_lookupPayment($referenceId);
        }

        if (null !== $payment) {
            // lookup for the transaction if not provided explicitly
            if (null === $transaction) {
                $transaction = $payment->lookupTransaction(
                    $authorizationDetails->getAmazonAuthorizationId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH
                );
            }
            if ($transaction && $authorizationDetails->isSetAuthorizationStatus()) {
                $authorizationStatus = $authorizationDetails->getAuthorizationStatus();
                if ($authorizationStatus->isSetState()) {
                    switch (strtolower($authorizationStatus->getState())) {
                        case 'pending':
                            $this->_handlePendingAuthorization($payment, $transaction, $authorizationDetails);
                            break;
                        case 'open':
                            if ($refetchDetails) {
                                $authorizationDetails = $this->_getApi()->getAuthorizationDetails($authorizationDetails->getAmazonAuthorizationId());
                            }
                            $this->_handleOpenAuthorization($payment, $transaction, $authorizationDetails);
                            break;
                        case 'declined':
                            $this->_handleDeclinedAuthorization($payment, $transaction, $authorizationDetails);
                            break;
                        case 'closed':
                            $this->_handleClosedAuthorization($payment, $transaction, $authorizationDetails);
                            break;
                    }
                }
            }
        }
    }

    /**
     * Import Amazon's Capture object data to Magento entities
     *
     * @param OffAmazonPaymentsService_Model_CaptureDetails|OffAmazonPaymentsNotifications_Model_CaptureDetails $captureDetails
     * @param Mage_Sales_Model_Order_Payment $payment
     */
    public function importCaptureDetails($captureDetails, $payment = null, $transaction = null) {

        // lookup for the payment if not provided explicitly
        if (null === $payment && $captureDetails->isSetCaptureReferenceId()) {
            $referenceId = $this->_sanitizeReferenceId($captureDetails->getCaptureReferenceId());
            $payment = $this->_lookupPayment($referenceId);
        }

        // do nothing if payment couldn't be found
        if (null !== $payment) {
            // lookup for the transaction if not provided explicitly
            if (null === $transaction) {
                $transaction = $payment->lookupTransaction(
                    $captureDetails->getAmazonCaptureId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE
                );
            }
            if ($transaction && $captureDetails->isSetCaptureStatus()) {
                $captureStatus = $captureDetails->getCaptureStatus();
                if ($captureStatus->isSetState()) {
                    switch (strtolower($captureStatus->getState())) {
                        case 'pending':
                            $this->_handlePendingCapture($payment, $transaction, $captureDetails);
                            break;
                        case 'declined':
                            $this->_handleDeclinedCapture($payment, $transaction, $captureDetails);
                            break;
                        case 'completed':
                            $this->_handleCompletedCapture($payment, $transaction, $captureDetails);
                            break;
                        case 'closed':
                            $this->_handleClosedCapture($payment, $transaction, $captureDetails);
                            break;
                    }
                }
            }
        }
    }

    /**
     * Import Amazon's Refund object data to Magento entities
     *
     * @param OffAmazonPaymentsService_Model_RefundDetails|OffAmazonPaymentsNotifications_Model_RefundDetails $refundDetails
     * @param Mage_Sales_Model_Order_Payment $payment
     */
    public function importRefundDetails($refundDetails, $payment = null, $transaction = null) {

        // lookup for the payment if not provided explicitly
        if (null === $payment && $refundDetails->isSetRefundReferenceId()) {
            $referenceId = $this->_sanitizeReferenceId($refundDetails->getRefundReferenceId());
            $payment = $this->_lookupPayment($referenceId);
        }

        // do nothing if payment couldn't be found
        if (null !== $payment) {
            // lookup for the transaction if not provided explicitly
            if (null === $transaction) {
                $transaction = $payment->lookupTransaction(
                    $refundDetails->getAmazonRefundId(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND
                );
            }
            if ($transaction && $refundDetails->isSetRefundStatus()) {
                $refundStatus = $refundDetails->getRefundStatus();
                if ($refundStatus->isSetState()) {
                    switch (strtolower($refundStatus->getState())) {
                        case 'pending':
                            $this->_handlePendingRefund($payment, $transaction, $refundDetails);
                            break;
                        case 'declined':
                            $this->_handleDeclinedRefund($payment, $transaction, $refundDetails);
                            break;
                        case 'completed':
                            $this->_handleCompletedRefund($payment, $transaction, $refundDetails);
                            break;
                    }
                }
            }
        }
    }

    /**
     * Process a notification message requested via IPN
     *
     * @param OffAmazonPaymentNotifications_Notification $notification
     */
    public function processNotification($notification = null) {
        if (null !== $notification) {
            switch ($notification->getNotificationType()) {
                case Creativestyle_AmazonPayments_Model_Api_Ipn::NOTIFICATION_TYPE_ORDER_REFERENCE:
                    if ($notification->isSetOrderReference()) {
                        $this->importOrderReferenceDetails($notification->getOrderReference());
                    } else {
                        throw new Creativestyle_AmazonPayments_Exception('OrderReference field not found in submitted notification');
                    }
                    break;
                case Creativestyle_AmazonPayments_Model_Api_Ipn::NOTIFICATION_TYPE_AUTHORIZATION:
                    if ($notification->isSetAuthorizationDetails()) {
                        $this->importAuthorizationDetails($notification->getAuthorizationDetails());
                    } else {
                        throw new Creativestyle_AmazonPayments_Exception('AuthorizationDetails field not found in submitted notification');
                    }
                    break;
                case Creativestyle_AmazonPayments_Model_Api_Ipn::NOTIFICATION_TYPE_CAPTURE:
                    if ($notification->isSetCaptureDetails()) {
                        $this->importCaptureDetails($notification->getCaptureDetails());
                    } else {
                        throw new Creativestyle_AmazonPayments_Exception('CaptureDetails field not found in submitted notification');
                    }
                    break;
                case Creativestyle_AmazonPayments_Model_Api_Ipn::NOTIFICATION_TYPE_REFUND:
                    if ($notification->isSetRefundDetails()) {
                        $this->importRefundDetails($notification->getRefundDetails());
                    } else {
                        throw new Creativestyle_AmazonPayments_Exception('RefundDetails field not found in submitted notification');
                    }
                    break;
                default:
                    throw new Creativestyle_AmazonPayments_Exception('Wrong Notification type');
            }
        } else {
            throw new Creativestyle_AmazonPayments_Exception('No notification data provided');
        }
    }

}

