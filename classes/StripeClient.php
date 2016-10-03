<?php

class StripeClient {

	/**
	 * Customer object
	 * @var mixed bool/object
	 */
	private $customer = null;

	/**
	 * Error log
	 * @var array
	 */
	protected $log = array();

	/**
	 * Constructs a new Stripe instance
	 * @return StripeClient
	 */
	function __construct($attribute=null) {		
		\Stripe\Stripe::setApiKey(StripeClient::getSecretKey());
		
		if ($attribute) {
			$this->customer = $this->getCustomer(StripeClient::getCustomerIdFromAttribute($attribute));
		}
	}

	public static function getCustomerIdFromAttribute($attribute)
	{
		if ($attribute instanceof ElggUser) {
		
			return $attribute->getPrivateSetting('stripe_customer_id');
		
		} else if (is_email_address($attribute)) {
		
			$users = get_user_by_email($attribute);
		
			if ($users) {
				return $users[0]->getPrivateSetting('stripe_customer_id');
			}
		
		} else if (is_string($attribute) && substr($attribute, 0, 4) == 'cus_') {
		
			return $attribute;
		
		} else if (is_numeric($attribute)) {
		
			return get_entity($attribute)->getPrivateSetting('stripe_customer_id');
		}

		return null;
	}

	public static function getSecretKey()
	{
		$environment = elgg_get_plugin_setting('stripe_environment', 'stripe');

		if ($environment == 'production') {
			$secret_key = elgg_get_plugin_setting('stripe_production_secret_key', 'stripe');
		} else {
			$secret_key = elgg_get_plugin_setting('stripe_test_secret_key', 'stripe');
		}

		return $secret_key;
	}

	public static function getPublishableKey()
	{
		$environment = elgg_get_plugin_setting('stripe_environment', 'stripe');
		
		if ($environment == 'production') {
			$publishable_key = elgg_get_plugin_setting('stripe_production_publishable_key', 'stripe');
		} else {
			$publishable_key = elgg_get_plugin_setting('stripe_test_publishable_key', 'stripe');
		}

		return $publishable_key;
	}

	/**
	 * Get a list of currencies supported by the merchant
	 * @return array
	 */
	public function getSupportedCurrencies() {

		$account = $this->getAccount();

		return ($account) ? $account->currencies_supported : array();
	}

	public function getSupportedCards() 
	{
		$account = $this->getAccount();

		$return = array('Visa', 'MasterCard', 'American Express');

		if ($account->country == 'US') {
			array_push($return, 'JCB', 'Diners Club', 'Discover');
		}

		return ($account) ? $return : array();
	}

	/**
	 * Get a customer
	 * @param string $customer_id
	 * @return \Stripe\Customer|false
	 */
	public function getCustomer($id=null) {

		if (!$id && isset($this->customer)) {
			return $this->customer;
		}

		$customer_id = $id ? $id : $this->customer->id;
		
		if (!$customer_id && elgg_is_logged_in()) {
			$customer_id = $this->getCustomerIdFromAttribute(elgg_get_logged_in_user_entity());
			if (!$customer_id) {
				try {
					return $this->createCustomer(elgg_get_logged_in_user_entity());
				}
				catch (Exception $e) {
					$this->log($ex);
					return false;
				}
			}
		}

		try {

			return \Stripe\Customer::retrieve($customer_id);
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Create a new customer
	 * @param ElggUser $user
	 * @param array $data
	 * @return \Stripe\Customer|false
	 */
	public function createCustomer($user = null, $data = array()) {

		$fields = array(
			'source',
			'account_balance',
			'card',
			'coupon',
			'plan',
			'quantity',
			'trial_end',
			'metadata',
			'description',
			'email',
		);

		try {

			foreach ($data as $key => $value) {
				if (!in_array($key, $fields)) {
					$data[$key] = '';
				}
			}
			$data = array_filter($data);

			if ($user) {
			
				if (!$data['email']) {
					$data['email'] = $user->email;
				}
			
				if (!$data['description']) {
					$data['description'] = $user->name;
				}
			
				if (!is_array($data['metadata'])) {
					$data['metadata'] = array();
				}
			
				$data['metadata']['guid'] = $user->guid;
				$data['metadata']['username'] = $user->username;
			}

			$this->customer = \Stripe\Customer::create($data);

			if ($user && $user->guid) {

				// Store any customer IDs this user might have for reference
				$stripe_ids = $user->stripe_customer_id;
			
				if (!$stripe_ids) {
					$stripe_ids = array();
				} else if (!is_array($stripe_ids)) {
					$stripe_ids = array($stripe_ids);
				}
			
				if (!in_array($this->customer->id, $stripe_ids)) {
					create_metadata($user->guid, 'stripe_customer_id', $this->customer->id, '', $user->guid, ACCESS_PUBLIC, true);
				}

				// Store current Customer ID
				$user->setPrivateSetting('stripe_customer_id', $this->customer->id);
			
			} else {

				// Store customer IDs with their email reference locally
				// so that users can be assigned their existing customer ID upon registration
				$customer_ref = elgg_get_plugin_setting($this->customer->email, 'stripe');
				
				if ($customer_ref) {
					$customer_ref = unserialize($customer_ref);
				} else {
					$customer_ref = array();
				}
				
				array_unshift($customer_ref, $this->customer->id);
				
				elgg_set_plugin_setting($this->customer->email, serialize($customer_ref), 'stripe');
			}

			return $this->customer;
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Update an existing Stripe customer
	 * @param string $customer_id
	 * @param array $data
	 * @return \Stripe\Customer|false
	 */
	public function updateCustomer($customer_id = '', $data = array()) {

		$fields = array(
			'account_balance',
			'card',
			'coupon',
			'plan',
			'quantity',
			'trial_end',
			'metadata',
			'description',
			'email',
		);

		try {
			$customer = $this->getCustomer($customer_id);
			if (!$customer) {
				return false;
			}
			foreach ($data as $key => $value) {
				if (in_array($key, $fields)) {
					$customer->$key = $value;
				}
			}
			return $customer->save();
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Delete a customer
	 * @param string $customer_id
	 * @return boolean
	 */
	public function deleteCustomer($customer_id = '') {

		try {
			$customer = $this->getCustomer($customer_id);
			if (!$customer) {
				return true;
			}
			$response = $customer->delete();
			if ($response->deleted) {
				$user = stripe_get_user_from_customer_id($response->id);
				if ($user) {
					if ($user->getPrivateSetting('stripe_customer_id') == $response->id) {
						$user->removePrivateSetting('stripe_customer_id');
					}
				}
				return true;
			}
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get all customers
	 * @param string $limit				Number of customers to retrieve
	 * @param string $ending_before		ID of the first element in the previous list
	 * @param string $starting_after	ID of the last element in the previous list
	 * @param mixed $created			Created hash
	 * @return array|false
	 */
	public function getCustomers($limit = 10, $ending_before = null, $starting_after = null, $created = null) {
		
		try {
		
			$params = array_filter(array(
				'limit'          => $limit,
				'ending_before'  => $ending_before,
				'starting_after' => $starting_after,
				'created'        => $created,
			));

			return \Stripe\Customer::all($params);
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get customer's default card
	 * @return \Stripe\Card|false
	 */
	public function getDefaultCard() {
		
		try {
		
			$card_id = $this->getCustomer()->default_card;
			
			if (!$card_id) {
				return false;
			}
			
			return $this->getCustomer()->sources->retrieve($card_id);
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Set customer's default card
	 * @param string $card_id
	 * @return \Stripe\Card|false
	 */
	public function setDefaultCard($card_id = '') {
		
		$customer = $this->getCustomer();

		try {

			$customer->default_source = $card_id;
			$customer->save();
		
			return $this->getDefaultCard();
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get an existing customer card
	 * @param string $card_id
	 * @return \Stripe\Card|false
	 */
	public function getCard($card_id = '') {
		
		try {
		
			return $this->getCustomer()->sources->retrieve($card_id);
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Create a new card from token or array
	 * @param array|string $card
	 * @return \Stripe\Card|false
	 */
	public function createCard($token, $default=true) {
		
		try {
		
			$card = $this->getCustomer()->sources->create(array("source" => $token));

			if (isset($card->id) && $default) {
				$this->setDefaultCard($card->id);
			}

			return $card;
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Update a card
	 * @param string $card_id
	 * @param array $data
	 * @return \Stripe\Card|false
	 */
	public function updateCard($card_id = '', $data = array()) {
		
		try {
		
			$fields = array(
				'exp_month',
				'exp_year',
				'name',
				'address_line1',
				'address_lin2',
				'address_city',
				'address_zip',
				'address_state',
				'address_country',
			);

			$card = $this->getCard($card_id);
			
			foreach ($data as $key => $value) {
				if (in_array($key, $fields)) {
					$card->$key = $value;
				}
			}
			
			$card->save();
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Delete a card
	 * @param string $card_id
	 * @return boolean
	 */
	public function deleteCard($card_id = null) {
		
		try {
		
			$card = $this->getCard($card_id);
		
			if (!$card) {
				return true;
			}
		
			$response = $card->delete();
		
			if ($response->deleted) {
				return true;
			}
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get all customer cards
	 * @param string $limit				Number of cards to retrieve
	 * @param string $ending_before		ID of the first element in the previous list
	 * @param string $starting_after	ID of the last element in the previous list
	 * @return array|false
	 */
	public function getCards($limit = 10, $ending_before = null, $starting_after = null) {
		
		try {
		
			$params = array_filter(array(
				'object'         => 'card',
				'limit'          => $limit,
				'ending_before'  => $ending_before,
				'starting_after' => $starting_after
			));
			
			$sources = $this->getCustomer()->sources;
			if (!$sources) {
				return false;
			}
		
			return $sources->all($params);
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get a subscription plan
	 * @param string $plan_id
	 * @return \Stripe\Plan|false
	 */
	public function getPlan($plan_id = '') {
		try {
			return \Stripe\Plan::retrieve($plan_id);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Create a new Stripe plan
	 * @param array $data
	 * @return \Stripe\Plan|false
	 */
	public function createPlan($data = array()) {

		$fields = array(
			'id',
			'amount',
			'currency',
			'interval',
			'interval_count',
			'name',
			'trial_period_days',
			'metadata',
			'statement_description'
		);
		try {
			foreach ($data as $key => $value) {
				if (!in_array($key, $fields)) {
					$data[$key] = '';
				}
			}
			$data = array_filter($data);
			return \Stripe\Plan::create($data);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Update an existing Stripe plan
	 * @param string $plan_id
	 * @param array $data
	 * @return \Stripe\Plan|false
	 */
	public function updatePlan($plan_id = '', $data = array()) {
		try {
			$plan = $this->getPlan($plan_id);
			if (!$plan) {
				$this->createPlan($data);
			}
			foreach ($data as $key => $value) {
				if (in_array($key, array('name', 'metadata', 'statement_description'))) {
					$plan->$key = $value;
				}
			}
			return $plan->save();
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Delete a plan
	 * @param string $plan_id
	 * @return boolean
	 */
	public function deletePlan($plan_id = '') {

		try {
			$plan = $this->getPlan($plan_id);
			if (!$plan) {
				return true;
			}
			$response = $plan->delete();
			if ($response->deleted) {
				return true;
			}
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get all plans
	 * @param string $limit				Number of plans to retrieve
	 * @param string $ending_before		ID of the first element in the previous list
	 * @param string $starting_after	ID of the last element in the previous list
	 * @return boolean
	 */
	public function getPlans($limit = 10, $ending_before = null, $starting_after = null) {
		try {
			$params = array_filter(array(
				'limit' => $limit,
				'ending_before' => $ending_before,
				'starting_after' => $starting_after
			));
			return \Stripe\Plan::all(array_filter($params));
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get an existing customer subscription
	 * @param string $subscription_id
	 * @return \Stripe\Card|false
	 */
	public function getSubscription($subscription_id = '') {
		
		try {
		
			return $this->getCustomer()->subscriptions->retrieve($subscription_id);
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Subscribe user to a plan
	 *
	 * @param array $data				Data
	 * @return \Stripe\Subscription|false
	 */
	public function createSubscription($data = array()) {
		
		$fields = array(
			'plan',
			'card',
			'coupon',
			'trial_end',
			'quantity',
			'application_fee_percent',
			'trial_period_days',
			'metadata',
		);
		
		try {

			foreach ($data as $key => $value) {
				if (!in_array($key, $fields)) {
					$data[$key] = '';
				}
			}

			$data         = array_filter($data);
			$subscription = $this->getCustomer()->subscriptions->create($data);
			
			return $subscription;
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Update an existing subscription
	 * @param string $subscription_id
	 * @param array $data
	 * @return \Stripe\Subscription|false
	 */
	public function updateSubscription($subscription_id = '', $data = array()) {
		
		$fields = array(
			'plan',
			'card',
			'prorate',
			'coupon',
			'trial_end',
			'quantity',
			'application_fee_percent',
			'trial_period_days',
			'metadata',
		);
		
		try {

			$subscription = $this->getSubscription($subscription_id);
			
			if (!$subscription) {
				return false;
			}
			
			foreach ($data as $key => $value) {
				if (in_array($key, $fields) && $value) {
					$subscription->$key = $value;
				}
			}
			
			return $subscription->save();
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Cancel an existing subscription
	 * @param string $subscription_id
	 * @param boolean $at_period_end
	 * @return \Stripe\Subscription
	 */
	public function cancelSubscription($id, $at_period_end = false) {
		
		try {
		
			return $this->getCustomer()->subscriptions->retrieve($id)->cancel(array('at_period_end' => $at_period_end));
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get all customer subscriptions
	 * @param string $limit				Number of subscriptions to retrieve
	 * @param string $ending_before		ID of the first element in the previous list
	 * @param string $starting_after	ID of the last element in the previous list
	 * @return boolean
	 */
	public function getSubscriptions($limit = 10, $ending_before = null, $starting_after = null) {
		
		try {
			
			$params = array_filter(array(
				'limit'          => $limit,
				'ending_before'  => $ending_before,
				'starting_after' => $starting_after
			));
			
			$subscriptions = $this->getCustomer()->subscriptions;
			if (!$subscriptions) {
				return false;
			}
			
			return $subscriptions->all($params);
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get an existing customer charge
	 * @param string $charge_id
	 * @return \Stripe\Charge|false
	 */
	public function getCharge($charge_id = '') {
		try {
			return \Stripe\Charge::retrieve($charge_id);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Create a new charge
	 * @param array $data
	 * @return \Stripe\Charge|false
	 */
	public function createCharge($data = array()) {
		
		try {
		
			$fields = array(
				'amount',
				'currency',
				'card',
				'description',
				'metadata',
				'capture',
				'statement_description',
				'application_fee',
			);
			
			foreach ($data as $key => $value) {
				if (!in_array($key, $fields)) {
					$data[$key] = '';
				}
			}
			
			$data             = array_filter($data);
			$data['customer'] = $this->getCustomer()->id;

			return \Stripe\Charge::create($data);
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Update a charge
	 * @param string $charge_id
	 * @param array $data
	 * @return \Stripe\Charge|false
	 */
	public function updateCharge($charge_id = '', $data = array()) {
		try {
			$fields = array(
				'description',
				'metadata',
			);

			$charge = $this->getCharge($charge_id);
			foreach ($data as $key => $value) {
				if (in_array($key, $fields)) {
					$charge->$key = $value;
				}
			}
			$charge->save();
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Refund a charge
	 * @param string $charge_id
	 * @param integer $amount
	 * @param boolean $refund_application_fee
	 * @return boolean
	 */
	public function refundCharge($charge_id = '', $amount = null, $refund_application_fee = true) {
		try {
			$charge = $this->getCharge($charge_id);
			if (!$charge) {
				return false;
			}
			$response = $charge->refund(array(
				'amount' => $amount,
				'refund_application_fee' => $refund_application_fee
			));
			if ($response->refunded) {
				return true;
			}
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get all customer charges
	 * @param string $limit				Number of items to retrieve
	 * @param string $ending_before		ID of the first element in the previous list
	 * @param string $starting_after	ID of the last element in the previous list
	 * @param mixed $created
	 * @return boolean
	 */
	public function getCharges($limit = 10, $ending_before = null, $starting_after = null, $created = null) {
		
		try {
			
			if ($this->customer) {
				$customer_id = $this->getCustomer()->id;
			}
			
			if (!$customer_id) {
				return false;
			}

			$params = array_filter(array(
				'created'        => $created,
				'customer'       => $customer_id,
				'limit'          => $limit,
				'ending_before'  => $ending_before,
				'starting_after' => $starting_after
			));

			return \Stripe\Charge::all($params);
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get an invoice
	 * @param string $invoice_id
	 * @return \Stripe\Invoice|false
	 */
	public function getInvoice($invoice_id = '') {
		try {
			return \Stripe\Invoice::retrieve($invoice_id);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get an upcoming invoice
	 * @param string $subscription_id
	 * @return \Stripe\Invoice|false
	 */
	public function getUpcomingInvoice($subscription_id = '') {
		
		try {
		
			$data = array(
				'customer'     => $this->getCustomer()->id,
				'subscription' => $subscription_id,
			);
		
			return \Stripe\Invoice::upcoming(array_filter($data));
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Create a new Stripe invoice
	 * @param array $data
	 * @return \Stripe\Invoice|false
	 */
	public function createInvoice($data = array()) {

		$fields = array(
			'application_fee',
			'description',
			'metadata',
			'subscription',
		);

		try {
		
			foreach ($data as $key => $value) {
				if (!in_array($key, $fields)) {
					$data[$key] = '';
				}
			}
		
			$data             = array_filter($data);
			$data['customer'] = $this->getCustomer()->id;

			return \Stripe\Invoice::create($data);
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Update an existing Stripe invoice
	 * @param string $invoice_id
	 * @param array $data
	 * @return \Stripe\Invoice|false
	 */
	public function updateInvoice($invoice_id = '', $data = array()) {
		$fields = array(
			'application_fee',
			'description',
			'metadata',
			'closed',
		);

		try {
			$invoice = $this->getInvoice($invoice_id);
			if (!$invoice) {
				$this->createInvoice($data);
			}
			foreach ($data as $key => $value) {
				if (in_array($key, $fields)) {
					$invoice->$key = $value;
				}
			}
			return $invoice->save();
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get all invoices
	 * @param string $limit				Number of invoices to retrieve
	 * @param string $ending_before		ID of the first element in the previous list
	 * @param string $starting_after	ID of the last element in the previous list
	 * @param mixed $date
	 * @return boolean
	 */
	public function getInvoices($limit = 10, $ending_before = null, $starting_after = null, $date = null) {
		
		try {
	
			$params = array_filter(array(
				'limit'          => $limit,
				'ending_before'  => $ending_before,
				'starting_after' => $starting_after,
				'date'           => $date,
			));
			
			if (!$this->getCustomer()->id) {
				return false;
			}
	
			$params['customer'] = $this->getCustomer()->id;

			return \Stripe\Invoice::all(array_filter($params));
	
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get a coupon by ID
	 * @param string $invoiceitem_id
	 * @return \Stripe\InvoiceItem|false
	 */
	public function getCoupon($coupon) {
		try {
			return \Stripe\Coupon::retrieve($coupon);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get an existing invoice item
	 * @param string $invoiceitem_id
	 * @return \Stripe\InvoiceItem|false
	 */
	public function getInvoiceItem($invoiceitem_id = '') {
		try {
			return \Stripe\InvoiceItem::retrieve($invoiceitem_id);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Create a new invoice item
	 * @param array|string $data
	 * @return \Stripe\InvoiceItem|false
	 */
	public function createInvoiceItem($data = array()) {
		
		$fields = array(
			'amount',
			'currency',
			'invoice',
			'subscription',
			'description',
			'metadata',
		);

		try {

			foreach ($data as $key => $value) {
				if (!in_array($key, $fields)) {
					$data[$key] = '';
				}
			}
			
			$data             = array_filter($data);
			$data['customer'] = $this->customer;
			
			return \Stripe\InvoiceItem::create($data);
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Update an invoice item
	 * @param string $invoiceitem_id
	 * @param array $data
	 * @return \Stripe\InvoiceItem|false
	 */
	public function updateInvoiceItem($invoiceitem_id = '', $data = array()) {
		try {
			$fields = array(
				'amount',
				'description',
				'metadata',
			);

			$invoiceitem = $this->getInvoiceItem($invoiceitem_id);
			foreach ($data as $key => $value) {
				if (in_array($key, $fields)) {
					$invoiceitem->$key = $value;
				}
			}
			$invoiceitem->save();
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Delete an invoice item
	 * @param string $invoiceitem_id
	 * @return boolean
	 */
	public function deleteInvoiceItem($invoiceitem_id = null) {
		try {
			$invoiceitem = $this->getInvoiceItem($invoiceitem_id);
			if (!$invoiceitem) {
				return true;
			}
			$response = $invoiceitem->delete();
			if ($response->deleted) {
				return true;
			}
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get all invoice items
	 *
	 * @param string $invoice_id
	 * @param string $limit				Number of invoiceitems to retrieve
	 * @param string $ending_before		ID of the first element in the previous list
	 * @param string $starting_after	ID of the last element in the previous list
	 * @param mixed $created
	 * @return array|false
	 */
	public function getInvoiceItems($invoice_id = null, $limit = 10, $ending_before = null, $starting_after = null, $created = null) {
		
		try {
			
			$params = array_filter(array(
				'limit' => $limit,
				'ending_before' => $ending_before,
				'starting_after' => $starting_after,
			));

			if ($invoice_id) {
				
				$invoice = $this->getInvoice($invoice_id);
				
				if (!$invoice) {
					return false;
				}
			
				return $invoice->lines->all(array_filter($params));
			
			} else {
				
				$params['customer'] = $this->getCustomer()->id;
				$params['created']  = $created;
				
				return \Stripe\InvoiceItem::all(array_filter($params));
			}
		
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Retrieve a Stripe account
	 * @return boolean
	 */
	public function getAccount() {
		try {
			return \Stripe\Account::retrieve();
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get a stripe event object
	 * @param string $event_id
	 * @return \Stripe\Event|false
	 */
	public function getEvent($event_id = '') {
		try {
			return \Stripe\Event::retrieve($event_id);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Handle exceptions and error messages
	 * @param Exception|string $exception
	 * @return void
	 */
	protected function log($exception) {
		if ($exception instanceof \Stripe\InvalidRequestError) {
			$error = elgg_echo('stripe:invalid_request_error', array($exception->getMessage()));
			elgg_log($exception->getMessage(), 'ERROR');
		} else if ($exception instanceof \Stripe\AuthenticationError) {
			$error = elgg_echo('stripe:authentication_error', array($exception->getMessage()));
			elgg_log($exception->getMessage(), 'ERROR');
		} else if ($exception instanceof \Stripe\ApiConnectionError) {
			$error = elgg_echo('stripe:api_error', array($exception->getMessage()));
			elgg_log($exception->getMessage(), 'ERROR');
		} else if ($exception instanceof \Stripe\Error) {
			$error = elgg_echo('stripe:generic_error', array($exception->getMessage()));
			elgg_log($exception->getMessage(), 'ERROR');
		} else if ($exception instanceof Exception) {
			$error = elgg_echo('stripe:generic_error', array($exception->getMessage()));
			elgg_log($exception->getMessage(), 'ERROR');
		} else {
			$error = $exception;
		}

		if (is_array($error)) {
			$this->log = array_merge($this->log, $error);
		} else if ($error) {
			array_push($this->log, $error);
		}
	}

	/**
	 * Display messages and errors to the user on the next page view
	 */
	public function showErrors() {
		if ($this->log) {
			foreach ($this->log as $error) {
				register_error($error);
			}
		}
	}

	/**
	 * Output an html view of errors and messages
	 * @return string
	 */
	public function viewErrors() {
		$view .= '';
		if ($this->log) {
			$view .= '<ul class="stripe-log">';
			foreach ($this->log as $error) {
				$view .= '<li>' . $error . '</li>';
			}
			$view .= '</ul>';
		}
		return $view;
	}

}
