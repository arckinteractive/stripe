<?php

access_show_hidden_entities(true);

$stripe = new StripeClient;

$has_more = true;
$starting_after = null;

while ($has_more) {

	$customers = $stripe->getCustomers(100, null, $starting_after, null);

	if ($customers->data && sizeof($customers->data)) {
		foreach ($customers->data as $customer) {
			
			// Check if the user with this customer id already exists
			$user = stripe_get_user_from_customer_id($customer->id);
			if (!$user) {
				// Check if user guid is supplied with customer metadata
				if (isset($customer->metadata->guid)) {
					$guid = $customer->metadata->guid;
					$user = get_entity($guid);
				}
			}

			if (!$user) {
				// Try mapping by email
				$users = get_user_by_email($customer->email);
				$user = ($users) ? $users[0] : false;
			}

			if ($user) {
				
				// Store any customer IDs this user might have for reference
				$stripe_ids = $user->stripe_customer_id;
				if (!$stripe_ids) {
					$stripe_ids = array();
				} else if (!is_array($stripe_ids)) {
					$stripe_ids = array($stripe_ids);
				}
				if (!in_array($customer->id, $stripe_ids)) {
					create_metadata($user->guid, 'stripe_customer_id', $customer->id, '', $user->guid, ACCESS_PUBLIC, true);
				}

				// Store current Customer ID
				if (!$user->getPrivateSetting('stripe_customer_id')) {
					$user->setPrivateSetting('stripe_customer_id', $customer->id);
				}
			} else {

				// Store customer IDs with their email reference locally
				// so that users can be assigned their existing customer ID upon registration
				$customer_ref = elgg_get_plugin_setting($customer->email, 'stripe');
				if ($customer_ref) {
					$customer_ref = unserialize($customer_ref);
				} else {
					$customer_ref = array();
				}
				if (!in_array($customer->id, $customer_ref)) {
					array_unshift($customer_ref, $customer->id);
				}
				elgg_set_plugin_setting($customer->email, serialize($customer_ref), 'stripe');
			}

			$starting_after = $customer->id;
		}
	}

	$has_more = $customers->has_more;
}

forward(REFERER);