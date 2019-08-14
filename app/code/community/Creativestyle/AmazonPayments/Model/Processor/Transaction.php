<?php
/**
 * This file is part of the official Amazon Pay and Login with Amazon extension
 * for Magento 1.x
 *
 * (c) 2017 creativestyle GmbH. All Rights reserved
 *
 * Distribution of the derivatives reusing, transforming or being built upon
 * this software, is not allowed without explicit written permission granted
 * by creativestyle GmbH
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 * @copyright  2017 creativestyle GmbH
 * @author     Marek Zabrowarny <ticket@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Model_Processor_Transaction
{
    const TRANSACTION_TYPE_ORDER                = 'OrderReference';
    const TRANSACTION_TYPE_AUTH                 = 'Authorization';
    const TRANSACTION_TYPE_CAPTURE              = 'Capture';
    const TRANSACTION_TYPE_REFUND               = 'Refund';

    const TRANSACTION_STATE_KEY                 = 'State';
    const TRANSACTION_REASON_CODE_KEY           = 'ReasonCode';
    const TRANSACTION_REASON_DESCRIPTION_KEY    = 'ReasonDescription';
    const TRANSACTION_ORDER_LANGUAGE_KEY        = 'Language';

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
    const TRANSACTION_REASON_AMAZON_CLOSED      = 'AmazonClosed';
    const TRANSACTION_REASON_EXPIRED_UNUSED     = 'ExpiredUnused';
    const TRANSACTION_REASON_PROCESSING_FAILURE = 'ProcessingFailure';
    const TRANSACTION_REASON_MAX_CAPTURES       = 'MaxCapturesProcessed';

    const EMAIL_TYPE_NEW_ORDER                  = 'new_order';
    const EMAIL_TYPE_AUTH_DECLINED              = 'auth_declined';

    /**
     * @var Mage_Sales_Model_Order_Payment_Transaction|null
     */
    protected $_transaction = null;

    /**
     * @var OffAmazonPaymentsService_Model|OffAmazonPayments_Model|null
     */
    protected $_transactionDetails = null;

    /**
     * Returns Amazon Pay API adapter instance
     *
     * @return Creativestyle_AmazonPayments_Model_Api_Advanced
     */
    protected function _getApi()
    {
        return Mage::getSingleton('amazonpayments/api_advanced')->setStore($this->_getStoreId());
    }

    /**
     * Returns Amazon Pay helper
     *
     * @return Creativestyle_AmazonPayments_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('amazonpayments');
    }

    /**
     * @param string $amount
     * @return string|null
     */
    protected function _formatAmount($amount = null)
    {
        return $amount ? $this->getTransaction()->getOrder()->getBaseCurrency()->formatTxt($amount) : null;
    }

    /**
     * Returns order's store ID
     *
     * @return int
     */
    protected function _getStoreId()
    {
        return $this->getTransaction()->getOrder()->getStoreId();
    }

    /**
     * Returns Magento transaction type
     *
     * @return string
     */
    protected function _getMagentoTransactionType()
    {
        return $this->getTransaction()->getTxnType();
    }

    /**
     * @return int
     */
    protected function _getTransactionAgeInDays()
    {
        /** @var Mage_Core_Model_Date $dateModel */
        $dateModel = Mage::getModel('core/date');
        $txnAge = ($dateModel->timestamp() - $dateModel->timestamp($this->getTransaction()->getCreatedAt()))
            / (60 * 60 * 24);
        return (int)floor($txnAge);
    }

    /**
     * Returns Magento transaction additional information
     *
     * @param string|null $key
     * @return array|null|string
     */
    protected function _getMagentoTransactionAdditionalInformation($key = null)
    {
        $additionalInformation = $this->getTransaction()
            ->getAdditionalInformation(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS);
        if (null !== $key) {
            if (is_array($additionalInformation) && array_key_exists($key, $additionalInformation)) {
                return $additionalInformation[$key];
            }

            return null;
        }

        return $additionalInformation;
    }

    /**
     * Returns transaction details
     *
     * Returns details for the transaction, if not set then retrieves
     * the details from Amazon Payments API.
     *
     * @return OffAmazonPayments_Model|OffAmazonPaymentsService_Model|null
     */
    protected function _getAmazonTransactionDetails()
    {
        if (null == $this->_transactionDetails) {
            $this->setTransactionDetails($this->_fetchTransactionDetails());
        }

        return $this->_transactionDetails;
    }

    /**
     * @return OffAmazonPaymentsService_Model_Status|null
     */
    protected function _getAmazonTransactionStatus()
    {
        // @codingStandardsIgnoreStart
        return call_user_func(
            array(
                $this->_getAmazonTransactionDetails(),
            'isSet' . $this->getTransactionType() . 'Status')
        )
            ? call_user_func(
                array(
                $this->_getAmazonTransactionDetails(),
                'get' . $this->getTransactionType() . 'Status')
            )
            : null;
        // @codingStandardsIgnoreEnd
    }

    /**
     * @return OffAmazonPaymentsService_Model_Buyer|null
     */
    protected function _getAmazonBuyer()
    {
        return is_callable(array($this->_getAmazonTransactionDetails(), 'isSetBuyer'))
            && $this->_getAmazonTransactionDetails()->isSetBuyer()
                ? $this->_getAmazonTransactionDetails()->getBuyer()
                : null;
    }

    /**
     * @return OffAmazonPaymentsService_Model_Address|null
     */
    protected function _getAmazonBillingAddress()
    {
        $transactionDetails = $this->_getAmazonTransactionDetails();
        if (is_callable(array($transactionDetails, 'isSetBillingAddress'))) {
            /** @var OffAmazonPaymentsService_Model_OrderReferenceDetails $transactionDetails */
            if ($transactionDetails->isSetBillingAddress()) {
                /** @var OffAmazonPaymentsService_Model_BillingAddress $billingAddress */
                $billingAddress = $transactionDetails->getBillingAddress();
                if (is_callable(array($billingAddress, 'isSetPhysicalAddress'))) {
                    if ($billingAddress->isSetPhysicalAddress()) {
                        return $billingAddress->getPhysicalAddress();
                    }
                }
            }
        } elseif (is_callable(array($transactionDetails, 'isSetAuthorizationBillingAddress'))) {
            /** @var OffAmazonPaymentsService_Model_AuthorizationDetails $transactionDetails */
            if ($transactionDetails->isSetAuthorizationBillingAddress()) {
                return $transactionDetails->getAuthorizationBillingAddress();
            }
        }

        return null;
    }

    /**
     * @return OffAmazonPaymentsService_Model_Address|null
     */
    protected function _getAmazonShippingAddress()
    {
        $transactionDetails = $this->_getAmazonTransactionDetails();
        if (is_callable(array($transactionDetails, 'isSetDestination'))) {
            /** @var OffAmazonPaymentsService_Model_OrderReferenceDetails $transactionDetails */
            if ($transactionDetails->isSetDestination()) {
                /** @var OffAmazonPaymentsService_Model_Destination $destination */
                $destination = $transactionDetails->getDestination();
                if (is_callable(array($destination, 'isSetPhysicalDestination'))) {
                    if ($destination->isSetPhysicalDestination()) {
                        return $destination->getPhysicalDestination();
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array|null
     */
    protected function _getAmazonIdList()
    {
        $transactionDetails = $this->_getAmazonTransactionDetails();
        if (is_callable(array($transactionDetails, 'isSetIdList'))) {
            if ($transactionDetails->isSetIdList()) {
                /** @var OffAmazonPaymentsService_Model_IdList $idList */
                $idList = $transactionDetails->getIdList();
                if (is_callable(array($idList, 'isSetmember'))) {
                    if ($idList->isSetmember()) {
                        return $idList->getmember();
                    }
                } elseif (is_callable(array($idList, 'isSetId'))) {
                    if ($idList->isSetId()) {
                        return $idList->getId();
                    }
                }
            }
        }

        return null;
    }

    /**
     * Extracts city name from given Amazon Pay address object
     *
     * @param OffAmazonPaymentsService_Model_Address|null $address
     * @return string|null
     */
    protected function _extractCityFromAmazonAddress($address = null)
    {
        return $address && $address->isSetCity()
            ? $address->getCity() : null;
    }

    /**
     * Extracts country code from given Amazon Pay address object
     *
     * @param OffAmazonPaymentsService_Model_Address|null $address
     * @return string|null
     */
    protected function _extractCountryCodeFromAmazonAddress($address = null)
    {
        return $address && $address->isSetCountryCode()
            ? $address->getCountryCode() : null;
    }

    /**
     * Extracts customer name from given Amazon Pay address object
     *
     * @param OffAmazonPaymentsService_Model_Address|null $address
     * @return Varien_Object
     */
    protected function _extractCustomerNameFromAmazonAddress($address = null)
    {
        return $address && $address->isSetName()
            ? $this->_getHelper()->explodeCustomerName($address->getName(), null) : null;
    }

    /**
     * Extracts address lines from given Amazon Pay address object
     *
     * @param OffAmazonPaymentsService_Model_Address|null $address
     * @return array|null
     */
    protected function _extractLinesFromAmazonAddress($address = null)
    {
        if ($address) {
            return array(
                $address->isSetAddressLine1() ? $address->getAddressLine1() : null,
                $address->isSetAddressLine2() ? $address->getAddressLine2() : null,
                $address->isSetAddressLine3() ? $address->getAddressLine3() : null,
            );
        }

        return null;
    }

    /**
     * Extracts phone from given Amazon Pay address object
     *
     * @param OffAmazonPaymentsService_Model_Address|null $address
     * @return string|null
     */
    protected function _extractPhoneFromAmazonAddress($address = null)
    {
        return $address && $address->isSetPhone()
            ? $address->getPhone() : null;
    }

    /**
     * Extracts postal code from given Amazon Pay address object
     *
     * @param OffAmazonPaymentsService_Model_Address|null $address
     * @return string|null
     */
    protected function _extractPostalCodeFromAmazonAddress($address = null)
    {
        return $address && $address->isSetPostalCode()
            ? $address->getPostalCode() : null;
    }

    /**
     * Retrieve transaction details from Amazon Payments API
     *
     * Retrieves details for provided Magento transaction object using
     * Amazon Payments API client. Before making a call, identifies the
     * type of provided transaction type by using appropriate function.
     *
     * @return OffAmazonPayments_Model|null
     */
    protected function _fetchTransactionDetails()
    {
        // @codingStandardsIgnoreStart
        return call_user_func(
            array($this->_getApi(), 'get' . $this->getTransactionType() . 'Details'),
            $this->getTransactionId()
        );
        // @codingStandardsIgnoreEnd
    }

    /**
     * Returns Amazon to Magento transaction data mapper
     *
     * @return Creativestyle_AmazonPayments_Model_Processor_Transaction_DataMapper
     */
    public function getDataMapper()
    {
        return Mage::getSingleton('amazonpayments/processor_transaction_dataMapper');
    }

    /**
     * Sets previously fetched transaction details
     *
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @return $this
     */
    public function setTransaction($transaction)
    {
        $this->_transaction = $transaction;
        return $this;
    }

    /**
     * Returns transaction instance
     *
     * @return Mage_Sales_Model_Order_Payment_Transaction
     * @throws Creativestyle_AmazonPayments_Exception
     */
    public function getTransaction()
    {
        if (null === $this->_transaction) {
            throw new Creativestyle_AmazonPayments_Exception('Transaction object is not set');
        }

        return $this->_transaction;
    }

    /**
     * Sets previously fetched transaction details
     *
     * @param OffAmazonPaymentsService_Model|OffAmazonPayments_Model $transactionDetails
     * @return $this
     */
    public function setTransactionDetails($transactionDetails)
    {
        $this->_transactionDetails = $transactionDetails;
        return $this;
    }

    /**
     * Returns transaction ID
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->getTransaction()->getTxnId();
    }

    /**
     * Returns Amazon Pay-specific name for Magento transaction type
     *
     * Checks the type of provided payment transaction object and
     * returns its corresponding Amazon transaction name. Returns
     * null if type of provided transaction object is neither
     * recognized nor has an Amazon Pay equivalent.
     *
     * @return string|null
     */
    public function getTransactionType()
    {
        return $this->getDataMapper()->getAmazonTransactionType($this->_getMagentoTransactionType());
    }

    /**
     * @return string|null
     */
    public function getTransactionState()
    {
        $transactionStatus = $this->_getAmazonTransactionStatus();
        return $transactionStatus ? $transactionStatus->getState() : null;
    }

    /**
     * @return string|null
     */
    public function getTransactionReasonCode()
    {
        $transactionStatus = $this->_getAmazonTransactionStatus();
        return $transactionStatus && $transactionStatus->isSetReasonCode()
            ? $transactionStatus->getReasonCode() : null;
    }

    /**
     * @return string|null
     */
    public function getTransactionReasonDescription()
    {
        $transactionStatus = $this->_getAmazonTransactionStatus();
        return $transactionStatus && $transactionStatus->isSetReasonDescription()
            ? $transactionStatus->getReasonDescription() : null;
    }

    /**
     * @return string|null
     */
    public function getTransactionOrderLanguage()
    {
        $transactionDetails = $this->_getAmazonTransactionDetails();
        return $transactionDetails
            && is_callable(array($transactionDetails, 'isSetOrderLanguage'))
            && $transactionDetails->isSetOrderLanguage()
                ? $transactionDetails->getOrderLanguage() : null;
    }

    /**
     * Returns transaction amount
     *
     * @return string|null
     */
    public function getTransactionAmount()
    {
        $transactionDetails = $this->_getAmazonTransactionDetails();
        if (is_callable(array($transactionDetails, 'get' . $this->getTransactionType() . 'Amount'))) {
            // @codingStandardsIgnoreStart
            /** @var OffAmazonPaymentsService_Model_Price $transactionAmount */
            $transactionAmount = call_user_func(
                array(
                $transactionDetails,
                'get' . $this->getTransactionType() . 'Amount'
                )
            );
            // @codingStandardsIgnoreEnd
            return $transactionAmount->getAmount();
        }

        if (is_callable(array($transactionDetails, 'getOrderTotal'))) {
            /** @var OffAmazonPaymentsService_Model_OrderTotal $transactionAmount */
            $transactionAmount = $transactionDetails->getOrderTotal();
            return $transactionAmount->getAmount();
        }

        return null;
    }

    /**
     * Returns transaction amount
     *
     * @return string|null
     */
    public function getFormattedTransactionAmount()
    {
        return $this->_formatAmount($this->getTransactionAmount());
    }

    /**
     * @return array|null
     */
    public function getChildrenIds()
    {
        return $this->_getAmazonIdList();
    }

    /**
     * @return string|null
     */
    public function getCustomerEmail()
    {
        $buyer = $this->_getAmazonBuyer();
        return $buyer && $buyer->isSetEmail()
            ? $buyer->getEmail() : null;
    }

    /**
     * @return Varien_Object|null
     */
    public function getCustomerName()
    {
        if ($customerName = $this->_extractCustomerNameFromAmazonAddress($this->_getAmazonBillingAddress())) {
            return $customerName;
        }

        $buyer = $this->_getAmazonBuyer();
        return $buyer && $buyer->isSetName()
            ? $this->_getHelper()->explodeCustomerName($buyer->getName(), null) : null;
    }

    /**
     * @return string|null
     */
    public function getCustomerFirstname()
    {
        $customerName = $this->getCustomerName();
        return $customerName  ? $customerName->getFirstname() : null;
    }

    /**
     * @return string|null
     */
    public function getCustomerLastname()
    {
        $customerName = $this->getCustomerName();
        return $customerName  ? $customerName->getLastname() : null;
    }

    /**
     * Returns customer first name for billing address
     *
     * @return string|null
     */
    public function getBillingAddressFirstname()
    {
        $customerName = $this->_extractCustomerNameFromAmazonAddress($this->_getAmazonBillingAddress());
        return $customerName ? $customerName->getFirstname() : null;
    }

    /**
     * Returns customer last name for billing address
     *
     * @return string|null
     */
    public function getBillingAddressLastname()
    {
        $customerName = $this->_extractCustomerNameFromAmazonAddress($this->_getAmazonBillingAddress());
        return $customerName ? $customerName->getLastname() : null;
    }

    /**
     * Returns billing address street lines
     *
     * @return array|null
     */
    public function getBillingAddressLines()
    {
        return $this->_extractLinesFromAmazonAddress($this->_getAmazonBillingAddress());
    }

    /**
     * Returns billing address city
     *
     * @return string|null
     */
    public function getBillingAddressCity()
    {
        return $this->_extractCityFromAmazonAddress($this->_getAmazonBillingAddress());
    }

    /**
     * Returns billing address postal code
     *
     * @return string|null
     */
    public function getBillingAddressPostalCode()
    {
        return $this->_extractPostalCodeFromAmazonAddress($this->_getAmazonBillingAddress());
    }

    /**
     * Returns billing address country code
     *
     * @return string|null
     */
    public function getBillingAddressCountryCode()
    {
        return $this->_extractCountryCodeFromAmazonAddress($this->_getAmazonBillingAddress());
    }

    /**
     * Returns customer phone for billing address
     *
     * @return string|null
     */
    public function getBillingAddressPhone()
    {
        return $this->_extractPhoneFromAmazonAddress($this->_getAmazonBillingAddress());
    }

    /**
     * Returns customer first name for shipping address
     *
     * @return string|null
     */
    public function getShippingAddressFirstname()
    {
        $customerName = $this->_extractCustomerNameFromAmazonAddress($this->_getAmazonShippingAddress());
        return $customerName ? $customerName->getData('firstname') : null;
    }

    /**
     * Returns customer last name for shipping address
     *
     * @return string|null
     */
    public function getShippingAddressLastname()
    {
        $customerName = $this->_extractCustomerNameFromAmazonAddress($this->_getAmazonShippingAddress());
        return $customerName ? $customerName->getData('lastname') : null;
    }

    /**
     * Returns shipping address street lines
     *
     * @return array|null
     */
    public function getShippingAddressLines()
    {
        return $this->_extractLinesFromAmazonAddress($this->_getAmazonShippingAddress());
    }

    /**
     * Returns shipping address city
     *
     * @return string|null
     */
    public function getShippingAddressCity()
    {
        return $this->_extractCityFromAmazonAddress($this->_getAmazonShippingAddress());
    }

    /**
     * Returns shipping address postal code
     *
     * @return string|null
     */
    public function getShippingAddressPostalCode()
    {
        return $this->_extractPostalCodeFromAmazonAddress($this->_getAmazonShippingAddress());
    }

    /**
     * Returns shipping address country code
     *
     * @return string|null
     */
    public function getShippingAddressCountryCode()
    {
        return $this->_extractCountryCodeFromAmazonAddress($this->_getAmazonShippingAddress());
    }

    /**
     * Returns customer phone for shipping address
     *
     * @return string|null
     */
    public function getShippingAddressPhone()
    {
        return $this->_extractPhoneFromAmazonAddress($this->_getAmazonShippingAddress());
    }

    /**
     * @return string|null
     */
    public function getMagentoTransactionState()
    {
        return $this->_getMagentoTransactionAdditionalInformation(self::TRANSACTION_STATE_KEY);
    }

    /**
     * @return string|null
     */
    public function getMagentoTransactionReasonCode()
    {
        return $this->_getMagentoTransactionAdditionalInformation(self::TRANSACTION_REASON_CODE_KEY);
    }

    /**
     * @return string|null
     */
    public function getMagentoTransactionOrderLanguage()
    {
        return $this->_getMagentoTransactionAdditionalInformation(self::TRANSACTION_ORDER_LANGUAGE_KEY);
    }

    /**
     * @return null|string
     */
    public function getMagentoChildTransactionType()
    {
        return $this->getDataMapper()->getMagentoChildTransactionType($this->getTransactionType());
    }

    /**
     * @return array|null
     */
    public function getMagentoBillingAddress()
    {
        return $this->getDataMapper()->getBillingAddress($this);
    }

    /**
     * @return array|null
     */
    public function getMagentoShippingAddress()
    {
        return $this->getDataMapper()->getShippingAddress($this);
    }

    /**
     * @param Varien_Object|null $stateObject
     * @param Varien_Object|null $customStatus
     * @return Varien_Object
     */
    public function generateMagentoOrderStateObject($stateObject = null, $customStatus = null)
    {
        return $this->getDataMapper()->generateMagentoOrderStateObject($this, $stateObject, $customStatus);
    }

    /**
     * @return string|null
     */
    public function getCreditmemoState()
    {
        return $this->getDataMapper()->getCreditmemoState(
            $this->getTransactionType(),
            $this->getTransactionState(),
            $this->getTransactionReasonCode()
        );
    }

    /**
     * @return string|null
     */
    public function getInvoiceState()
    {
        return $this->getDataMapper()->getInvoiceState(
            $this->getTransactionType(),
            $this->getTransactionState(),
            $this->getTransactionReasonCode()
        );
    }

    /**
     * @return array|null
     */
    public function getPaymentFlags()
    {
        return $this->getDataMapper()->getPaymentFlags(
            $this->getTransactionType(),
            $this->getTransactionState(),
            $this->getTransactionReasonCode()
        );
    }

    /**
     * @return null|string
     */
    public function getTransactionalEmailToSend()
    {
        $email = $this->getDataMapper()->getTransactionalEmailToSend(
            $this->getTransactionType(),
            $this->getTransactionState(),
            $this->getTransactionReasonCode()
        );
        if (null === $email && null !== $this->getMagentoBillingAddress()) {
            return self::EMAIL_TYPE_NEW_ORDER;
        }

        return $email;
    }

    /**
     * Checks whether Magento order data should be updated
     *
     * @return bool
     */
    public function shouldUpdateOrderData()
    {
        return $this->getDataMapper()->shouldUpdateOrderData(
            $this->getTransactionType(),
            $this->getTransactionState()
        );
    }

    /**
     * Checks whether Magento payment transaction should be updated
     *
     * @return bool
     */
    public function shouldUpdateTransaction()
    {
        return $this->getMagentoTransactionState() != $this->getTransactionState()
            || $this->getMagentoTransactionReasonCode() != $this->getTransactionReasonCode()
            || $this->getMagentoTransactionOrderLanguage() != $this->getTransactionOrderLanguage();
    }

    /**
     * Checks whether Magento payment transaction should be closed
     *
     * @return bool
     */
    public function shouldCloseTransaction()
    {
        return $this->getDataMapper()->shouldCloseTransaction($this->getMagentoTransactionState());
    }

    /**
     * Checks whether parent transaction should be updated
     *
     * @return bool
     */
    public function shouldUpdateParentTransaction()
    {
        return $this->getTransaction()->getId()
            && $this->getDataMapper()->shouldUpdateParentTransaction(
                $this->getTransactionType(),
                $this->getTransactionState(),
                $this->getTransactionReasonCode()
            );
    }

    /**
     * Checks whether transaction is eligible for data polling
     *
     * @return bool
     */
    public function shouldPollData()
    {
        switch ($this->getMagentoTransactionState()) {
            case self::TRANSACTION_STATE_PENDING:
            case self::TRANSACTION_STATE_SUSPENDED:
            case null:
                return true;
            case self::TRANSACTION_STATE_OPEN:
                $txnAge = $this->_getTransactionAgeInDays();
                if (($this->getTransactionType() == self::TRANSACTION_TYPE_ORDER && $txnAge > 180)
                    || ($this->getTransactionType() == self::TRANSACTION_TYPE_AUTH && $txnAge > 30)) {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * Returns transaction status and details to save in 'additional_data'
     * field of the payment transaction entity in Magento
     *
     * @return array|null
     */
    public function getRawDetails()
    {
        $rawDetails = array();
        if ($state = $this->getTransactionState()) {
            $rawDetails[self::TRANSACTION_STATE_KEY] = $state;
        }

        if ($reasonCode = $this->getTransactionReasonCode()) {
            $rawDetails[self::TRANSACTION_REASON_CODE_KEY] = $reasonCode;
        }

        if ($reasonDescription = $this->getTransactionReasonDescription()) {
            $rawDetails[self::TRANSACTION_REASON_DESCRIPTION_KEY] = $reasonDescription;
        }

        if ($orderLanguage = $this->getTransactionOrderLanguage()) {
            $rawDetails[self::TRANSACTION_ORDER_LANGUAGE_KEY] = $orderLanguage;
        }

        return !empty($rawDetails) ? $rawDetails : null;
    }
}
