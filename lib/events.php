<?php

/**
 * Setup menus at page setup
 */
function stripe_pagesetup() {

	elgg_register_menu_item('page', array(
		'name' => 'stripe:customers',
		'href' => 'admin/stripe/customers',
		'text' => elgg_echo('admin:stripe:customers'),
		'context' => 'admin',
		'section' => 'stripe',
	));

	$user = elgg_get_page_owner_entity();

	elgg_register_menu_item('page', array(
		'name' => 'stripe:cards',
		'href' => "billing/$user->username/cards/all",
		'text' => elgg_echo('stripe:cards:all'),
		'selected' => (substr_count(current_page_url(), 'cards/all')),
		'context' => 'settings',
		'section' => 'stripe',
	));

	elgg_register_menu_item('page', array(
		'name' => 'stripe:charges',
		'href' => "billing/$user->username/charges/all",
		'text' => elgg_echo('stripe:charges:all'),
		'selected' => (substr_count(current_page_url(), 'charges/all')),
		'context' => 'settings',
		'section' => 'stripe',
	));

	elgg_register_menu_item('page', array(
		'name' => 'stripe:invoices',
		'href' => "billing/$user->username/invoices/all",
		'text' => elgg_echo('stripe:invoices:all'),
		'selected' => (substr_count(current_page_url(), 'invoices/all')),
		'context' => 'settings',
		'section' => 'stripe',
	));

	elgg_register_menu_item('page', array(
		'name' => 'stripe:subscriptions',
		'href' => "billing/$user->username/subscriptions/all",
		'text' => elgg_echo('stripe:subscriptions:all'),
		'selected' => (substr_count(current_page_url(), 'subscriptions/all')),
		'context' => 'settings',
		'section' => 'stripe',
	));
}

/**
 * Check if the email of the user has already been associated with a Stripe customer
 * If so, map them
 *
 * @param string $event
 * @param string $type
 * @param ElggUser $user
 * @param true
 */
function stripe_register_user($event, $type, $user) {

	$customer_ref = elgg_get_plugin_setting($user->email, 'stripe');

	if (!$customer_ref) {
		return;
	}

	$customer_ref = unserialize($customer_ref);
	if (is_array($customer_ref) && sizeof($customer_ref)) {
		$user->setPrivateSetting('stripe_customer_id', $customer_ref[0]);
		$customer_ref = array_reverse($customer_ref);
		foreach ($customer_ref as $c_ref) {
			create_metadata($user->guid, 'stripe_customer_id', $c_ref, '', $user->guid, ACCESS_PUBLIC, true);
		}
	}

	elgg_unset_plugin_setting($user->email, 'stripe');
	return true;
}
