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
$paypalMethods = array(
    Mage_Paypal_Model_Config::METHOD_WPS,
    Mage_Paypal_Model_Config::METHOD_WPP_EXPRESS,
    Mage_Paypal_Model_Config::METHOD_WPP_DIRECT,
    Mage_Paypal_Model_Config::METHOD_WPP_PE_DIRECT,
    Mage_Paypal_Model_Config::METHOD_WPP_PE_EXPRESS,
    Mage_Paypal_Model_Config::METHOD_PAYFLOWPRO);

if (Mage::helper('fianet')->getMagentoVersion() >= 150) {
    $paypalMethods[] = Mage_Paypal_Model_Config::METHOD_PAYFLOWLINK;
    $paypalMethods[] = Mage_Paypal_Model_Config::METHOD_HOSTEDPRO;
}

foreach ($paypalMethods as $method) {
    if (!Mage::getModel('sac/payment_association')->load($method)->hasData()) {
        Mage::getModel('sac/payment_association')
                ->load($method)
                ->setId($method)
                ->setValue('paypal')
                ->save();
    }
}

$installer->endSetup();
