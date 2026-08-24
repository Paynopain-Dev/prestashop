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

class paylandsLandingokModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $cart = new Cart((int)Tools::getValue('id'));
        if (!$this->module->checkCurrency($cart)) {
            Tools::redirect('index.php?controller=order');
        }

        $customer = new Customer($cart->id_customer);
        $success = true;
        $url_redirect = '';

        if (!Validate::isLoadedObject($customer)) {
            $success = false;
            $url_redirect = 'index.php?controller=order&step=1';
        }

        $logo = trim(Configuration::get('PNP_LOGO'));
        if (empty($logo)) {
            $logo = $this->module->getModuleUrl() . '/views/img/paylands-logo-dark.png';
        }

        $face_color = trim(Configuration::get('PNP_FACE_COLOR'));
        if (empty($face_color) || strpos($face_color, "#") === false) {
            $face_color = '#ef0643';
        }

        if ($success) {
            $order_id = 0;
            if (_PS_VERSION_ >= 1.7) {
                $order_id = (int)Order::getIdByCartId((int)$cart->id);
            } else {
                $order_id = (int)Order::getOrderByCartId($cart->id);
            }
            $url_redirect = 'index.php?controller=order-confirmation&id_cart=' . (int)$cart->id . '&id_module=' . (int)$this->module->id . '&id_order=' . $order_id . '&key=' . $customer->secure_key;
        }

        $this->context->smarty->assign(
            array(
                'logo' => $logo,
                'url_redirect' => $url_redirect,
                'face_color' => $face_color
            )
        );

        if (_PS_VERSION_ >= 1.7) {
            $this->setTemplate('module:paylands/views/templates/front/ok.tpl');
        } else {
            die($this->context->smarty->fetch(_PS_MODULE_DIR_.$this->module->name . '/views/templates/front/ok.tpl'));
        }
    }
}
