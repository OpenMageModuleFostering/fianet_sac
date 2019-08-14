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

$Payments = Mage::getModel('fianet/mageConfiguration')->getPaymentMethods();

foreach ($Payments as $code => $name) {
    if (!preg_match('/receiveandpay/i', $code)) {
        $val = 'carte';
        if (preg_match('/checkmo/i', $code)) {
            $val = 'cheque';
        }
        if (preg_match('/paypal/i', $code)) {
            $val = 'paypal';
        }
        if (preg_match('/purchaseorder/i', $code)) {
            $val = 'virement';
        }
        if (preg_match('/free/i', $code)) {
            $val = 'virement';
        }

        if (!Mage::getModel('sac/payment_association')->load($code)->hasData()) {
            Mage::getModel('sac/payment_association')
                    ->load($code)
                    ->setId($code)
                    ->setValue($val)
                    ->save();
        }
    }
}

$installer->endSetup();
