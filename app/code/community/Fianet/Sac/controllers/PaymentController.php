<?php

class Fianet_Sac_PaymentController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{
		$this->loadLayout();
		$this->_setActiveMenu('fianet');
		$this->renderLayout();
	}
	
	public function saveAction()
	{
		$post = $this->getRequest()->getPost();
		//Zend_Debug::dump($post);
		try
		{
			if (empty($post))
			{
				Mage::throwException($this->__('Invalid form data.'));
			}
			
			if (is_array($post))
			{
				$nb = 0;
				foreach ($post as $code => $val)
				{
					Mage::getModel('sac/payment_association')
					->load($code)
					->setId($code)
					->setValue($val)
					->save();
					$nb++;
				}
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('fianet')->__('%s items saved.', $nb));
			}
		}
		catch (Exception $e)
		{
			Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
		}
		$this->_redirect('*/*');
	}
	
}