<?php
/**
 * This file is part of the official Amazon Pay and Login with Amazon extension
 * for Magento 1.x
 *
 * (c) 2016 - 2017 creativestyle GmbH. All Rights reserved
 *
 * Distribution of the derivatives reusing, transforming or being built upon
 * this software, is not allowed without explicit written permission granted
 * by creativestyle GmbH
 *
 * @category   Creativestyle
 * @package    Creativestyle_AmazonPayments
 * @copyright  2016 - 2017 creativestyle GmbH
 * @author     Marek Zabrowarny <ticket@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Model_Lookup_AccountRegion extends Creativestyle_AmazonPayments_Model_Lookup_Abstract
{
    protected function _getRegions()
    {
        return Mage::getSingleton('amazonpayments/config')->getGlobalConfigData('account_regions');
    }

    public function toOptionArray() 
    {
        if (null === $this->_options) {
            $this->_options = array();
            foreach ($this->_getRegions() as $region => $regionName) {
                $this->_options[] = array(
                    'value' => $region,
                    'label' => Mage::helper('amazonpayments')->__($regionName)
                );
            }
        }

        return $this->_options;
    }

    public function getRegionLabelByCode($code) 
    {
        $regions = $this->getOptions();
        if (array_key_exists($code, $regions)) {
            return $regions[$code];
        }

        return null;
    }
}
