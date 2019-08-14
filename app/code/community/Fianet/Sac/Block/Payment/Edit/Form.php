<?php

class Fianet_Sac_Block_Payment_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
	protected function _prepareForm()
	{
		$form = new Varien_Data_Form(array(
					'id' => 'edit_form',
					'action' => $this->getUrl('*/*/save'),
					'method' => 'post',
					'enctype' => 'multipart/form-data'
					)
			);
		
		$form->setUseContainer(true);
		$this->setForm($form);
		
		$fieldset = $form->addFieldset('fianet_form', array('legend'=>Mage::helper('fianet')->__('Payment methods')));
		
		$Payments	= Mage::getModel('fianet/MageConfiguration')
			->getPaymentMethods();
		$values = Mage::getModel('sac/Source_payment_type')->toOptionArray();
		
		foreach ($Payments as $code => $name)
		{
			if (!eregi('receiveandpay', $code))
			{
				$current_payment_association = Mage::getModel('sac/payment_association')->load($code);
				
				$fieldset->addField($code, 'select', array(
							'label'     => $name,
							'class'     => 'required-entry',
							'required'  => true,
							'name'      => $code,
							'value'		=> $current_payment_association->getValue(),
							'values'	=> $values,
							));
			}
		}
		return parent::_prepareForm();
	}
}