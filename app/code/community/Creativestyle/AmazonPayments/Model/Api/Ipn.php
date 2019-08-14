<?php
/**
 * This file is part of the official Amazon Pay and Login with Amazon extension
 * for Magento 1.x
 *
 * (c) 2014 - 2017 creativestyle GmbH. All Rights reserved
 *
 * Distribution of the derivatives reusing, transforming or being built upon
 * this software, is not allowed without explicit written permission granted
 * by creativestyle GmbH
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 * @copyright  2014 - 2017 creativestyle GmbH
 * @author     Marek Zabrowarny <ticket@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Model_Api_Ipn extends Creativestyle_AmazonPayments_Model_Api_Abstract
{
    const NOTIFICATION_TYPE_ORDER_REFERENCE = 'OrderReferenceNotification';
    const NOTIFICATION_TYPE_AUTHORIZATION   = 'AuthorizationNotification';
    const NOTIFICATION_TYPE_CAPTURE         = 'CaptureNotification';
    const NOTIFICATION_TYPE_REFUND          = 'RefundNotification';

    /**
     * @return OffAmazonPaymentsNotifications_Client
     */
    protected function _getApi()
    {
        if (null === $this->_api) {
            $this->_api = new OffAmazonPaymentsNotifications_Client(
                $this->_getConfig()->getApiConnectionParams($this->_store)
            );
        }

        return $this->_api;
    }

    /**
     * Extracts transaction data from IPN notification
     *
     * @param OffAmazonPaymentsNotifications_Model_NotificationImpl $notification
     * @return array
     * @throws Creativestyle_AmazonPayments_Exception
     */
    protected function _getTransactionDetailsFromNotification($notification)
    {
        switch ($notification->getNotificationType()) {
            case self::NOTIFICATION_TYPE_ORDER_REFERENCE:
                /** @var OffAmazonPaymentsNotifications_Model_OrderReferenceNotification $notification */
                if ($notification->isSetOrderReference()) {
                    return array(null, $notification->getOrderReference()->getAmazonOrderReferenceId());
                }
                break;
            case self::NOTIFICATION_TYPE_AUTHORIZATION:
                /** @var OffAmazonPaymentsNotifications_Model_AuthorizationNotification $notification */
                if ($notification->isSetAuthorizationDetails()) {
                    return array(null, $notification->getAuthorizationDetails()->getAmazonAuthorizationId());
                }
                break;
            case self::NOTIFICATION_TYPE_CAPTURE:
                /** @var OffAmazonPaymentsNotifications_Model_CaptureNotification $notification */
                if ($notification->isSetCaptureDetails()) {
                    return array(
                        $notification->getCaptureDetails(),
                        $notification->getCaptureDetails()->getAmazonCaptureId()
                    );
                }
                break;
            case self::NOTIFICATION_TYPE_REFUND:
                /** @var OffAmazonPaymentsNotifications_Model_RefundNotification $notification */
                if ($notification->isSetRefundDetails()) {
                    return array(
                        $notification->getRefundDetails(),
                        $notification->getRefundDetails()->getAmazonRefundId()
                    );
                }
                break;
            default:
                throw new Creativestyle_AmazonPayments_Exception('Unknown IPN notification type', 500);
        }

        throw new Creativestyle_AmazonPayments_Exception(
            sprintf('Invalid IPN %s data', $notification->getNotificationType()),
            400
        );
    }

    /**
     * Returns order instance for transaction of given ID
     *
     * @param string $transactionId
     * @return int
     * @throws Creativestyle_AmazonPayments_Exception_TransactionNotFound
     */
    protected function _lookupOrderIdByTransactionId($transactionId)
    {
        /** @var Mage_Sales_Model_Resource_Order_Payment_Transaction_Collection $transactionCollection */
        $transactionCollection = Mage::getModel('sales/order_payment_transaction')
            ->getCollection()
            ->addFieldToFilter('txn_id', $transactionId)
            ->setPageSize(1)
            ->setCurPage(1);

        foreach ($transactionCollection as $transaction) {
            return (int)$transaction->getOrderId();
        }

        throw new Creativestyle_AmazonPayments_Exception_TransactionNotFound($transactionId);
    }

    /**
     * @param $transactionId
     * @return Mage_Sales_Model_Order_Payment
     * @throws Creativestyle_AmazonPayments_Exception
     */
    protected function _lookupPaymentByTransactionId($transactionId)
    {
        $order = Mage::getModel('sales/order')->load($this->_lookupOrderIdByTransactionId($transactionId));
        if ($order->getId()) {
            return $order->getPayment();
        }

        throw new Creativestyle_AmazonPayments_Exception('Order for the payment transaction not found', 500);
    }

    /**
     * Converts a http POST body and headers into a notification object
     *
     * @param array $headers
     * @param string $body
     * @return OffAmazonPaymentsNotifications_Model_NotificationImpl
     */
    public function parseMessage($headers, $body)
    {
        return $this->_getApi()->parseRawMessage($headers, $body);
    }

    /**
     * Process notification object and updates corresponding sales entities
     *
     * @param OffAmazonPaymentsNotifications_Model_NotificationImpl $notification
     * @return string|null
     * @throws Creativestyle_AmazonPayments_Exception
     */
    public function processNotification($notification)
    {
        if ($notification) {
            list($transactionDetails, $transactionId) = $this->_getTransactionDetailsFromNotification($notification);
            $payment = $this->_lookupPaymentByTransactionId($transactionId);
            $transaction = $payment->lookupTransaction($transactionId);
            $payment->getMethodInstance()
                ->importTransactionDetails($payment, $transaction, new Varien_Object(), $transactionDetails);
            $payment->getOrder()
                ->addRelatedObject($transaction)
                ->save();
            return $transactionId;
        }

        throw new Creativestyle_AmazonPayments_Exception('Invalid IPN notification data', 400);
    }
}
