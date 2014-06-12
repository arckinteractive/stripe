<?php

$id = elgg_extract('id', $vars);

$user = elgg_get_page_owner_entity();

$stripe = new StripeClient();
$charge = $stripe->getCharge($id);

$title = elgg_echo('stripe:charges:title', array($charge->id));
echo elgg_view_module('info', elgg_echo('stripe:charges:title', array($charge->id)), elgg_view('stripe/objects/charge', array(
	'object' => $charge,
)));

if ($charge->invoice) {
	$invoice = $stripe->getInvoice($charge->invoice);

	echo elgg_view_module('info', elgg_echo('stripe:invoices:title', array($invoice->id)), elgg_view('stripe/objects/invoice', array(
		'object' => $invoice,
	)));
}

if (elgg_get_logged_in_user_guid() !== elgg_get_page_owner_guid()) {
	$customer = $stripe->getCustomer($charge->customer);

	echo elgg_view_module('info', elgg_echo('stripe:customers:title', array($customer->id)), elgg_view('stripe/objects/customer', array(
		'object' => $customer,
	)));
}