<?php

$id = elgg_extract('id', $vars);

$user = elgg_get_page_owner_entity();

$stripe = new StripeClient();
$invoice = $stripe->getInvoice($id);

$title = elgg_echo('stripe:invoices:title', array($invoice->id));
echo elgg_view_module('info', elgg_echo('stripe:invoices:title', array($invoice->id)), elgg_view('stripe/objects/invoice', array(
	'object' => $invoice,
)));

if ($invoice->charge) {
	$charge = $stripe->getCharge($invoice->charge);

	if ($charge) {
		echo elgg_view_module('info', elgg_echo('stripe:charges:title', array($charge->id)), elgg_view('stripe/objects/charge', array(
			'object' => $charge,
		)));
	}
}

echo elgg_view_module('info', elgg_echo('stripe:invoices:items:title', array($id)), elgg_view('stripe/pages/invoices/items', array(
	'id' => $invoice->id,
)));

if ($invoice->subscription) {
	$subscription = $stripe->getSubscription($invoice->customer, $invoice->subscription);

	if ($subscription) {
		echo elgg_view_module('info', elgg_echo('stripe:subscriptions:title', array($subscription->id)), elgg_view('stripe/objects/subscription', array(
			'object' => $subscription,
		)));
	}
}

if (elgg_get_logged_in_user_guid() !== elgg_get_page_owner_guid()) {
	$customer = $stripe->getCustomer($invoice->customer);

	if ($customer) {
		echo elgg_view_module('info', elgg_echo('stripe:customers:title', array($customer->id)), elgg_view('stripe/objects/customer', array(
			'object' => $customer,
		)));
	}
}

//echo $stripe->viewErrors();
