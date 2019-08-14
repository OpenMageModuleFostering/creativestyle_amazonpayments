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
 * @author     Marek Zabrowarny / creativestyle GmbH <amazon@creativestyle.de>
 */
class Creativestyle_AmazonPayments_Model_Setup extends Mage_Eav_Model_Entity_Setup {

    protected function _getVersion() {
        return (string)$this->_moduleConfig->version;
    }

    protected function _copyConfigData(array $data) {
        foreach ($data as $oldConfigPath => $newConfigPath) {
            $query = $this->getConnection()->select()
                ->from($this->getTable('core/config_data'), '*')
                ->where('path = ?', $oldConfigPath);
            $oldConfig = $this->getConnection()->fetchAll($query);
            if (count($oldConfig)) {
                $newConfig = array();
                foreach ($oldConfig as $row) {
                    // check if config entry with given path, scope and scope_id exists
                    $query = $this->getConnection()->select()
                        ->from($this->getTable('core/config_data'), 'COUNT(*)')
                        ->where('scope = ? AND scope_id = ? AND path = ?', $row['scope'], $row['scope_id'], $newConfigPath);
                    $count = $this->getConnection()->fetchOne($query);
                    if (!$count) {
                        $newConfig[] = array(
                            'scope'     => $row['scope'],
                            'scope_id'  => $row['scope_id'],
                            'path'      => $newConfigPath,
                            'value'     => $row['value']
                        );
                    }
                }
                if (!empty($newConfig)) {
                    try {
                        $this->getConnection()->beginTransaction();
                        $this->getConnection()->insertMultiple($this->getTable('core/config_data'), $newConfig);
                        $this->getConnection()->commit();
                    } catch (Exception $e) {
                        $this->getConnection()->rollback();
                        throw $e;
                    }
                }
            }
        }
    }

    protected function _updateConfigData(array $data) {
        foreach ($data as $configPath => $updates) {
            if (is_array($updates) && !empty($updates)) {
                try {
                    $this->getConnection()->beginTransaction();
                    foreach ($updates as $oldValue => $newValue) {
                        $update = $this->getConnection()->update(
                            $this->getTable('core/config_data'),
                            array('value' => $newValue),
                            $this->getConnection()->quoteInto('path = ?', $configPath) . ' AND '
                                . $this->getConnection()->quoteInto('value = ?', $oldValue)
                        );
                    }
                    $this->getConnection()->commit();
                } catch (Exception $e) {
                    $this->getConnection()->rollback();
                    throw $e;
                }
            }
        }
    }

    public function updateConfigData() {
        switch ($this->_getVersion()) {
            case '1.8.2':
                $this->_copyConfigData(array(
                    'amazonpayments/login/active' => 'amazonpayments/general/login_active',
                    'amazonpayments/login/client_id' => 'amazonpayments/account/client_id',
                    'amazonpayments/login/language' => 'amazonpayments/general/language',
                    'amazonpayments/login/authentication' => 'amazonpayments/general/authentication'
                ));
                $this->_updateConfigData(array(
                    'amazonpayments/account/region' => array(
                        'de' => 'EUR',
                        'uk' => 'GBP'
                    )
                ));
                break;
            default:
                break;
        }
    }
}
