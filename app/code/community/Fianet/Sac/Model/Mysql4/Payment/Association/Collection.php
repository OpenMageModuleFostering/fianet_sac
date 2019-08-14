<?php

class Fianet_Sac_Model_Mysql4_Payment_Association_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract 
{
	protected function _construct()
	{
		parent::_construct();
		$this->_init('sac/payment_association');
	}
}