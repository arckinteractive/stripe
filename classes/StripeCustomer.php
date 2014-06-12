<?php

class StripeCustomer {

	/**
	 * Elgg user
	 * @var ElggUser
	 */
	protected $user;

	/**
	 * Stripe customer account
	 * @var Stripe_Customer
	 */
	protected $account;

	
	/**
	 * Create or retrieve a Stripe customer account
	 * @param mixed $user_attr	ElggUser or guid or email
	 * @throws IOException
	 */
	function __construct($user_attr = null) {

		if ($user_attr instanceof ElggUser) {
			$this->user = $user_attr;
		} else if (is_email_address($user_attr)) {
			$users = get_user_by_email($user_attr);
			if (!$users) {
				$customer_ref = elgg_get_plugin_setting($user_attr, 'stripe');
				if ($customer_ref) {
					$customer_ref = unserialize($customer_ref);
				} else {
					$customer_ref = array();
				}
				$customer_id = $customer_ref[0];
			} else {
				$this->user = $users[0];
			}
			$email = $user_attr;
		} else if (is_string($user_attr) && substr($user_attr, 0, 4) == 'cus_') {
			$customer_id = $user_attr;
		} else if (is_numeric($user_attr)) {
			$this->user = get_entity($user_attr);
		}

		if (!$this->user && $customer_id) {
			if ($user = stripe_get_user_from_customer_id($customer_id)) {
				$this->user = $user;
			}
		}

		if (!$this->user) {
			$this->user = new ElggUser;
			$this->user->email = $email;
			if ($customer_id) {
				$this->user->setPrivateSetting('stripe_customer_id', $customer_id);
			}
		}

		$this->account = $this->getCustomerAccount();
		
		if (!$this->account) {
			throw new IOException("Stripe customer account can not be retrieved or created");
		}
	}

	/**
	 * Get Stripe customer ID for the user
	 * @return string|boolean
	 */
	public function getCustomerId() {

		$customer_id = $this->user->getPrivateSetting('stripe_customer_id');

		if (!$customer_id) {

			$stripe = new StripeClient();

			// Try other customer IDs stored on this user
			$customer_ids = $this->user->stripe_customer_id;
			if ($customer_ids) {
				if (!is_array($customer_ids)) {
					$customer_ids = array($customer_ids);
				}
				foreach ($customer_ids as $customer_id) {
					$account = $stripe->getCustomer($customer_id);
					if ($account) {
						break;
					}
				}
			}

			if (!$account) {
				$account = $stripe->createCustomer($this->user);
			}

			$customer_id = $account->id;
			$this->user->setPrivateSetting('stripe_customer_id', $customer_id);
		}

		return $customer_id;
	}

	/**
	 * Retrieve a Stripe customer account
	 * @return Stripe_Customer|boolean
	 * @throws Stripe_Error
	 */
	public function getCustomerAccount() {

		if ($this->account->id) {
			return $this->account;
		}

		try {

			$customer_id = $this->getCustomerId();

			if (!$customer_id) {
				throw new Stripe_Error('No customer id');
			}

			$stripe = new StripeClient();
			$account = $stripe->getCustomer($customer_id);
			if (!$account->id || isset($account->deleted)) {
				throw new Stripe_Error('Customer does not exist or has been deleted');
			}
			return $account;
		} catch (Stripe_Error $e) {
			$this->user->removePrivateSetting('stripe_customer_id');
			error_log($e->getMessage());
			return $this->getCustomerAccount();
		}
	}

}
