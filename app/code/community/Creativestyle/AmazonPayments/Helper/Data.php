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

/**
 * Amazon Payments data helper
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 */
class Creativestyle_AmazonPayments_Helper_Data extends Mage_Core_Helper_Abstract {

    protected function _getConfig() {
        return Mage::getSingleton('amazonpayments/config');
    }

    /**
     * Send email id payment was failed
     *
     * @param Mage_Sales_Model_Order_Payment $payment
     * @param OffAmazonPaymentsService_Model_AuthorizationDetails|OffAmazonPaymentsNotifications_Model_AuthorizationDetails $authorizationDetails
     */
    public function sendAuthorizationDeclinedEmail($payment, $authorizationDetails) {
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        $mailTemplate = Mage::getModel('core/email_template');
        /* @var $mailTemplate Mage_Core_Model_Email_Template */

        $template = $this->_getConfig()->getAuthorizationDeclinedEmailTemplate();

        $order = $payment->getOrder();
        $mailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $order->getStore()->getId()))
            ->sendTransactional(
                $template,
                $this->_getConfig()->getAuthorizationDeclinedEmailIdentity(),
                $order->getCustomerEmail(),
                null,
                array(
                    'orderId' => $order->getIncrementId(),
                    'storeName' => $order->getStore()->getFrontendName(),
                    'customer' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname()
                )
            );
        $translate->setTranslateInline(true);
        return $this;
    }

    /**
     * Return array of all available Amazon payment methods
     *
     * @return array
     */
    public function getAvailablePaymentMethods() {
        return array(
            'amazonpayments_advanced',
            'amazonpayments_advanced_sandbox'
        );
    }

    /**
     * Check if the current User Agent is specific for any mobile device
     *
     * @return bool
     */
    public function isMobileDevice() {
        $userAgent = Mage::app()->getRequest()->getServer('HTTP_USER_AGENT');
        if (empty($userAgent)) {
            return false;
        }
        return preg_match('/iPhone|iPod|BlackBerry|Palm|Googlebot-Mobile|Mobile|mobile|mobi|Windows Mobile|Safari Mobile|Android|Opera Mini/', $userAgent);
    }

}
