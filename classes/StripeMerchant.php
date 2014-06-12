<?php

class StripeMerchant {

	/**
	 * Merchant entity
	 * @var ElggUser|ElggSite|ElggGroup
	 */
	protected $entity;

	/**
	 * Stripe account
	 * @var Stripe_Customer
	 */
	protected $account;

	/**
	 * Access token for signing API requests
	 * @var string
	 */
	protected $access_token;

	/**
	 * Create or retrieve a Stripe account
	 * @param mixed $entity_attr	ElggUser or guid or email
	 * @throws IOException
	 */
	public function __construct($entity_attr = null) {

		if (is_null($entity_attr)) {
			$entity_attr = elgg_get_site_entity();
		}

		if ($entity_attr instanceof ElggEntity) {
			$this->entity = $entity_attr;
		} else if (is_string($entity_attr) && substr($entity_attr, 0, 5) == 'acct_') {
			$account_id = $entity_attr;
		} else if (is_numeric($entity_attr)) {
			$this->entity = get_entity($entity_attr);
		}

		if (!$this->entity && $account_id) {
			if ($entity = stripe_get_entity_from_account_id($account_id)) {
				$this->entity = $entity;
			}
		}
	}

	/**
	 * Retrieve a Stripe account for this mercant
	 *
	 * @return Stripe_Customer|boolean
	 * @throws Stripe_Error
	 */
	public function getMerchantAccount() {

		if ($this->account->id) {
			return $this->account;
		}

		if (!$this->entity) {
			return false;
		}

		if (!$access_token = $this->getAccessToken()) {
			return false;
		}

		try {
			$stripe = new StripeClient();
			$stripe->setAccessToken($access_token);
			$account = $stripe->getAccount();
			if (!$account->id || isset($account->deleted)) {
				throw new Stripe_Error('Account does not exist or has been deleted');
			}
			$this->account = $account;
			return $this->account;
		} catch (Stripe_Error $e) {
			$this->entity->setPrivateSetting('stripe_secret_key', null);
			$this->entity->setPrivateSetting('stripe_publishable_key', null);
			return false;
		}
	}

	/**
	 * Get a list of currencies supported by the merchant
	 * @return array
	 */
	public function getSupportedCurrencies() {

		$account = $this->getMerchantAccount();
		return ($account) ? $account->currencies_supported : array();
	}

	/**
	 * Get a list of card brands supported by the merchant
	 */
	public function getSupportedCards() {

		$account = $this->getMerchantAccount();

		if (!$account) {
			return array();
		}

		$return = array('Visa', 'MasterCard', 'American Express');

		if ($account->country == 'US') {
			array_push($return, 'JCB', 'Diners Club', 'Discover');
		}

		return $return;
	}

	/**
	 * Get an access token for signing API requests
	 */
	public function getAccessToken() {

		if (!elgg_instanceof($this->entity)) {
			return false;
		}

		switch ($this->entity->getType()) {

			case 'site' :
				return StripeClientFactory::getSecretKey();
				break;

			default :
				return $this->entity->getPrivateSetting('stripe_access_token');
				break;
		}
	}

}
