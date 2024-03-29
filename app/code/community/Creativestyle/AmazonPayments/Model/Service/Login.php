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
class Creativestyle_AmazonPayments_Model_Service_Login
{

    const ACCOUNT_STATUS_OK = 1;
    const ACCOUNT_STATUS_CONFIRM = 2;
    const ACCOUNT_STATUS_DATA_MISSING = 4;
    const ACCOUNT_STATUS_ERROR = 16;

    protected $_amazonUserData;

    protected $_customer = null;

    protected $_websiteId = null;

    public function __construct($amazonUserData)
    {
        if (!$this->_validateAmazonUserData($amazonUserData)) {
            throw new Creativestyle_AmazonPayments_Exception('[LWA-service] Provided user profile is invalid');
        }

        $this->_amazonUserData = $amazonUserData;
    }

    /**
     * Returns instance of Amazon Payments config object
     *
     * @return Creativestyle_AmazonPayments_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('amazonpayments/config');
    }

    /**
     * @return Mage_Customer_Model_Session
     */
    protected function _getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    protected function _getWebsiteId()
    {
        if (null === $this->_websiteId) {
            $this->_websiteId = Mage::app()->getStore()->getWebsiteId();
        }

        return $this->_websiteId;
    }

    protected function _validateAmazonUserData($amazonUserData)
    {
        return $amazonUserData instanceof Varien_Object
            && $amazonUserData->getEmail() && $amazonUserData->getName() && $amazonUserData->getUserId();
    }

    protected function _getCustomer()
    {
        $amazonUserIdAttr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('customer', 'amazon_user_id');
        if (!$amazonUserIdAttr->getId()) {
            throw new Creativestyle_AmazonPayments_Exception(
                '[LWA-service] amazon_user_id customer attribute does not exist'
            );
        }
        
        if (null === $this->_customer) {
            $customer = Mage::getModel('customer/customer');
            $collection = $customer->getCollection()
                ->addAttributeToFilter('amazon_user_id', $this->_amazonUserData->getUserId())
                ->setPageSize(1);
            if ($customer->getSharingConfig()->isWebsiteScope()) {
                $collection->addAttributeToFilter('website_id', $this->_getWebsiteId());
            }

            if (!empty($collection)) {
                // @codingStandardsIgnoreStart
                $this->_customer = $collection->getFirstItem();
                // @codingStandardsIgnoreEnd
            }
        }

        return $this->_customer;
    }

    protected function _getCustomerByEmail()
    {
        if (null === $this->_customer) {
            $this->_customer = Mage::getModel('customer/customer')
                ->setWebsiteId($this->_getWebsiteId())
                ->loadByEmail($this->_amazonUserData->getEmail());
            if (!$this->_customer->getId()) {
                $this->_customer = null;
            }
        }

        return $this->_customer;
    }

    protected function _getEmptyCustomer()
    {
        if (null === $this->_customer) {
            $this->_customer = Mage::getModel('customer/customer');
        }

        return $this->_customer;
    }

    protected function _getCustomerRequiredData()
    {
        $requiredData = array();
        $eavConfig = Mage::getSingleton('eav/config');
        foreach ($this->_getConfig()->getGlobalConfigData('customer_attributes') as $attributeCode => $attributeData) {
            $attributeModel = $eavConfig->getAttribute('customer', $attributeCode);
            if ($attributeModel instanceof Varien_Object) {
                if ($attributeModel->getIsRequired()) {
                    $requiredData[] = $attributeCode;
                }
            }
        }

        return $requiredData;
    }

    protected function _createCustomer($accountData = array())
    {
        if ($customer = $this->_getEmptyCustomer()) {
            $password = $customer->generatePassword(8);
            $customerName = Mage::helper('amazonpayments')->explodeCustomerName($this->_amazonUserData->getName());
            $customer->setId(null)
                ->setWebsiteId($this->_getWebsiteId())
                ->setSkipConfirmationIfEmail($this->_amazonUserData->getEmail())
                ->setFirstname($customerName->getFirstname())
                ->setLastname($customerName->getLastname())
                ->setEmail($this->_amazonUserData->getEmail())
                ->setPassword($password)
                ->setPasswordConfirmation($password)
                ->setConfirmation($password)
                ->setAmazonUserId($this->_amazonUserData->getUserId());

            foreach ($accountData as $attribute => $value) {
                $customer->setData($attribute, $value);
            }

            // validate customer
            $validation = $customer->validate();
            if ($validation !== true && !empty($validation)) {
                $validation = implode(", ", $validation);
                throw new Creativestyle_AmazonPayments_Exception(
                    '[LWA-service] error while creating customer account: ' . $validation
                );
            }

            $customer->save();
            return $customer;
        }

        throw new Creativestyle_AmazonPayments_Exception('[LWA-service] unable to create new customer account');
    }

    public function connect($accountData = array())
    {
        if (null !== $this->_getCustomer()) {
            return new Varien_Object(
                array(
                'status' => self::ACCOUNT_STATUS_OK,
                'customer' => $this->_getCustomer()
                )
            );
        } elseif (null !== $this->_getCustomerByEmail()) {
            if ($this->_getCustomerSession()->isLoggedIn()) {
                $this->_getCustomerByEmail()->setAmazonUserId($this->_amazonUserData->getUserId())->save();
                return new Varien_Object(
                    array(
                    'status' => self::ACCOUNT_STATUS_OK,
                    'customer' => $this->_getCustomerByEmail()
                    )
                );
            }

            return new Varien_Object(
                array(
                'status' => self::ACCOUNT_STATUS_CONFIRM,
                'customer' => $this->_getCustomerByEmail()
                )
            );
        } else {
            $requiredData = $this->_getCustomerRequiredData();
            $postedData = array_keys($accountData);
            $dataDiff = array_diff($requiredData, $postedData);
            if (!(empty($requiredData) || empty($dataDiff))) {
                return new Varien_Object(
                    array(
                    'status' => self::ACCOUNT_STATUS_DATA_MISSING,
                    'required_data' => $requiredData
                    )
                );
            } else {
                $customer = $this->_createCustomer($accountData);
                if (null !== $customer) {
                    return new Varien_Object(
                        array(
                        'status' => self::ACCOUNT_STATUS_OK,
                        'customer' => $customer
                        )
                    );
                }
            }
        }

        return new Varien_Object(array('status' => self::ACCOUNT_STATUS_ERROR));
    }

    public function setWebsiteId($websiteId)
    {
        $this->_websiteId = $websiteId;
        return $this;
    }
}
