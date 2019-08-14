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
 * @copyright  Copyright (c) 2016 creativestyle GmbH
 * @author     Marek Zabrowarny / creativestyle GmbH
 */

$installer = $this;
$installer->startSetup();

$amazonUserIdAttr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('customer', 'amazon_user_id');

if (!$amazonUserIdAttr->getId()) {
    $installer->addAttribute('customer', 'amazon_user_id', array(
        'type'      => 'varchar',
        'label'     => 'Amazon UID',
        'visible'   => false,
        'required'  => false,
        'unique'    => true
    ));
}

$installer->endSetup();
