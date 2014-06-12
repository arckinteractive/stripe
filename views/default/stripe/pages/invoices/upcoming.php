<?php

$user = elgg_get_page_owner_entity();

$subscription_id = elgg_extract('id', $vars, null);

$stripe = new StripeClient();
$invoice = $stripe->getUpcomingInvoice($user, $subscription_id);

echo elgg_view_module('info', elgg_echo('stripe:invoices:upcoming'), elgg_view('stripe/objects/invoice', array(
	'object' => $invoice,
)));

$list = elgg_view('stripe/objects/list', array(
	'objects' => $invoice->lines,
		));

echo elgg_view_module('info', elgg_echo('stripe:invoices:items'), $list);

if ($invoice->subscription) {
	$subscription = $stripe->getSubscription($invoice->customer, $invoice->subscription);

	if ($subscription) {
		echo elgg_view_module('info', elgg_echo('stripe:subscriptions:title', array($subscription->id)), elgg_view('stripe/objects/subscription', array(
			'object' => $subscription,
		)));
	}
}