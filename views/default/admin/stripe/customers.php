<?php

$limit = get_input('limit', 10);
$ending_before = get_input('ending_before');
$starting_after = get_input('starting_after');

$stripe = new StripeClient();
$customers = $stripe->getCustomers($limit, $ending_before, $starting_after);

$mod = elgg_view('output/url', array(
	'text' => elgg_echo('stripe:customers:sync'),
	'href' => 'action/stripe/customers/sync',
	'is_action' => true,
	'class' => 'elgg-button elgg-button-action mam pam',
		));

echo elgg_view_module('main', elgg_echo('stripe:customers:sync'), $mod);


$mod2 = elgg_view('stripe/objects/list', array(
	'objects' => $customers,
	'starting_after' => $starting_after,
	'ending_before' => $ending_before,
	'limit' => $limit,
));

echo elgg_view_module('main', elgg_echo('stripe:customers:list'), $mod2);