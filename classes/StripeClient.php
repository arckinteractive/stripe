<?php

class StripeClient {

	/**
	 * Error log
	 * @var array
	 */
	protected $log = array();

	/**
	 * Access token used to sign API requests
	 * This will not be used on operations with customers,
	 * as the intention is to keep shared customers between multiple accounts
	 * @var string
	 */
	protected $access_token = null;

	/**
	 * Constructs a new Stripe instance
	 * @param string $environment		'sandbox' or 'production'
	 * @param mixed $merchant_attr		Account ID, ElggEntity or entity guid of the merchant on whose behalf the requests are to be made
	 * @return StripeClient
	 */
	function __construct($environment = null, $merchant_attr = null) {
		StripeClientFactory::stageEnvironment($environment);
		
		$merchant = new StripeMerchant($merchant_attr);
		$access_token = $merchant->getAccessToken();
		$this->setAccessToken($access_token);
	}

	/**
	 * Set an access token for individual requests
	 * @param string $access_token		Access token from Stripe Connect flow, defaults to site access token
	 */
	public function setAccessToken($access_token = null) {
		$this->access_token = $access_token;
	}

	/**
	 * Get a customer
	 * @param string $customer_id
	 * @return Stripe_Customer|false
	 */
	public function getCustomer($customer_id = '') {
		try {
			return Stripe_Customer::retrieve($customer_id);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Create a new customer
	 * @param ElggUser $user
	 * @param array $data
	 * @return Stripe_Customer|false
	 */
	public function createCustomer($user = null, $data = array()) {

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

			$customer = Stripe_Customer::create($data);

			if ($user && $user->guid) {

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
				$user->setPrivateSetting('stripe_customer_id', $customer->id);
			} else {

				// Store customer IDs with their email reference locally
				// so that users can be assigned their existing customer ID upon registration
				$customer_ref = elgg_get_plugin_setting($customer->email, 'stripe');
				if ($customer_ref) {
					$customer_ref = unserialize($customer_ref);
				} else {
					$customer_ref = array();
				}
				array_unshift($customer_ref, $customer->id);
				elgg_set_plugin_setting($customer->email, serialize($customer_ref), 'stripe');
			}

			return $customer;
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Update an existing Stripe customer
	 * @param string $customer_id
	 * @param array $data
	 * @return Stripe_Customer|false
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
				'limit' => $limit,
				'ending_before' => $ending_before,
				'starting_after' => $starting_after,
				'created' => $created,
			));

			return Stripe_Customer::all($params, $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get customer's default card
	 * @param mixed $cus_attr
	 * @return Stripe_Card|false
	 */
	public function getDefaultCard($cus_attr = null) {
		try {
			$customer = new StripeCustomer($cus_attr);
			$default_card = $customer->getCustomerAccount()->default_card;
			if (!$default_card) {
				return false;
			}
			return $customer->getCustomerAccount()->cards->retrieve($default_card, $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Set customer's default card
	 * @param mixed $cus_attr
	 * @param string $card_id
	 * @return Stripe_Card|false
	 */
	public function setDefaultCard($cus_attr = null, $card_id = '') {
		try {
			$customer = new StripeCustomer($cus_attr);
			$account = $customer->getCustomerAccount();
			$account->default_card = $card_id;
			if (!$account->save()) {
				return false;
			}
			return $this->getDefaultCard($cus_attr);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get an existing customer card
	 * @param mixed $cus_attr
	 * @param string $card_id
	 * @return Stripe_Card|false
	 */
	public function getCard($cus_attr = null, $card_id = '') {
		try {
			$customer = new StripeCustomer($cus_attr);
			return $customer->getCustomerAccount()->cards->retrieve($card_id, $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Create a new card from token or array
	 * @param mixed $cus_attr
	 * @param array|string $card
	 * @return Stripe_Card|false
	 */
	public function createCard($cus_attr = null, $card = array()) {
		try {
			if (is_array($card)) {

				$fields = array(
					'number',
					'exp_month',
					'exp_year',
					'cvc',
					'name',
					'address_line1',
					'address_lin2',
					'address_city',
					'address_zip',
					'address_state',
					'address_country',
				);
				foreach ($card as $key => $value) {
					if (!in_array($key, $fields)) {
						$card[$key] = '';
					}
				}
				$card = array_filter($card);
			} else {
				$card = array(
					'card' => $card
				);
			}

			$customer = new StripeCustomer($cus_attr);
			return $customer->getCustomerAccount()->cards->create($card);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Update a card
	 * @param mixed $cus_attr
	 * @param string $card_id
	 * @param array $data
	 * @return Stripe_Card|false
	 */
	public function updateCard($cus_attr = null, $card_id = '', $data = array()) {
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

			$card = $this->getCard($cus_attr, $card_id);
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
	 * @param mixed $cus_attr
	 * @param string $card_id
	 * @return boolean
	 */
	public function deleteCard($cus_attr = null, $card_id = null) {
		try {
			$card = $this->getCard($cus_attr, $card_id);
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
	 * @param mixed $cus_attr	GUID of the customer
	 * @param string $limit				Number of cards to retrieve
	 * @param string $ending_before		ID of the first element in the previous list
	 * @param string $starting_after	ID of the last element in the previous list
	 * @return array|false
	 */
	public function getCards($cus_attr = null, $limit = 10, $ending_before = null, $starting_after = null) {
		try {
			$params = array_filter(array(
				'limit' => $limit,
				'ending_before' => $ending_before,
				'starting_after' => $starting_after
			));
			$customer = new StripeCustomer($cus_attr);
			return $customer->getCustomerAccount()->cards->all($params, $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get a subscription plan
	 * @param string $plan_id
	 * @return Stripe_Plan|false
	 */
	public function getPlan($plan_id = '') {
		try {
			return Stripe_Plan::retrieve($plan_id, $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Create a new Stripe plan
	 * @param array $data
	 * @return Stripe_Plan|false
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
			return Stripe_Plan::create($data, $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Update an existing Stripe plan
	 * @param string $plan_id
	 * @param array $data
	 * @return Stripe_Plan|false
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
			return Stripe_Plan::all(array_filter($params), $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get an existing customer subscription
	 * @param mixed $cus_attr
	 * @param string $subscription_id
	 * @return Stripe_Card|false
	 */
	public function getSubscription($cus_attr = null, $subscription_id = '') {
		try {
			$customer = new StripeCustomer($cus_attr);
			return $customer->getCustomerAccount()->subscriptions->retrieve($subscription_id, $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Subscribe user to a plan
	 *
	 * @param mixed $cus_attr	GUID of the user
	 * @param array $data				Data
	 * @return Stripe_Subscription|false
	 */
	public function createSubscription($cus_attr = 0, $data = array()) {
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

			$data = array_filter($data);

			$customer = new StripeCustomer($cus_attr);
			$subscription = $customer->getCustomerAccount()->subscriptions->create($data, $this->access_token);
			return $subscription;
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Update an existing subscription
	 * @param mixed $cus_attr
	 * @param string $subscription_id
	 * @param array $data
	 * @return Stripe_Subscription|false
	 */
	public function updateSubscription($cus_attr = 0, $subscription_id = '', $data = array()) {
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

			$subscription = $this->getSubscription($cus_attr, $subscription_id);
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
	 * @param mixed $cus_attr
	 * @param string $subscription_id
	 * @param boolean $at_period_end
	 * @return Stripe_Subscription
	 */
	public function cancelSubscription($cus_attr = 0, $subscription_id = '', $at_period_end = false) {
		try {
			$subscription = $this->getSubscription($cus_attr, $subscription_id);
			if (!$subscription) {
				return false;
			}
			return $subscription->cancel(array('at_period_end' => $at_period_end));
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
	public function getSubscriptions($cus_attr = null, $limit = 10, $ending_before = null, $starting_after = null) {
		try {
			$params = array_filter(array(
				'limit' => $limit,
				'ending_before' => $ending_before,
				'starting_after' => $starting_after
			));
			$customer = new StripeCustomer($cus_attr);
			return $customer->getCustomerAccount()->subscriptions->all($params, $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get an existing customer charge
	 * @param string $charge_id
	 * @return Stripe_Charge|false
	 */
	public function getCharge($charge_id = '') {
		try {
			return Stripe_Charge::retrieve($charge_id, $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Create a new charge
	 * @param mixed $cus_attr
	 * @param array $data
	 * @return Stripe_Charge|false
	 */
	public function createCharge($cus_attr = null, $data = array()) {
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
			$data = array_filter($data);

			$customer = new StripeCustomer($cus_attr);
			$data['customer'] = $customer->getCustomerAccount()->id;

			return Stripe_Charge::create($data, $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Update a charge
	 * @param string $charge_id
	 * @param array $data
	 * @return Stripe_Charge|false
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
	 * @param mixed $cus_attr
	 * @param string $limit				Number of items to retrieve
	 * @param string $ending_before		ID of the first element in the previous list
	 * @param string $starting_after	ID of the last element in the previous list
	 * @param mixed $created
	 * @return boolean
	 */
	public function getCharges($cus_attr = null, $limit = 10, $ending_before = null, $starting_after = null, $created = null) {
		try {
			if ($cus_attr) {
				$customer = new StripeCustomer($cus_attr);
				$customer_id = $customer->getCustomerAccount()->id;
			}

			$params = array_filter(array(
				'created' => $created,
				'customer' => $customer_id,
				'limit' => $limit,
				'ending_before' => $ending_before,
				'starting_after' => $starting_after
			));

			return Stripe_Charge::all($params, $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get an invoice
	 * @param string $invoice_id
	 * @return Stripe_Invoice|false
	 */
	public function getInvoice($invoice_id = '') {
		try {
			return Stripe_Invoice::retrieve($invoice_id, $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get an upcoming invoice
	 * @param mixed $cus_attr
	 * @param string $subscription_id
	 * @return Stripe_Invoice|false
	 */
	public function getUpcomingInvoice($cus_attr = null, $subscription_id = '') {
		try {
			$customer = new StripeCustomer($cus_attr);
			$data = array(
				'customer' => $customer->getCustomerAccount()->id,
				'subscription' => $subscription_id,
			);
			return Stripe_Invoice::upcoming(array_filter($data), $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Create a new Stripe invoice
	 * @param mixed $cus_attr
	 * @param array $data
	 * @return Stripe_Invoice|false
	 */
	public function createInvoice($cus_attr = null, $data = array()) {

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
			$data = array_filter($data);

			$customer = new StripeCustomer($cus_attr);
			$data['customer'] = $customer->getCustomerAccount()->id;

			return Stripe_Invoice::create($data, $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Update an existing Stripe invoice
	 * @param string $invoice_id
	 * @param array $data
	 * @return Stripe_Invoice|false
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
	 * @param mixed $cus_attr
	 * @param string $limit				Number of invoices to retrieve
	 * @param string $ending_before		ID of the first element in the previous list
	 * @param string $starting_after	ID of the last element in the previous list
	 * @param mixed $date
	 * @return boolean
	 */
	public function getInvoices($cus_attr = null, $limit = 10, $ending_before = null, $starting_after = null, $date = null) {
		try {
			$params = array_filter(array(
				'limit' => $limit,
				'ending_before' => $ending_before,
				'starting_after' => $starting_after,
				'date' => $date,
			));
			$customer = new StripeCustomer($cus_attr);
			$params['customer'] = $customer->getCustomerAccount()->id;

			return Stripe_Invoice::all(array_filter($params), $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get an existing invoice item
	 * @param string $invoiceitem_id
	 * @return Stripe_InvoiceItem|false
	 */
	public function getInvoiceItem($invoiceitem_id = '') {
		try {
			return Stripe_InvoiceItem::retrieve($invoiceitem_id, $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Create a new invoice item
	 * @param mixed $cus_attr
	 * @param array|string $data
	 * @return Stripe_InvoiceItem|false
	 */
	public function createInvoiceItem($cus_attr = null, $data = array()) {
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
			$data = array_filter($data);

			$customer = new StripeCustomer($cus_attr);
			$data['customer'] = $data;
			return Stripe_InvoiceItem::create($data, $this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Update an invoice item
	 * @param string $invoiceitem_id
	 * @param array $data
	 * @return Stripe_InvoiceItem|false
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
	 * @param mixed $cus_attr
	 * @param string $invoice_id
	 * @param string $limit				Number of invoiceitems to retrieve
	 * @param string $ending_before		ID of the first element in the previous list
	 * @param string $starting_after	ID of the last element in the previous list
	 * @param mixed $created
	 * @return array|false
	 */
	public function getInvoiceItems($cus_attr = null, $invoice_id = null, $limit = 10, $ending_before = null, $starting_after = null, $created = null) {
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
				$customer = new StripeCustomer($cus_attr);
				$params['customer'] = $customer->getCustomerAccount()->id;
				$params['created'] = $created;
				return Stripe_InvoiceItem::all(array_filter($params), $this->access_token);
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
			return Stripe_Account::retrieve($this->access_token);
		} catch (Exception $ex) {
			$this->log($ex);
			return false;
		}
	}

	/**
	 * Get a stripe event object
	 * @param string $event_id
	 * @return Stripe_Event|false
	 */
	public function getEvent($event_id = '') {
		try {
			return Stripe_Event::retrieve($event_id, $this->access_token);
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
		if ($exception instanceof Stripe_InvalidRequestError) {
			$error = elgg_echo('stripe:invalid_request_error', array($exception->getMessage()));
			elgg_log($exception->getMessage(), 'ERROR');
		} else if ($exception instanceof Stripe_AuthenticationError) {
			$error = elgg_echo('stripe:authentication_error', array($exception->getMessage()));
			elgg_log($exception->getMessage(), 'ERROR');
		} else if ($exception instanceof Stripe_ApiConnectionError) {
			$error = elgg_echo('stripe:api_error', array($exception->getMessage()));
			elgg_log($exception->getMessage(), 'ERROR');
		} else if ($exception instanceof Stripe_Error) {
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
