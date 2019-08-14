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
$installer->run("
    SET foreign_key_checks = 0 ;
    ALTER TABLE `{$this->getTable('fianet_kwixo_catproduct_association')}`
    ADD CONSTRAINT `{$this->getTable('fianet_kwixo_catproduct_association')}_ibfk_2`
    FOREIGN KEY (`catalog_category_entity_id`)
    REFERENCES `catalog_category_entity` (`entity_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;
    SET foreign_key_checks = 1 ;
");
$installer->endSetup();
