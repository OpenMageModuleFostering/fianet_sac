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

$shipping_list = Mage::getModel('fianet/mageConfiguration')->getShippingMethods();
foreach ($shipping_list as $Code => $label) {

    if (!Mage::getModel('fianet/shipping_association')->load($Code)->hasData()) {
        Mage::getModel('fianet/shipping_association')
                ->load($Code)
                ->setShipping_code($Code)
                ->setFianet_shipping_type('4')
                ->setDelivery_times('2')
                ->setConveyor_name('A definir')
                ->save();
    }
}
$installer->endSetup();
