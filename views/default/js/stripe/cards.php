//<script>
	elgg.provide('elgg.stripe');

	elgg.stripe.cards = function() {

		Stripe.setPublishableKey(elgg.config.stripePublishableKey);

		$('form:has(fieldset[data-stripe])').on('submit', elgg.stripe.submitCardForm);

		$('.stripe-cards-picker').on('change', function(e) {
			var $elem = $(this);
			if ($elem.val() === '__new__') {
				elgg.get($elem.data('endpoint'), {
					data: $elem.data(),
					beforeSend: function() {
						elgg.stripe.paymentMethodPicker = $elem;
						$('body').addClass('stripe-state-loading');
					},
					success: function(data) {
						if (data) {
							$(data).find('form').data('picker', true);
							elgg.stripe.paymentMethodPickerModal = elgg.stripe.lightboxOpen(data, 600);
							$('form:has(fieldset[data-stripe])').on('submit', elgg.stripe.submitCardForm);
						}
					},
					error: function() {
						elgg.register_error(elgg.echo('stripe:error:ajax'));
					},
					complete: function() {
						$('body').removeClass('stripe-state-loading');
					}
				});
			}
			return true;
		});
		$('.stripe-cards-no-picker').on('click', function(e) {
			e.preventDefault();
			var $elem = $(this);
			elgg.get($elem.attr('href'), {
				beforeSend: function() {
					elgg.stripe.paymentMethodPicker = $elem.next('.stripe-cards-picker');
					$('body').addClass('stripe-state-loading');
				},
				success: function(data) {
					if (data) {
						$(data).find('form').data('picker', true);
						elgg.stripe.paymentMethodPickerModal = elgg.stripe.lightboxOpen(data, 600);
						$('form:has(fieldset[data-stripe])').on('submit', elgg.stripe.submitCardForm);
					}
				},
				error: function() {
					elgg.register_error(elgg.echo('stripe:error:ajax'));
				},
				complete: function() {
					$('body').removeClass('stripe-state-loading');
				}
			});
		});

	};

	elgg.stripe.submitCardForm = function(e) {

		var $form = $(this);

		// Disable the submit button to prevent repeated clicks
		var $submit = $form.find('input[type="submit"]');
		$submit.data('text', $submit.val());
		$submit.addClass('elgg-state-disabled').val(elgg.echo('stripe:cards:validating')).prop('disabled', true);

		if (!$form.has('fieldset[data-stripe]')) {
			return true;
		}

		Stripe.card.createToken($form, function(status, response) {

			if (response.error) {
				// Show the errors on the form
				$form.find('.stripe-errors').text(response.error.message);
				$form.find('input[type="submit"]').removeClass('elgg-state-disabled').val($submit.data('text')).prop('disabled', false);
			} else {

				// Unbind the submit event
				$form.off('submit', elgg.stripe.submitCardForm);

				$form.prepend($('<input>').attr({'type': 'hidden', 'name': 'stripe-token'}).val(response.id));
				if ($form.find('[name="stripe-remember"]').is(':checked')) {
					elgg.action('action/stripe/cards/add', {
						data: $form.serialize(),
						beforeSend: function() {
							$form.find('input[type="submit"]').val(elgg.echo('stripe:cards:saving'));
						},
						success: function(data) {
							if (data.status >= 0) {
								response.id = data.output.id;
								response.view = data.output.view;
								$('#cards-list').prepend(data.output.view);
								elgg.stripe.updateCards(response);
							}
						},
						complete: function() {
							$form.find('fieldset[data-stripe]').remove();
						}
					});
				} else {
					var submitForm = elgg.stripe.updateCards(response);
					if (submitForm) {
						$form.submit();
					} else {
						$form.find('fieldset[data-stripe]').remove();
					}
				}
			}
		});

		return false;
	};

	elgg.stripe.updateCards = function(response) {

		if (elgg.stripe.paymentMethodPicker) {
			$('.stripe-cards-no-picker').hide();
			$('.stripe-cards-picker').each(function(e) {
				var $picker = $(this).show();
				var label = response.type + '-' + response.last4 + '(' + response.exp_month + ' / ' + response.exp_year + ')';
				var $option = $('<option>').attr('value', response.id).text(label);
				$('option:eq(0)', $picker).after($option);
			});

			elgg.stripe.paymentMethodPicker.val(response.id);
			if (elgg.stripe.paymentMethodPickerModal) {
				elgg.stripe.lightboxClose(elgg.stripe.paymentMethodPickerModal);
			}
			return false;
		} else {
			return true;
		}
	};

	elgg.stripe.lightboxOpen = function(content, width) {

		var $modal = $('<div>');
		$modal.html(content).dialog({
			title: null,
			dialogClass: 'stripe-modal',
			width: (width) ? width : 600,
			modal: true,
			close: function() {
				$(this).dialog('destroy').remove();
			},
			position: {
				my: "center",
				at: "center",
				of: window
			}
		});
		if (typeof $.fn.parsley !== 'undefined') {
			$('#gateway-modal').find('form').parsley();
		}

		return $modal;
	};

	elgg.stripe.lightboxClose = function($modal) {
		$modal.dialog('close');
	};

	elgg.register_hook_handler('init', 'system', elgg.stripe.cards);