<?php

$id = elgg_extract('id', $vars);

$user = elgg_get_page_owner_entity();

$stripe = new StripeClient();
$card = $stripe->getCard($user, $id);

$vars['object'] = $card;

echo elgg_view('stripe/objects/card', $vars);
