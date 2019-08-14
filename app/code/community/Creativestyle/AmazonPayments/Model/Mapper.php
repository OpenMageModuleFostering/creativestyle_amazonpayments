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
class Creativestyle_AmazonPayments_Model_Mapper {

    /**
     * Check whether provided address lines contain PO Box data
     */
    protected function _isPoBox($addressLine1, $addressLine2 = null) {
        if (is_numeric($addressLine1)) {
            return true;
        }
        if (strpos(strtolower($addressLine1), 'packstation') !== false) {
            return true;
        }
        if (strpos(strtolower($addressLine2), 'packstation') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Convert Amazon AddressLine fields to the array indexed with the same
     * keys Magento order address entities are using. Try to guess if address
     * lines contain company name or PO Box
     *
     * @param string $addressLine1
     * @param string $addressLine2
     * @param string $addressLine3
     * @param string $countryId
     *
     * @return array
     */
    protected function _mapAmazonAddressLines($addressLine1, $addressLine2 = null, $addressLine3 = null, $countryId = null) {
        $data = array('street' => array());
        if ($countryId && in_array($countryId, array('DE', 'AT'))) {
            if ($addressLine3) {
                if ($this->_isPoBox($addressLine1, $addressLine2)) {
                    $data['street'][] = $addressLine1;
                    $data['street'][] = $addressLine2;
                } else {
                    $data['company'] = trim($addressLine1 . ' ' . $addressLine2);
                }
                $data['street'][] = $addressLine3;
            } else if ($addressLine2) {
                if ($this->_isPoBox($addressLine1)) {
                    $data['street'][] = $addressLine1;
                } else {
                    $data['company'] = $addressLine1;
                }
                $data['street'][] = $addressLine2;
            } else {
                $data['street'][] = $addressLine1;
            }
        } else {
            if ($addressLine1) {
                $data['street'][] = $addressLine1;
            }
            if ($addressLine2) {
                $data['street'][] = $addressLine2;
            }
            if ($addressLine3) {
                $data['street'][] = $addressLine3;
            }
        }
        return $data;
    }

    /**
     * Convert address object from Amazon Payments API response to Varien_Object
     * indexed with the same keys Magento order address entities are using
     *
     * @param OffAmazonPaymentsService_Model_Address $amazonAddress
     *
     * @return Varien_Object
     */
    public function mapAmazonAddress($amazonAddress) {
        $data = $this->_mapAmazonAddressLines(
            $amazonAddress->getAddressLine1(),
            $amazonAddress->getAddressLine2(),
            $amazonAddress->getAddressLine3(),
            $amazonAddress->getCountryCode()
        );
        $explodedName = Mage::helper('amazonpayments')->explodeCustomerName($amazonAddress->getName());
        $data['firstname'] = $explodedName->getFirstname();
        $data['lastname'] = $explodedName->getLastname();
        $data['country_id'] = $amazonAddress->getCountryCode();
        $data['city'] = $amazonAddress->getCity();
        $data['postcode'] = $amazonAddress->getPostalCode();
        $data['telephone'] = $amazonAddress->getPhone();
        return new Varien_Object($data);
    }

    /**
     * Convert transaction info object to Varien_Object indexed with
     * the same keys as Magento order entity is
     *
     * @param OffAmazonPaymentsService_Model|OffAmazonPayments_Model $transactionInfo
     *
     * @return Varien_Object
     */
    public function mapTransactionInfo($transactionInfo) {
        $data = array();
        // OrderReferenceDetails from API response
        if ($transactionInfo instanceof OffAmazonPaymentsService_Model_OrderReferenceDetails) {
            if ($transactionInfo->isSetBuyer()) {
                $data['customer_email'] = $transactionInfo->getBuyer()->getEmail();
                $customerName = Mage::helper('amazonpayments')->explodeCustomerName($transactionInfo->getBuyer()->getName(), null);
                $data['customer_firstname'] = $customerName->getFirstname();
                $data['customer_lastname'] = $customerName->getLastname();
            }
            if ($transactionInfo->isSetDestination()) {
                if ($transactionInfo->getDestination()->isSetPhysicalDestination()) {
                    $data['shipping_address'] = $this->mapAmazonAddress($transactionInfo->getDestination()->getPhysicalDestination());
                }
            }
            if ($transactionInfo->isSetBillingAddress()) {
                if ($transactionInfo->getBillingAddress()->isSetPhysicalAddress()) {
                    $data['billing_address'] = $this->mapAmazonAddress($transactionInfo->getBillingAddress()->getPhysicalAddress());
                    $data['customer_firstname'] = $data['billing_address']->getFirstname();
                    $data['customer_lastname'] = $data['billing_address']->getLastname();
                }
            } elseif (isset($data['shipping_address'])) {
                $data['billing_address'] = $data['shipping_address'];
            }
        }
        // OrderReference from OrderReferenceNotification
        elseif ($transactionInfo instanceof OffAmazonPaymentsNotifications_Model_OrderReference) {}
        // AuthorizationDetails from API response
        elseif ($transactionInfo instanceof OffAmazonPaymentsService_Model_AuthorizationDetails) {
            if ($transactionInfo->isSetAuthorizationBillingAddress()) {
                $data['billing_address'] = $this->mapAmazonAddress($transactionInfo->getAuthorizationBillingAddress());
                $data['customer_firstname'] = $data['billing_address']->getFirstname();
                $data['customer_lastname'] = $data['billing_address']->getLastname();
            }
        }
        // AuthorizationDetails from AuthorizationNotification
        elseif ($transactionInfo instanceof OffAmazonPaymentsNotifications_Model_AuthorizationDetails) {}
        // CaptureDetails from API response
        elseif ($transactionInfo instanceof OffAmazonPaymentsService_Model_CaptureDetails) {}
        // CaptureDetails from CaptureNotification
        elseif ($transactionInfo instanceof OffAmazonPaymentsNotifications_Model_CaptureDetails) {}
        // RefundDetails from API response
        elseif ($transactionInfo instanceof OffAmazonPaymentsService_Model_RefundDetails) {}
        // RefundDetails from RefundNotification
        elseif ($transactionInfo instanceof OffAmazonPaymentsNotifications_Model_RefundDetails) {}
        return new Varien_Object($data);
    }

}
