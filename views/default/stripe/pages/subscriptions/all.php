<?php
$user = elgg_get_page_owner_entity();

$starting_after = get_input('starting_after', null);
$ending_before = get_input('ending_before', null);
$limit = get_input('limit', 10);

$stripe = new StripeClient();
$cards = $stripe->getSubscriptions($user->guid, $limit, $ending_before, $starting_after);

echo elgg_view('stripe/objects/list', array(
	'objects' => $cards,
	'starting_after' => $starting_after,
	'ending_before' => $ending_before,
	'limit' => $limit,
		));
