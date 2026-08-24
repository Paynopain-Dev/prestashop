
var paylandsModel = {
	/**
	 * Initialize paylands 3DSecure
	 * @param data
	 * @constructor
	 */
	Initialize3Dsecure: function(source_uuid) {
		$.ajax({
			url: paylandConfig.secure_url,
			data: {uuid: source_uuid, ajax:1},
			success: function (response) {
				var response = JSON.parse(response);
				window.location.href = response.url;
			},
			error: function () {
				$(".paylands-container .loader").hide();
				$(".paylands-messages").html(paylandConfig.translations.errorServer);
				$(".paylands-messages").show();
				$("#payment-confirmation button").removeAttr("disabled");
			}
		});
	},
	/**
	 * Place order if payment is not secure
	 * @param data
	 */
	placeOrder: function(source_uuid) {
		$.ajax({
			url: paylandConfig.place_url,
			method: 'POST',
			data: {uuid: source_uuid, ajax:1},
			success: function (response) {
				if(response.success) {
					window.location.href = response.data.url;
				} else {
					$(".paylands-container .loader").hide();
					$(".paylands-messages").html(response.data.message);
					$(".paylands-messages").show();
					$("#payment-confirmation button").removeAttr("disabled");
				}
			},
			error: function () {
				$(".paylands-container .loader").hide();
				$(".paylands-messages").html(paylandConfig.translations.errorServer);
				$(".paylands-messages").show();
				$("#payment-confirmation button").removeAttr("disabled");
			}
		});
	}
}
$(document).ready(function (){

	/**
	 * Check for error parameter
	 */
	if(window.location.href.indexOf("paylands-error") > 0) {
		$('#content').prepend('<p class="paylands-error"> ERROR: ' + paylandConfig.translations.payment_error + '</p>')
	}
	/**
	 * show form for a new card
	 */
	$(document).on("click",'input[name="paylands_card"]', function() {
		let input = $(this);
		if (input.val() === "custom") {
			$('.custom-form').show();
		} else {
			$('.custom-form').hide();
		}
	});

	/**
	 * Load paylands form
	 */
	$(document).on("click",'input[name="payment-option"]', function() {
		let input = $(this);
		if(input.data("module-name") === "paylands") {
			$(".paylands-messages").hide();
			window.paylands.setTemplate(paylandConfig.template);
			window.paylands.setMode(paylandConfig.mode);
			window.paylands.initializate(paylandConfig.token,"paylands-frame");
		}
	});

	/**
	 * Handle action when the customer place order
	 */
	$(document).on("submit","#payment-form", function (event) {
		let input = $('input[name="payment-option"]:checked');
		if(input.data("module-name") === "paylands") {
			event.preventDefault();
			event.stopPropagation();
			$(".paylands-container .loader").show();
			$('.paylands-cards-messages').hide();
			$(".paylands-messages").hide();
			let cardNumber = $('input[name="paylands_card"]:checked').val();

			if (paylandConfig.hasCards &&  paylandConfig.save_card) {
				if (typeof cardNumber === 'undefined') {
					console.log("Show error");
					$('.paylands-cards-messages').html(paylandConfig.translations.select_card);
					$('.paylands-cards-messages').show();
					$(".paylands-container .loader").hide();
				} else if (cardNumber == 'custom') {
					console.log("Saving new card");
					window.paylands.storeSourceCard();
				} else {
					console.log("Process the payment");
					if (paylandConfig.is_secure) {
						paylandsModel.Initialize3Dsecure(cardNumber);
					} else {
						paylandsModel.placeOrder(cardNumber);
					}
				}
			} else {
				window.paylands.storeSourceCard();
			}
		}
	});

	/**
	 * Show message if paylands send an error
	 */
	$(document).on("error",function(event) {
		$(".paylands-container .loader").hide();
		$(".paylands-messages").html(paylandConfig.translations.error);
		$(".paylands-messages").show();
		$("#payment-confirmation button").removeAttr("disabled");
	});

	/**
	 * Show message if paylands send an error
	 */
	$(document).on("errorServer",function(event) {
		$(".paylands-container .loader").hide();
		$(".paylands-messages").html(paylandConfig.translations.errorServer);
		$(".paylands-messages").show();
		$("#payment-confirmation button").removeAttr("disabled");
	});

	/**
	 * Process order payment
	 */
	$(document).on("savedCard",function(event) {
		if(paylandConfig.is_secure) {
			paylandsModel.Initialize3Dsecure(event.originalEvent.data.source.uuid);
		} else {
			paylandsModel.placeOrder(event.originalEvent.data.source.uuid);
		}
	});
});
