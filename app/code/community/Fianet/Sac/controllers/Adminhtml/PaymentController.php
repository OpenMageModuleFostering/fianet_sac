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
class Fianet_Sac_Adminhtml_PaymentController extends Mage_Adminhtml_Controller_Action {

    public function indexAction() {
        $this->loadLayout();
        $this->_setActiveMenu('adminfianet');
        $this->renderLayout();
    }

    public function saveAction() {
        $post = $this->getRequest()->getPost();
        try {
            if (empty($post)) {
                Mage::throwException($this->__('Invalid form data.'));
            }

            if (is_array($post)) {
                $nb = 0;
                foreach ($post as $code => $val) {
                    Mage::getModel('sac/payment_association')
                            ->load($code)
                            ->setId($code)
                            ->setValue($val)
                            ->save();
                    $nb++;
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('sac')->__('%s items saved.', $nb));
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*');
    }

}
