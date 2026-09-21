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

<link href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" rel="stylesheet">
<style type="text/css">
    .fancybox-slide--iframe .fancybox-content {
        max-width: 80%;
        max-height: 80%;
        margin: 0;
    }
</style>

<div class="row">
    <div class="col-xs-12">
        {foreach from=$pnp_payment_entries item=entry}
        <p class="payment_module">
            <a class="bankwire pnp-payment-btn" href="javascript:void(0);"
               data-pnp-url="{$entry.url|escape:'htmlall':'UTF-8'}"
               data-pnp-id="{$entry.id|escape:'htmlall':'UTF-8'}">
                {if $entry.logo}
                <img src="{$entry.logo|escape:'htmlall':'UTF-8'}" alt="{$entry.name|escape:'htmlall':'UTF-8'}"/>
                {/if}
                {$entry.name|escape:'htmlall':'UTF-8'}
            </a>
        </p>
        {/foreach}
        <input type="hidden" name="redirect" value="">
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js"></script>
<script type="text/javascript"><!--
    $(".pnp-payment-btn").on('click', function (e) {
        var url = $(this).data('pnp-url');
        jQuery.fancybox.open({
            src: url,
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
