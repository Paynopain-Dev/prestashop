{*
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
*}

<div class="row">
    <div class="col-xs-12">
        <p class="payment_module">
            <a class="bankwire" id="paylands_payment_button" href="javascript:void(0);">
                <img src="{$module_dir|escape:'htmlall':'UTF-8'}logo.png"
                     alt="{l s='Pay with Card Safely' mod='paylands'}"/>
                {l s='Pay with Card Safely' mod='paylands'}
            </a>
        </p>
        <input type="hidden" name="redirect" value="">
        <section>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" rel="stylesheet">
            <style type="text/css">
                .fancybox-slide--iframe .fancybox-content {
                    max-width: 80%;
                    max-height: 80%;
                    margin: 0;
                }
            </style>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
            <input type="hidden" name="redirect" value="">
            <script type="text/javascript"><!--
                $("#paylands_payment_button").on('click', function (e) {
                    jQuery.fancybox.open({
                        src: "{$link->getModuleLink('paylands', 'payment', [], true)|escape:'htmlall':'UTF-8'}",
                        type: 'iframe',
                        iframe: {
                            css: {
                                width: '600px'
                            }
                        },
                        beforeClose: function (instance, current, e) {
                            var redirect = jQuery("input[name='redirect']").val();
                            if (redirect === '') {
                                return false;
                            }
                            window.location.replace(redirect);
                        }
                    });
                });
                //--></script>
        </section>
    </div>
</div>