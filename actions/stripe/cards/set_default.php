<?php

$card_id = get_input('card_id');
$customer_id = get_input('customer_id');

$user = stripe_get_user_from_customer_id($customer_id);

if (!elgg_instanceof($user) || !$user->canEdit()) {
	register_error(elgg_echo('stripe:access_error'));
	forward(REFERER);
}

$stripe = new StripeClient();
if ($stripe->setDefaultCard($user->guid, $card_id)) {
	system_message(elgg_echo('stripe:cards:make_default:success'));
} else {
	register_error(elgg_echo('stripe:cards:make_default:error'));
	$stripe->showErrors();
}

forward(REFERER);
