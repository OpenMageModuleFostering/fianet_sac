<?php
$installer = $this;

$installer->startSetup();

	$installer->run("
			CREATE TABLE IF NOT EXISTS `{$this->getTable('fianet_sac_payment_association')}` (
			`code` VARCHAR( 255 ) NOT NULL ,
			`value` VARCHAR( 32 ) NOT NULL ,
			PRIMARY KEY ( `code` ) 
			);
	
			INSERT IGNORE INTO `{$this->getTable('fianet_configuration')}` (`code`, `text`, `default_value`, `type`, `advanced`, `sort`, `values`, `is_global`) VALUES
			('SAC_BASEURL_PRODUCTION', 'Production URL', 'https://secure.fia-net.com/fscreener/', 'S', '1', 201, NULL, '1'),
			('SAC_BASEURL_TEST', 'Test URL', 'https://secure.fia-net.com/pprod/', 'S', '1', 202, NULL, '1'),
			('SAC_URL_STACKING', 'Script to sent orders', 'engine/stacking.cgi', 'S', '1', 203, NULL, '1'),
			('SAC_URL_VALIDSTACK', 'Script to receive evaluation', 'engine/get_validstack.cgi', 'S', '1', 204, NULL, '1'),
			('SAC_URL_GETALERT', 'Script to receive reevaluation', 'engine/get_alert.cgi', 'S', '1', 205, NULL, '1'),
			('SAC_URL_BOMERCHANT', 'Connection to SAC back-office', 'commun/visucheck_detail.php', 'S', '1', 206, NULL, '1'),
			('SAC_URL_CHECKXML', 'Validate XML page', 'marchand/checkxml.php', 'S', '1', 207, NULL, '1'),
			('SAC_STATUS', 'SAC Status', '0', 'S', '0', 20, 'array(\"0\"=>Mage::Helper(\"fianet\")->__(\"Disabled\"),\"1\"=>Mage::Helper(\"fianet\")->__(\"Test\"),\"2\"=>Mage::Helper(\"fianet\")->__(\"Production\"))', '0'),
			('SAC_SITEID', 'Site identifier', NULL, 'S', '0', 21, NULL, '0'),
			('SAC_LOGIN', 'Login', NULL, 'S', '0', 22, NULL, '0'),
			('SAC_PASSWORD', 'Password', NULL, 'S', '0', 23, NULL, '0');
				");
				
$options = array('TEST'=>'TEST', 'PRODUCTION'=>'PRODUCTION');

$installer->addAttribute('order', 'fianet_sac_sent', array('type'=>'int', 'visible' => false, 'required' => true, 'default_value' => 0));
$installer->addAttribute('order', 'fianet_sac_mode', array('type'=>'varchar', 'visible' => false, 'required' => true, 'default_value' => 'TEST', 'option' => $options));
$installer->addAttribute('order', 'fianet_sac_evaluation', array('type'=>'varchar', 'visible' => false, 'required' => false));
$installer->addAttribute('order', 'fianet_sac_reevaluation', array('type'=>'varchar', 'visible' => false, 'required' => false));

$installer->endSetup();