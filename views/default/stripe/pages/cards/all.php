<?php
$user = elgg_get_page_owner_entity();

$starting_after = get_input('starting_after', null);
$ending_before = get_input('ending_before', null);
$limit = get_input('limit', 10);

$stripe = new StripeClient();
$cards = $stripe->getCards($user->guid, $limit, $ending_before, $starting_after);

$list = elgg_view('stripe/objects/list', array(
	'objects' => $cards,
	'starting_after' => $starting_after,
	'ending_before' => $ending_before,
	'limit' => $limit,
		));
echo elgg_view_module('aside', elgg_echo('stripe:cards:list'), $list);

$form = elgg_view_form('stripe/cards/add', array(
	'class' => 'stripe-form',
		), array(
	'entity' => $user,
		));
echo elgg_view_module('aside', elgg_echo('stripe:cards:add'), $form);
