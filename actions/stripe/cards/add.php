<?php

$token = get_input('stripe-token');

$guid = get_input('guid');
$email = get_input('email');
$customer_id = get_input('customer_id');

if ($guid) {
	$attr = $guid;
} else if ($email) {
	$attr = $email;
} else if ($customer_id) {
	$attr = $customer_id;
} else {
	$attr = elgg_get_logged_in_user_guid();
}

$stripe = new StripeClient();
$card = $stripe->createCard($attr, $token);

if ($card) {
	system_message(elgg_echo('stripe:cards:add:success'));

	if (elgg_is_xhr()) {
		echo json_encode(array(
			'label' => "{$card->type}-{$card->last4} ({$card->exp_month} / {$card->exp_year})",
			'id' => $card->id,
			'view' => elgg_view('stripe/objects/card', array(
				'object' => $card
			)),
		));
	}
} else {
	register_error(elgg_echo('stripe:cards:add:error'));
	$stripe->showErrors();
}

forward(REFERER);
