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
class Creativestyle_AmazonPayments_Adminhtml_Amazonpayments_OrderController extends Mage_Adminhtml_Controller_Action {

    public function authorizeAction() {
        $orderId = $this->getRequest()->getParam('order_id', null);
        if (null !== $orderId) {
            try {
                $order = Mage::getModel('sales/order')->load($orderId);
                if ($order->getId()) {
                    if (in_array($order->getPayment()->getMethod(), Mage::helper('amazonpayments')->getAvailablePaymentMethods())) {
                        $order->getPayment()
                            ->setAmountAuthorized($order->getTotalDue())
                            ->setBaseAmountAuthorized($order->getBaseTotalDue())
                            ->getMethodInstance()->authorize($order->getPayment(), $order->getBaseTotalDue());
                        $order->save();
                    }
                }
            } catch (OffAmazonPaymentsService_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                Creativestyle_AmazonPayments_Model_Logger::logException($e);
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Failed to authorize the payment.'));
                Mage::logException($e);
            }
            $this->_redirect('adminhtml/sales_order/view', array('order_id' => $orderId));
            return;
        }
        $this->_redirect('adminhtml/sales_order');
    }

    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/amazonpayments_authorize');
    }
}
