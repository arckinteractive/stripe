<?php

// Composer autoload
require_once __DIR__ . '/vendors/autoload.php';

require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/events.php';
require_once __DIR__ . '/lib/hooks.php';
require_once __DIR__ . '/lib/page_handlers.php';

elgg_register_event_handler('init', 'system', array(__NAMESPACE__ . '\\StripeApiFactory', 'init'), 1);
elgg_register_event_handler('shutdown', 'system', array(__NAMESPACE__ . '\\StripeApiFactory', 'shutdown'));

elgg_register_event_handler('init', 'system', __NAMESPACE__ . '\\stripe_init');
elgg_register_event_handler('pagesetup', 'system', __NAMESPACE__ . '\\stripe_pagesetup');

function stripe_init() {

	// Register Stripe js
	elgg_register_js('stripe.js', 'https://js.stripe.com/v2/', 'head', 50);
	elgg_load_js('stripe.js');

	elgg_extend_view('js/initialize_elgg', 'js/stripe/config');
	elgg_extend_view('js/elgg', 'js/stripe/cards');

	elgg_extend_view('css/elgg', 'css/stripe/css');
	elgg_extend_view('css/admin', 'css/stripe/css');

	// Registering actions
	elgg_register_action('stripe/customers/sync', __DIR__ . '/actions/stripe/customers/sync.php', 'admin');
	elgg_register_action('stripe/cards/add', __DIR__ . '/actions/stripe/cards/add.php', 'public');
	elgg_register_action('stripe/cards/remove', __DIR__ . '/actions/stripe/cards/remove.php');
	elgg_register_action('stripe/cards/set_default', __DIR__ . '/actions/stripe/cards/make_default.php');
	elgg_register_action('stripe/subscriptions/cancel', __DIR__ . '/actions/stripe/subscriptions/cancel.php');

	// Page handler
	elgg_register_page_handler('billing', 'stripe_page_handler');

	elgg_register_plugin_hook_handler('register', 'menu:stripe-actions', 'stripe_actions_menu');

	elgg_register_plugin_hook_handler('ping', 'stripe.events', 'stripe_ping_event');

	elgg_register_plugin_hook_handler('customer.created', 'stripe.events', 'stripe_customer_created_event');
	elgg_register_plugin_hook_handler('customer.deleted', 'stripe.events', 'stripe_customer_deleted_event');

	elgg_register_plugin_hook_handler('charge.succeeded', 'stripe.events', 'stripe_charge_succeeded_event');
	elgg_register_plugin_hook_handler('charge.failed', 'stripe.events', 'stripe_charge_failed_event');
	elgg_register_plugin_hook_handler('charge.refunded', 'stripe.events', 'stripe_charge_refunded_event');

	// Stripe Webhooks
	elgg_ws_expose_function('stripe.webhooks', 'stripe_webhook_handler', array(
		'environment' => array(
			'type' => 'string',
			'required' => true,
		)), 'Handles webhooks received from Stripe', 'POST', false, false);

	// Map newly registered users to their Stripe profiles if any
	elgg_register_event_handler('create', 'user', 'stripe_register_user');
}

/**
 * Handle Stripe webhooks
 */
function stripe_webhook_handler($environment) {

	$body = get_post_data();
	$event_json = json_decode($body);
	$event_id = $event_json->id;

	$gateway = new StripeClient($environment);
	$event = $gateway->getEvent($event_id);

	if (!$event) {
		return array(
			'success' => false,
			'message' => 'Stripe Event for this webhook was not found',
		);
	}

	$ia = elgg_set_ignore_access(true);
	$ha = access_get_show_hidden_status();
	access_show_hidden_entities(true);

	$result = elgg_trigger_plugin_hook_handler($event->type, 'stripe.events', array(
		'environment' => $environment,
		'event' => $event,
			), array(
		'success' => true,
	));

	access_show_hidden_entities($ha);
	elgg_set_ignore_access($ia);

	return $result;
}
