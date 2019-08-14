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
class Creativestyle_AmazonPayments_Model_Observer {


    // **********************************************************************
    // Object instances geters

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }



    // **********************************************************************
    // Event observers

    /**
     * Inject Authorize button to the admin order view page
     *
     * @param Varien_Event_Observer $observer
     * @return Creativestyle_AmazonPayments_Model_Observer
     */
    public function injectAuthorizeButton($observer) {
        try {
            $order = Mage::registry('sales_order');
            // check if object instance exists and whether manual authorization is enabled
            if (is_object($order) && $order->getId() && $this->_getConfig()->isManualAuthorizationAllowed()) {
                $payment = $order->getPayment();
                if (in_array($payment->getMethod(), Mage::helper('amazonpayments')->getAvailablePaymentMethods())) {
                    // check if payment wasn't authorized already
                    $orderTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
                    $authTransaction = $payment->lookupTransaction(false, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
                    // invoke injectAuthorizeButton helper if authorization transaction does not exist or is closed
                    if ($orderTransaction && !$orderTransaction->getIsClosed() && (!$authTransaction || $authTransaction->getIsClosed())) {
                        $block = Mage::getSingleton('core/layout')->getBlock('sales_order_edit');
                        if ($block) {
                            $url = Mage::getModel('adminhtml/url')->getUrl('admin_amazonpayments/adminhtml_order/authorize', array('order_id' => $order->getId()));
                            $message = Mage::helper('amazonpayments')->__('Are you sure you want to authorize payment for this order?');
                            $block->addButton('payment_authorize', array(
                                'label'     => Mage::helper('amazonpayments')->__('Authorize payment'),
                                'onclick'   => "confirmSetLocation('{$message}', '{$url}')",
                                'class'     => 'go'
                            ));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
        }
        return $this;
    }

    /**
     * Capture and log Amazon Payments API call
     *
     * @param Varien_Event_Observer $observer
     * @return Creativestyle_AmazonPayments_Model_Observer
     */
    public function logApiCall($observer) {
        $callData = $observer->getEvent()->getCallData();
        if (is_array($callData)) {
            Creativestyle_AmazonPayments_Model_Logger::logApiCall($callData);
        }
        return $this;
    }

    /**
     * Capture and log incoming IPN notification
     *
     * @param Varien_Event_Observer $observer
     * @return Creativestyle_AmazonPayments_Model_Observer
     */
    public function logIpnCall($observer) {
        $callData = $observer->getEvent()->getCallData();
        if (is_array($callData)) {
            Creativestyle_AmazonPayments_Model_Logger::logIpnCall($callData);
        }
        return $this;
    }



    // **********************************************************************
    // Cronjobs

    /**
     * Invokes Amazon Payments logfiles rotating
     *
     * @return Creativestyle_AmazonPayments_Model_Observer
     */
    public function rotateLogfiles() {
        try {
            Creativestyle_AmazonPayments_Model_Logger::rotateLogfiles();
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
            throw $e;
        }
        return $this;
    }

}
