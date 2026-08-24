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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
          integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <title>{l s='Your Cards' mod='paylands'}</title>
    <style type="text/css" media="all">
        #pnp-process {
            width: 300px;
            min-height: 500px;
            margin: 0 auto;
        }

        .vertical-center {
            min-height: 100%; /* Fallback for browsers do NOT support vh unit */
            min-height: 100vh; /* These two lines are counted as one :-)       */

            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
<div id="pnp-process" class="vertical-center">
    <div class="container">
        <div class="row align-items-center justify-content-center">
            <div class="col-sm-12">
                <div class="card align-middle">
                    <div class="card text-center">
                        <div class="card-header">
                            <div>
                                <img style="max-width: 200px"
                                     src="{$logo|escape:'htmlall':'UTF-8'}">
                            </div>
                            <h3 class="panel-title">{l s='Choose a Card' mod='paylands'}</h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                {foreach from=$customer_cards key=index item=card}
                                    <li class="list-group-item">
                                        <label for="card-id-{$index}">
                                            <input name="card_uui" id="card-id-{$index}>"
                                                   class="form-check-input" type="radio"
                                                   value="{$card.card_uuid|escape:'htmlall':'UTF-8'}"
                                                   onclick="enableButtons()">
                                            {$card.brand|escape:'htmlall':'UTF-8'} {l s='Ending In' mod='paylands'}
                                            .....{$card.last4|escape:'htmlall':'UTF-8'} {l s='Expires' mod='paylands'}
                                            : {$card.expire_month|escape:'htmlall':'UTF-8'}
                                            / {$card.expire_year|escape:'htmlall':'UTF-8'}
                                        </label>
                                        <div>
                                            <button class="btn btn-danger badge badge-danger delete-card"
                                                    data-id="{$card.card_uuid|escape:'htmlall':'UTF-8'}"
                                                    disabled>{l s='Delete' mod='paylands'}
                                            </button>
                                        </div>
                                    </li>
                                {/foreach}
                                <li class="list-group-item">
                                    <button type="button" class="btn btn-link"
                                            id="pay-with-new-cart">{l s='Pay With New Card' mod='paylands'}</button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-footer text-muted">
                            <button type="button" id="btn-submit" disabled
                                    class="btn btn-primary btn-lg btn-block">{l s='Continue' mod='paylands'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script type="text/javascript"><!--
    function disableButtons() {
        $('#btn-submit').attr('disabled', 'disabled');
        $('.delete-card').attr('disabled', 'disabled');
        $('#pay-with-new-cart').attr('disabled', 'disabled');
        $("input").attr('disabled', 'disabled');
        return true;
    }

    function enableButtons() {
        $('#btn-submit').removeAttr('disabled');
        $('#pay-with-new-cart').removeAttr('disabled');
        $('input').removeAttr('disabled');
        return true;
    }

    $("input.form-check-input").on('click', function (e) {
        $(this).closest(".list-group-item").find('.delete-card').removeAttr('disabled');
        enableButtons();
    });

    $("#pay-with-new-cart").on('click', function () {
        var self = $(this);
        $.ajax({
            url: "{$link->getModuleLink('paylands', 'paywithform', ['id' => {$cart_id}], true)}",
            dataType: 'json',
            method: "POST",
            beforeSend: function () {
                disableButtons();
                self.html("<span class='spinner-border spinner-border-sm' role='status' aria-hidden='true'></span> {l s='Loading...' mod='paylands'}");
            },
            success: function (response) {
                if (response['redirect']) {
                    window.location.replace(response['redirect']);
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    });

    $('#btn-submit').on('click', function () {
        var card_uuid = $("input[name='card_uui']").val();
        var self = $(this);
        $.ajax({
            url: "{$link->getModuleLink('paylands', 'paywithcard', ['id' => {$cart_id}], true)}",
            dataType: 'json',
            method: "POST",
            data: {literal}{card_uuid: card_uuid}{/literal},
            beforeSend: function () {
                disableButtons();
                self.html("<span class='spinner-border spinner-border-sm' role='status' aria-hidden='true'></span>  {l s='Loading...' mod='paylands'}");
            },
            success: function (response) {
                if (response['redirect']) {
                    window.location.replace(response['redirect']);
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    });

    $('.delete-card').on('click', function () {
        var self = $(this);
        var id = self.data('id');
        $.ajax({
            url: "{$link->getModuleLink('paylands', 'deletecard', ['id' => {$cart_id}], true)}",
            dataType: 'json',
            method: "POST",
            data: {literal}{card_uuid: id}{/literal},
            beforeSend: function () {
                disableButtons();
            },
            success: function (response) {
                if (response['success'] === true) {
                    self.closest('.list-group-item').fadeOut();
                    enableButtons();
                    $('#btn-submit').attr('disabled', 'disabled');
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
        });
    });
    //--></script>
</body>
</html>