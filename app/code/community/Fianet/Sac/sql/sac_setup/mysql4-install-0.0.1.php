<?php

/**
 * 2000-2012 FIA-NET
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please contact us
 * via http://www.fia-net-group.com/formulaire.php so we can send you a copy immediately.
 *
 *  @author FIA-NET <support-boutique@fia-net.com>
 *  @copyright 2000-2012 FIA-NET
 *  @version Release: $Revision: 1.0.1 $
 *  @license http://www.opensource.org/licenses/OSL-3.0  Open Software License (OSL 3.0)
 */
?>
<?php

$installer = $this;

$installer->startSetup();
$installer->run("
    CREATE TABLE IF NOT EXISTS `{$this->getTable('fianet_sac_payment_association')}` (
    `code` VARCHAR( 255 ) NOT NULL ,
    `value` VARCHAR( 32 ) NOT NULL ,
    PRIMARY KEY (`code`)
    );
");

$options = array('TEST' => 'TEST', 'PRODUCTION' => 'PRODUCTION');
$installer->addAttribute('order', 'fianet_sac_sent', array('type' => 'int', 'visible' => false, 'required' => true, 'default_value' => 0));
$installer->addAttribute('order', 'fianet_sac_mode', array('type' => 'varchar', 'visible' => false, 'required' => true, 'default_value' => 'TEST', 'option' => $options));
$installer->addAttribute('order', 'fianet_sac_evaluation', array('type' => 'varchar', 'visible' => false, 'required' => false));
$installer->addAttribute('order', 'fianet_sac_reevaluation', array('type' => 'varchar', 'visible' => false, 'required' => false));

if (!(Mage::helper('fianet')->getMagentoVersion() < 140)) {
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_order_grid'), 'fianet_sac_evaluation', 'varchar(255) default NULL');
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_order_grid'), 'fianet_sac_mode', 'varchar(255) default NULL');
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_order_grid'), 'fianet_sac_reevaluation', 'varchar(255) default NULL');
    $installer->getConnection()->addColumn($installer->getTable('sales_flat_order_grid'), 'fianet_sac_sent', 'int(11) default 0');
}

$installer->endSetup();
