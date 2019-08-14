<?php

class Fianet_Sac_Block_Payment_Configuration extends Mage_Adminhtml_Block_Widget_Form_Container
{
	public function __construct()
	{
		parent::__construct();
		
		$this->_objectId = 'id';
		
		$this->_blockGroup = 'sac';
		$this->_controller = 'payment';
		$this->_mode = 'edit';
		
		$this->_updateButton('save', 'label', $this->__('Save'));		
	}
	
	public function getHeaderText()
	{
		return Mage::helper('fianet')->__('Manage payment configuration');
	}
}