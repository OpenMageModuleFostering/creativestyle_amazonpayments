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
class Creativestyle_AmazonPayments_Model_Lookup_Language extends Creativestyle_AmazonPayments_Model_Lookup_Abstract {

    const LANGUAGE_EN_GB = 'en-GB';
    const LANGUAGE_DE_DE = 'de-DE';
    const LANGUAGE_FR_FR = 'fr-FR';
    const LANGUAGE_IT_IT = 'it-IT';
    const LANGUAGE_ES_ES = 'es-ES';

    public function toOptionArray() {
        if (null === $this->_options) {
            $this->_options = array(
                array(
                    'value' => '',
                    'label' => Mage::helper('amazonpayments')->__('Auto')
                ),
                array(
                    'value' => self::LANGUAGE_EN_GB,
                    'label' => Mage::helper('amazonpayments')->__('English')
                ),
                array(
                    'value' => self::LANGUAGE_DE_DE,
                    'label' => Mage::helper('amazonpayments')->__('German')
                ),
                array(
                    'value' => self::LANGUAGE_FR_FR,
                    'label' => Mage::helper('amazonpayments')->__('French')
                ),
                array(
                    'value' => self::LANGUAGE_IT_IT,
                    'label' => Mage::helper('amazonpayments')->__('Italian')
                ),
                array(
                    'value' => self::LANGUAGE_ES_ES,
                    'label' => Mage::helper('amazonpayments')->__('Spanish')
                )
            );
        }
        return $this->_options;
    }

    public function getLanguageByLocale($locale, $normalize = true) {
        $allowedLanguages = array(self::LANGUAGE_EN_GB, self::LANGUAGE_DE_DE, self::LANGUAGE_FR_FR, self::LANGUAGE_IT_IT, self::LANGUAGE_ES_ES);
        $localeNormalize = str_replace('_', '-', $locale);
        if (in_array($localeNormalize, $allowedLanguages)) {
            return $normalize ? $localeNormalize : $locale;
        }
        $localeLanguage = substr($localeNormalize, 0, 2);
        foreach ($allowedLanguages as $allowedLanguage) {
            if (false !== strpos($allowedLanguage, $localeLanguage)) {
                return $normalize ? $allowedLanguage : str_replace('-', '_', $allowedLanguage);
            }
        }
        return null;
    }

}
