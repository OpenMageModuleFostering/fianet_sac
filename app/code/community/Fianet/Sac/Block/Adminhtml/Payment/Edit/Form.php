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
class Fianet_Sac_Block_Adminhtml_Payment_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {

    protected function _prepareForm() {
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save'),
            'method' => 'post',
            'enctype' => 'multipart/form-data'
        ));

        $form->setUseContainer(true);
        $this->setForm($form);

        $fieldset = $form->addFieldset('fianet_form', array('legend' => Mage::helper('fianet')->__('Payment methods')));

        $payments = Mage::getModel('fianet/mageConfiguration')->getPaymentMethods();
        $values = Mage::getModel('sac/source_type')->toOptionArray();

        foreach ($payments as $code => $name) {			
            if ((!preg_match('#receiveandpay#', $code))&&(!preg_match('/kwx/i', $code))) {
                $current_payment_association = Mage::getModel('sac/payment_association')->load($code);

                $fieldset->addField($code, 'select', array(
                    'label' => $name,
                    'class' => 'required-entry',
                    'required' => true,
                    'name' => $code,
                    'value' => $current_payment_association->getValue(),
                    'values' => $values,
                ));
            }
        }
        return parent::_prepareForm();
    }

}