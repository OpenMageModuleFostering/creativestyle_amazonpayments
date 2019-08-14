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
class Creativestyle_AmazonPayments_CheckoutController extends Mage_Core_Controller_Front_Action
{
    /**
     * Current order reference ID
     *
     * @var string|null
     */
    protected $_orderReferenceId = null;

    /**
     * Current access token value
     *
     * @var string|null
     */
    protected $_accessToken = null;

    /**
     * Returns Amazon checkout instance
     *
     * @return Creativestyle_AmazonPayments_Model_Checkout
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('amazonpayments/checkout');
    }

    /**
     * Returns checkout session instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Returns saved order reference ID
     *
     * @return string|null
     */
    protected function _getOrderReferenceId()
    {
        return $this->_orderReferenceId;
    }

    /**
     * Returns saved access token
     *
     * @return string|null
     */
    protected function _getAccessToken()
    {
        return $this->_accessToken;
    }

    /**
     * Returns Amazon Pay API adapter instance
     *
     * @return Creativestyle_AmazonPayments_Model_Api_Advanced
     */
    protected function _getApi()
    {
        return Mage::getModel('amazonpayments/api_advanced');
    }

    /**
     * Returns current quote entity
     *
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return $this->_getCheckout()->getQuote();
    }

    protected function _getShippingMethodsHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('amazonpayments_checkout_shippingmethod');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    protected function _getReviewHtml()
    {
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        $update->load('amazonpayments_checkout_review');
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    protected function _isSubmitAllowed()
    {
        if (!$this->_getQuote()->isVirtual()) {
            $address = $this->_getQuote()->getShippingAddress();
            $method = $address->getShippingMethod();
            $rate = $address->getShippingRateByCode($method);
            if (!$this->_getQuote()->isVirtual() && (!$method || !$rate)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clear Order Reference ID in controller properties
     * and in session data and return its value
     */
    protected function _clearOrderReferenceId()
    {
        $this->_orderReferenceId = null;
        $this->_getCheckoutSession()->setAmazonOrderReferenceId(null);
    }

    /**
     * Cancels order reference at Amazon Payments gateway
     * and clears corresponding session data
     */
    protected function _cancelOrderReferenceId()
    {
        if ($this->_orderReferenceId) {
            $this->_getApi()->cancelOrderReference($this->_orderReferenceId);
            $this->_clearOrderReferenceId();
        }
    }

    /**
     * Send Ajax redirect response
     *
     * @return $this
     */
    protected function _ajaxRedirectResponse()
    {
        $this->getResponse()
            ->setHeader('HTTP/1.1', '403 Session Expired')
            ->setHeader('Login-Required', 'true')
            ->sendResponse();
        return $this;
    }

    /**
     * Validate ajax request and redirect on failure
     *
     * @return bool
     */
    protected function _expireAjax()
    {
        if (!$this->_getQuote()->hasItems() || $this->_getQuote()->getHasError()) {
            $this->_ajaxRedirectResponse();
            return true;
        }

        if ($this->_getCheckoutSession()->getCartWasUpdated(true)) {
            $this->_ajaxRedirectResponse();
            return true;
        }

        if (null === $this->_getOrderReferenceId()) {
            $this->_ajaxRedirectResponse();
            return true;
        }

        return false;
    }

    public function preDispatch()
    {
        parent::preDispatch();
        $this->_orderReferenceId = $this->getRequest()->getParam(
            'orderReferenceId',
            $this->_getCheckoutSession()->getAmazonOrderReferenceId()
        );
        $this->_accessToken = $this->getRequest()->getParam(
            'accessToken',
            $this->_getCheckoutSession()->getAmazonAccessToken()
        );
        $this->_getCheckoutSession()->setAmazonOrderReferenceId($this->_orderReferenceId);
        $this->_getCheckoutSession()->setAmazonAccessToken($this->_accessToken);
    }

    public function indexAction()
    {
        try {
            if (!$this->_getQuote()->hasItems() || $this->_getQuote()->getHasError()) {
                $this->_redirect('checkout/cart');
                return;
            }

            if (!$this->_getQuote()->validateMinimumAmount()) {
                $error = Mage::getStoreConfig('sales/minimum_order/error_message') ?
                    Mage::getStoreConfig('sales/minimum_order/error_message') :
                    Mage::helper('checkout')->__('Subtotal must exceed minimum order amount');
                $this->_getCheckoutSession()->addError($error);
                $this->_redirect('checkout/cart');
                return;
            }

            if (null === $this->_getOrderReferenceId() && null === $this->_getAccessToken()) {
                $this->_redirect('checkout/cart');
                return;
            }

            $this->_getCheckoutSession()->setCartWasUpdated(false);
            $this->_getCheckout()->savePayment(null);

            $this->loadLayout();
            $this->getLayout()->getBlock('head')->setTitle($this->__('Amazon Pay'));
            $this->renderLayout();
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
            $this->_getCheckoutSession()->addError(
                $this->__('There was an error processing your order. Please contact us or try again later.')
            );
            $this->_redirect('checkout/cart');
            return;
        }
    }

    public function saveShippingAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                if ($this->_expireAjax()) {
                    return;
                }

                // submit draft data of order reference to Amazon gateway
                $orderReferenceDetails = $this->_getApi()->setOrderReferenceDetails(
                    $this->_getQuote()->getBaseGrandTotal(),
                    $this->_getQuote()->getBaseCurrencyCode(),
                    $this->_getOrderReferenceId()
                );

                /** @var Creativestyle_AmazonPayments_Model_Processor_Transaction $transactionProcessor */
                $transactionProcessor = Mage::getModel('amazonpayments/processor_transaction');
                $transactionProcessor->setTransactionDetails($orderReferenceDetails);
                $shippingAddress = $transactionProcessor->getMagentoShippingAddress();
                $result = $this->_getCheckout()->saveShipping(
                    array_merge(
                        $shippingAddress,
                        array('use_for_shipping' => true)
                    ),
                    false
                );
            } catch (Exception $e) {
                Creativestyle_AmazonPayments_Model_Logger::logException($e);
                $result = array(
                    'error' => -1,
                    'error_messages' => $e->getMessage(),
                    'allow_submit' => false
                );
            }

            if (!isset($result['error'])) {
                $result = array(
                    'render_widget' => array(
                        'shipping-method' => $this->_getShippingMethodsHtml()
                    ),
                    'allow_submit' => false
                );
            };
        } else {
            $this->_forward('noRoute');
            return;
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function saveShippingMethodAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                if ($this->_expireAjax()) {
                    return;
                }

                $data = $this->getRequest()->getPost('shipping_method', '');
                $this->_getCheckout()->saveShippingMethod($data);
            } catch (Exception $e) {
                Creativestyle_AmazonPayments_Model_Logger::logException($e);
                $result = array(
                    'error' => true,
                    'error_messages' => $e->getMessage(),
                    'allow_submit' => false
                );
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            }

            Mage::dispatchEvent(
                'checkout_controller_onepage_save_shipping_method',
                array('request' => $this->getRequest(), 'quote' => $this->_getQuote())
            );
            $this->_getQuote()->collectTotals()->save();
            $result = array(
                'render_widget' => array('review' => $this->_getReviewHtml()),
                'allow_submit' => $this->_isSubmitAllowed()
            );
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } else {
            $this->_forward('noRoute');
        }
    }

    /**
     * @param OffAmazonPaymentsService_Model_OrderReferenceDetails $orderReference
     * @return array|null
     */
    protected function _validateOrderReference($orderReference)
    {
        /** @var OffAmazonPaymentsService_Model_Constraints $constraints */
        if ($constraints = $orderReference->getConstraints()) {
            /** @var OffAmazonPaymentsService_Model_Constraint $constraint */
            foreach ($constraints->getConstraint() as $constraint) {
                switch ($constraint->getConstraintID()) {
                    case 'ShippingAddressNotSet':
                        return array(
                            'success' => false,
                            'error' => true,
                            'error_messages' => $this->__(
                                'There has been a problem with the selected payment method from your Amazon account, '
                                . 'please update the payment method or choose another one.'
                            ),
                            'allow_submit' => false
                        );
                    case 'PaymentMethodNotAllowed':
                    case 'PaymentPlanNotSet':
                        return array(
                            'success' => false,
                            'error' => true,
                            'error_messages' => $this->__(
                                'There has been a problem with the selected payment method from your Amazon account, '
                                . 'please update the payment method or choose another one.'
                            ),
                            'allow_submit' => $this->_isSubmitAllowed(),
                            'deselect_payment' => true,
                            'render_widget' => array(
                                'wallet' => true
                            )
                        );
                }
            }
        }

        return null;
    }

    /**
     * @param array $postedAgreements
     * @param array $requiredAgreements
     * @return array|null
     */
    protected function _validateCheckoutAgreements($postedAgreements, $requiredAgreements)
    {
        if ($requiredAgreements) {
            $diff = array_diff($requiredAgreements, $postedAgreements);
            if ($diff) {
                return array(
                    'success' => false,
                    'error' => true,
                    'error_messages' => $this->__(
                        'Please agree to all the terms and conditions before placing the order.'
                    ),
                    'allow_submit' => $this->_isSubmitAllowed()
                );
            }
        }

        return null;
    }

    /**
     * @param Creativestyle_AmazonPayments_Exception_InvalidTransaction $e
     * @return array|null
     */
    protected function _handleInvalidTransactionException(Creativestyle_AmazonPayments_Exception_InvalidTransaction $e)
    {
        if ($e->getType() == Creativestyle_AmazonPayments_Model_Processor_Transaction::TRANSACTION_TYPE_AUTH) {
            if ($e->getState()
                == Creativestyle_AmazonPayments_Model_Processor_Transaction::TRANSACTION_STATE_DECLINED) {
                if ($e->getReasonCode()
                    == Creativestyle_AmazonPayments_Model_Processor_Transaction::TRANSACTION_REASON_INVALID_PAYMENT) {
                    return array(
                        'success'        => false,
                        'error'          => true,
                        'error_messages' => $this->__(
                            'There has been a problem with the selected payment method from your Amazon account, '
                            . 'please update the payment method or choose another one.'
                        ),
                        'allow_submit' => $this->_isSubmitAllowed(),
                        'deselect_payment' => true,
                        'render_widget' => array(
                            'wallet' => true
                        ),
                        'disable_widget' => array(
                            'address-book' => true,
                            'shipping-method' => true,
                            'review' => true
                        )
                    );
                }
            }
        }

        Creativestyle_AmazonPayments_Model_Logger::logException($e);

        $this->_getCheckoutSession()->addError(
            $this->__('There was an error processing your order. Please contact us or try again later.')
        );

        return array(
            'success' => false,
            'error' => true,
            'redirect' => Mage::getUrl('checkout/cart')
        );
    }

    public function saveOrderAction()
    {
        try {
            // validate checkout agreements
            $result = $this->_validateCheckoutAgreements(
                array_keys($this->getRequest()->getPost('agreement', array())),
                Mage::helper('checkout')->getRequiredAgreementIds()
            );
            if ($result) {
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            }

            // validate order reference
            $orderReferenceDetails = $this->_getApi()->getOrderReferenceDetails(
                $this->_getOrderReferenceId(),
                $this->_getAccessToken()
            );
            $result = $this->_validateOrderReference($orderReferenceDetails);
            if ($result) {
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            }

            $skipOrderReferenceProcessing = $orderReferenceDetails->getOrderReferenceStatus()->getState()
                != Creativestyle_AmazonPayments_Model_Processor_Transaction::TRANSACTION_STATE_DRAFT;

            $sequenceNumber = (int)$this->_getCheckoutSession()->getAmazonSequenceNumber();
            $this->_getQuote()->getPayment()
                ->setTransactionId($this->_getOrderReferenceId())
                ->setSkipOrderReferenceProcessing($skipOrderReferenceProcessing)
                ->setAmazonSequenceNumber($sequenceNumber ? $sequenceNumber : null);
            $this->_getCheckoutSession()->setAmazonSequenceNumber($sequenceNumber ? $sequenceNumber + 1 : 1);



            $simulation = $this->getRequest()->getPost('simulation', array());
            if (!empty($simulation) && isset($simulation['object'])) {
                $simulationData = array(
                    'object' => isset($simulation['object']) ? $simulation['object'] : null,
                    'state' => isset($simulation['state']) ? $simulation['state'] : null,
                    'reason_code' => isset($simulation['reason']) ? $simulation['reason'] : null
                );
                $simulationData['options'] = Creativestyle_AmazonPayments_Model_Simulator::getSimulationOptions(
                    $simulationData['object'],
                    $simulationData['state'],
                    $simulationData['reason_code']
                );
                $this->_getQuote()->getPayment()->setSimulationData($simulationData);
            }

            $this->_getCheckout()->saveOrder();
            $this->_getQuote()->save();
            $result = array(
                'success' => true,
                'error' => false,
                'redirect' => Mage::getUrl('checkout/onepage/success')
            );
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        } catch (Creativestyle_AmazonPayments_Exception_InvalidTransaction $e) {
            $result = $this->_handleInvalidTransactionException($e);
            if (is_array($result)) {
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
                return;
            }
        } catch (Exception $e) {
            Creativestyle_AmazonPayments_Model_Logger::logException($e);
            Mage::helper('checkout')->sendPaymentFailedEmail($this->_getQuote(), $e->getMessage());
            $this->_getCheckoutSession()->addError(
                $this->__('There was an error processing your order. Please contact us or try again later.')
            );
            $result = array(
                'success' => false,
                'error' => true,
                'redirect' => Mage::getUrl('checkout/cart')
            );
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    public function clearOrderReferenceAction()
    {
        $this->_clearOrderReferenceId();
        $this->_redirect('checkout/cart');
    }

    public function cancelOrderReferenceAction()
    {
        $this->_cancelOrderReferenceId();
        $this->_redirect('checkout/cart');
    }

    public function couponPostAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                if ($this->_expireAjax()) {
                    return;
                }

                $couponCode = (string) $this->getRequest()->getParam('coupon_code');
                if ($this->getRequest()->getParam('remove') == 1) {
                    $couponCode = '';
                }

                $this->_getQuote()->getShippingAddress()->setCollectShippingRates(true);
                $result = $this->_getQuote()->setCouponCode($couponCode)
                    ->collectTotals()
                    ->save();
            } catch (Exception $e) {
                Creativestyle_AmazonPayments_Model_Logger::logException($e);
                $result = array(
                    'error' => -1,
                    'error_messages' => $e->getMessage()
                );
            }

            if (!isset($result['error'])) {
                $result = array(
                    'render_widget' => array(
                        'review' => $this->_getReviewHtml()
                    ),
                    'allow_submit' => $this->_isSubmitAllowed()
                );
            };
        } else {
            $this->_forward('noRoute');
            return;
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}
