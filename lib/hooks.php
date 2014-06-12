<?php

/**
 * Create a Stripe object menu
 *
 * @param string $hook		Equals 'register'
 * @param string $type		Equals 'stripe-actions'
 * @param array $return		Current menu
 * @param array $params		Additional params
 * @return array
 */
function stripe_actions_menu($hook, $type, $return, $params) {

	$object = elgg_extract('object', $params);

	switch ($object->object) {

		case 'card' :

			$user = stripe_get_user_from_customer_id($object->customer);

			if (!elgg_instanceof($user) || !$user->canEdit()) {
				return $return;
			}

			$stripe = new StripeClient();
			$default = $stripe->getDefaultCard($user->guid);

			if ($default->id == $object->id) {
				$return[] = ElggMenuItem::factory(array(
							'name' => 'default',
							'text' => elgg_echo('stripe:cards:default'),
							'href' => false,
				));
			} else {
				$return[] = ElggMenuItem::factory(array(
							'name' => 'default',
							'text' => elgg_echo('stripe:cards:make_default'),
							'href' => "action/stripe/cards/set_default?card_id={$object->id}&customer_id={$object->customer}",
							'is_action' => 800,
							'class' => 'elgg-requires-confirmation',
							'rel' => elgg_echo('question:areyousure'),
				));
				$return[] = ElggMenuItem::factory(array(
							'name' => 'remove',
							'text' => elgg_echo('stripe:cards:remove'),
							'href' => "action/stripe/cards/set_default?card_id={$object->id}&customer_id={$object->customer}",
							'is_action' => true,
							'priority' => 900,
							'class' => 'elgg-requires-confirmation',
							'rel' => elgg_echo('question:areyousure'),
				));
			}
			break;

		case 'charge' :

			$user = stripe_get_user_from_customer_id($object->customer);

			if (!elgg_instanceof($user) || !$user->canEdit()) {
				return $return;
			}

			$full = elgg_normalize_url("billing/{$user->username}/charges/view/{$object->id}");

			if (current_page_url() !== $full) {
				$return[] = ElggMenuItem::factory(array(
							'name' => 'details',
							'text' => elgg_echo('stripe:charges:view'),
							'href' => $full,
				));
			}

			break;

		case 'invoice' :

			$user = stripe_get_user_from_customer_id($object->customer);

			if (!elgg_instanceof($user) || !$user->canEdit()) {
				return $return;
			}

			if (isset($object->id)) {
				$full = elgg_normalize_url("billing/{$user->username}/invoices/view/{$object->id}");
				if (current_page_url() !== $full) {
					$return[] = ElggMenuItem::factory(array(
								'name' => 'details',
								'text' => elgg_echo('stripe:invoices:view'),
								'href' => $full,
					));
				}
			}
			break;

		case 'subscription' :

			$user = stripe_get_user_from_customer_id($object->customer);

			if (!elgg_instanceof($user) || !$user->canEdit()) {
				return $return;
			}

			$upcoming = elgg_normalize_url("billing/{$user->username}/invoices/upcoming/{$object->id}");

			if (current_page_url() !== $upcoming) {
				$return[] = ElggMenuItem::factory(array(
							'name' => 'details',
							'text' => elgg_echo('stripe:invoices:upcoming'),
							'href' => $upcoming,
				));
			}

			if (!$object->cancel_at_period_end) {
				$return[] = ElggMenuItem::factory(array(
							'name' => 'cancel',
							'text' => elgg_echo('subscriptions:cancel'),
							'href' => "action/stripe/subscriptions/cancel?subscription_id={$object->id}&customer_id={$object->customer}",
							'is_action' => 800,
							'class' => 'elgg-requires-confirmation',
							'rel' => elgg_echo('question:areyousure'),
				));
			}

			break;
	}

	return $return;
}

/**
 * Stripe ping
 * @param string $hook	Equals 'ping'
 * @param string $type	Equals 'stripe.event'
 * @param array $return
 * @param array $params
 * @return array
 */
function stripe_ping_event($hook, $type, $return, $params) {

	return array(
		'success' => true,
		'message' => 'Ping succeeded',
	);
}

/**
 * Webhook on a new customer
 * Adds a customer id to the user with the customer email, or stores a customer id as a plugin setting
 * 
 * @param string $hook	Equals 'customer.created'
 * @param string $type	Equals 'stripe.event'
 * @param array $return
 * @param array $params
 * @return array
 */
function stripe_customer_created_event($hook, $type, $return, $params) {

	$event = elgg_extract('event', $params);

	if (!$event instanceof Stripe_Event) {
		return $return;
	}

	$customer = $event->data->object;

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

	return array(
		'success' => true,
	);
}


/**
 * Webhook on a deleted customer
 * Removes a customer id from the user
 *
 * @param string $hook	Equals 'customer.deleted'
 * @param string $type	Equals 'stripe.event'
 * @param array $return
 * @param array $params
 * @return array
 */
function stripe_customer_deleted_event($hook, $type, $return, $params) {

	$event = elgg_extract('event', $params);

	if (!$event instanceof Stripe_Event) {
		return $return;
	}

	$customer = $event->data->object;

	// Check if the user with this customer id already exists
	$user = stripe_get_user_from_customer_id($customer->id);
	
	if ($user) {

		// Store any customer IDs this user might have for reference
		$stripe_ids = $user->stripe_customer_id;
		if (!$stripe_ids) {
			$stripe_ids = array();
		} else if (!is_array($stripe_ids)) {
			$stripe_ids = array($stripe_ids);
		}

		$key = array_search($customer->id, $stripe_ids);
		if ($key) {
			unset($stripe_ids[$key]);
			$user->stripe_customer_id = $stripe_ids;
		}

		// Store current Customer ID
		if ($user->getPrivateSetting('stripe_customer_id') == $customer->id) {
			$user->removePrivateSetting('stripe_customer_id');
		}
	}

	return array(
		'success' => true,
	);
}

/**
 * Webhook on successful charge
 * Sends out a notification to the user with a corresponding customer id
 *
 * @param string $hook	Equals 'charge.succeeded'
 * @param string $type	Equals 'stripe.event'
 * @param array $return
 * @param array $params
 * @return array
 */
function stripe_charge_succeeded_event($hook, $type, $return, $params) {

	$event = elgg_extract('event', $params);

	if (!$event instanceof Stripe_Event) {
		return $return;
	}

	$charge = $event->data->object;
	$customer_id = $charge->customer;

	$customer = stripe_get_user_from_customer_id($customer_id);

	if ($customer) {

		if ($merchant_guid = $charge->metadata->merchant_guid) {
			$merchant = get_entity($merchant_guid);
		} else {
			$merchant = elgg_get_site_entity();
		}

		$amount = new StripePricing($charge->amount / 100, 0, 0, $charge->currency);

		$subject = elgg_echo('stripe:notification:charge:succeeded:subject', array($merchant->name));
		$body = elgg_echo('stripe:notification:charge:succeeded:body', array(
			$customer->name,
			$amount,
			$merchant->name,
			$charge->card->type,
			$charge->card->last4,
			elgg_view('output/url', array('href' => elgg_normalize_url("billing/$customer->username/charges/all")))
		));

		if (notify_user($customer->guid, $merchant->guid, $subject, $body)) {
			return array(
				'success' => true,
				'message' => 'User has been notified about the successful payment'
			);
		}
	}

	return array(
		'success' => false,
		'message' => 'Notification failed or user does not exist'
	);
}

/**
 * Webhook on failed charge
 * Sends out a notification to the user with a corresponding customer id
 *
 * @param string $hook	Equals 'charge.failed'
 * @param string $type	Equals 'stripe.event'
 * @param array $return
 * @param array $params
 * @return array
 */
function stripe_charge_failed_event($hook, $type, $return, $params) {

	$event = elgg_extract('event', $params);

	if (!$event instanceof Stripe_Event) {
		return $return;
	}

	$charge = $event->data->object;
	$customer_id = $charge->customer;

	$customer = stripe_get_user_from_customer_id($customer_id);

	if ($customer) {

		if ($merchant_guid = $charge->metadata->merchant_guid) {
			$merchant = get_entity($merchant_guid);
		} else {
			$merchant = elgg_get_site_entity();
		}

		$amount = new StripePricing($charge->amount / 100, 0, 0, $charge->currency);

		$subject = elgg_echo('stripe:notification:charge:failed:subject', array($merchant->name));
		$body = elgg_echo('stripe:notification:charge:failed:body', array(
			$customer->name,
			$amount,
			$merchant->name,
			$charge->card->type,
			$charge->card->last4,
			$charge->failure_message,
			elgg_view('output/url', array('href' => elgg_normalize_url("billing/$customer->username/charges/all")))
		));

		if (notify_user($customer->guid, $merchant->guid, $subject, $body)) {
			return array(
				'success' => true,
				'message' => 'User has been notified about the failed payment',
			);
		}
	}

	return array(
		'success' => false,
		'message' => 'Notification failed or user does not exist',
	);
}

/**
 * Webhook on refunded charge
 * Sends out a notification to the user with a corresponding customer id
 *
 * @param string $hook	Equals 'charge.refunded'
 * @param string $type	Equals 'stripe.event'
 * @param array $return
 * @param array $params
 * @return array
 */
function stripe_charge_refunded_event($hook, $type, $return, $params) {

	$event = elgg_extract('event', $params);

	if (!$event instanceof Stripe_Event) {
		return $return;
	}

	$charge = $event->data->object;
	$customer_id = $charge->customer;

	$customer = stripe_get_user_from_customer_id($customer_id);

	if ($customer) {

		if ($merchant_guid = $charge->metadata->merchant_guid) {
			$merchant = get_entity($merchant_guid);
		} else {
			$merchant = elgg_get_site_entity();
		}

		$amount = new StripePricing($charge->refunds[0]->amount / 100, 0, 0, $charge->refunds[0]->currency);

		$subject = elgg_echo('stripe:notification:charge:refunded:subject', array($merchant->name));
		$body = elgg_echo('stripe:notification:charge:refunded:body', array(
			$customer->name,
			$amount,
			$merchant->name,
			$charge->card->type,
			$charge->card->last4,
			elgg_view('output/url', array('href' => elgg_normalize_url("billing/$customer->username/charges/all")))
		));

		if (notify_user($customer->guid, $merchant->guid, $subject, $body)) {
			return array(
				'success' => true,
				'message' => 'User has been notified about the refund',
			);
		}
	}

	return array(
		'success' => false,
		'message' => 'Notification failed or user does not exist',
	);
}
