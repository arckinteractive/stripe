<?php

$id = elgg_extract('id', $vars);

if (!$id) {
	return;
}

$starting_after = get_input('starting_after', null);
$ending_before = get_input('ending_before', null);
$limit = get_input('limit', 100);

$stripe = new StripeClient();
$items = $stripe->getInvoiceItems($user->guid, $id, $limit, $ending_before, $starting_after);

echo elgg_view('stripe/objects/list', array(
	'objects' => $items,
	'starting_after' => $starting_after,
	'ending_before' => $ending_before,
	'limit' => $limit,
));