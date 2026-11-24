<?php
/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

class paylandsDeletecardModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    private $ajaxResponse;

    public function init() {
        parent::init();
        $this->ajax = true;
    }

    public function postProcess()
    {
        $result = array('success' => false);

        // A customer may only delete their own saved cards. card_uuid is a string uuid:
        // the old (int) cast turned it into 0, which in MySQL matches every uuid that does
        // not start with a digit, deleting the cards of many customers at once.
        $customer = $this->context->customer;
        $card_uuid = Tools::getValue('card_uuid');

        if (Validate::isLoadedObject($customer) && $customer->isLogged()
            && is_string($card_uuid) && $card_uuid !== ''
        ) {
            $deleted = Db::getInstance()->delete(
                'pnp_paylands_cards',
                '`card_uuid` = "' . pSQL($card_uuid) . '" AND `customer_id` = ' . (int)$customer->id
            );
            $result['success'] = $deleted && Db::getInstance()->Affected_Rows() > 0;
        }

        $this->ajaxResponse = $result;
    }

    public function displayAjax()
    {
        $this->ajaxDie(Tools::jsonEncode($this->ajaxResponse));
    }
}
