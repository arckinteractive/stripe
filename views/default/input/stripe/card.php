<?php

$user = elgg_extract('entity', $vars, elgg_get_logged_in_user_entity());

$stripe = new StripeClient();
$cards = $stripe->getCards($user->guid);

$options_values = array('' => elgg_echo('stripe:cards:select'));
if ($cards->data) {
	foreach ($cards->data as $card) {
		$options_values[$card->id] = "{$card->type}-{$card->last4} ({$card->exp_month} / {$card->exp_year})";
	}
}
$options_values['__new__'] = elgg_echo('stripe:cards:add');

$vars['options_values'] = $options_values;

$name = elgg_extract('name', $vars, 'stripe-token');
$value = elgg_extract('value', $vars, '');

if (!sizeof($cards->data)) {
	$hidden = ' hidden';
	echo elgg_view('output/url', array(
		'text' => elgg_echo('stripe:cards:add'),
		'href' => 'billing/add_card/' . $user->username,
		'class' => 'elgg-button elgg-button-action stripe-cards-no-picker',
	));
}
echo elgg_view('input/dropdown', array(
	'name' => $name,
	'value' => $value,
	'options_values' => $options_values,
	'class' => 'stripe-cards-picker' . $hidden,
	'data-endpoint' => 'billing/add_card/' . $user->username,
));
