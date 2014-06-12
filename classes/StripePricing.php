<?php

class StripePricing {

	protected $price;
	protected $rate;
	protected $base;
	protected $currency;

	const SEPARATOR_DECIMAL = '.';
	const SEPARATOR_MILLE = ' ';

	/**
	 * Construct a new price object
	 *
	 * @param float $price				Fixed value
	 * @param float $rate				Percentile value calculated from $base
	 * @param float $base				Base amount to calculate percentile value from
	 * @param string $currency_code		Currency code
	 */
	function __construct($price = 0, $rate = 0, $base = 0, $currency_code = '') {
		$this->setPrice($price);
		$this->setRate($rate);
		$this->setBase($base);
		$this->setCurrency($currency_code);
	}

	/**
	 * Sets fixed value
	 * @param float $price
	 * @return void
	 */
	public function setPrice($price = 0) {
		if (empty($price) || !is_numeric($price)) {
			$price = 0;
		}
		$this->price = (float) $price;
	}

	/**
	 * Get fixed value
	 * @return float
	 */
	public function getPrice() {
		return (float) $this->price;
	}

	/**
	 * Get the price suitable for interfacing with Stripe, i.e. in minimum unit of currency
	 * @return integer
	 * @todo Check for currency (e.g. Yien has no cents)
	 */
	public function getStripePrice() {
		return round($this->getPrice() * 100, 0);
	}

	/**
	 * Set percentile value
	 * @param float $rate
	 * @return void
	 */
	public function setRate($rate = 0) {
		if (empty($rate) || !is_numeric($rate)) {
			$rate = 0;
		}
		$this->rate = (float) $rate;
	}

	/**
	 * Get percentile value
	 * @return float
	 */
	public function getRate() {
		return (float) $this->rate;
	}

	/**
	 * Set base amount
	 * @param float $amount
	 * @return void
	 */
	public function setBase($amount = 0) {
		if (empty($amount) || !is_numeric($amount)) {
			$amount = 0;
		}
		$this->base = (float) $amount;
	}

	/**
	 * Get base amount
	 * @return float
	 */
	public function getBase() {
		return (float) $this->base;
	}

	/**
	 * Set currency
	 * @param string $currency_code
	 * @return void
	 */
	public function setCurrency($currency_code = '') {
		$currency = new StripeCurrencies($currency_code);
		if ($currency->isValid()) {
			$this->currency = $currency->getCurrencyCode();
		}
	}

	/**
	 * Get currency object
	 * @return StripeCurrencies
	 */
	public function getCurrency() {
		if (isset($this->currency)) {
			return $this->currency;
		}
		$default_currency = StripeCurrencies::getDefaultCurrency();
		return $default_currency;
	}

	/**
	 * Check if the price results in a valid amount
	 * @return boolean
	 */
	public function isValid() {
		$amount = $this->getAmount();
		return (!empty($amount) || $amount === 0);
	}

	/**
	 * Calculate amount
	 * @param integer $decimals	Number of decimals
	 * @return float
	 */
	public function getAmount($decimals = 2) {
		$price = $this->getPrice();
		$rate = $this->getRate();

		$amount = $price + ($rate * $this->getBase() / 100);

		return (float) round($amount, $decimals);
	}

	/**
	 * Get a readable amount
	 * @param integer $decimals
	 * @return string
	 */
	public function getHumanAmount($decimals = 2, $user_currency_symbol = true) {
		$currency_code = $this->getCurrency();
		$currency = new StripeCurrencies($currency_code);
		if ($currency_symbol = $currency->getCurrencySymbol()) {
			$currency_code = $currency_symbol;
		}
		$amount = number_format($this->getAmount($decimals), $decimals, self::SEPARATOR_DECIMAL, self::SEPARATOR_MILLE);
		return elgg_echo('stripe:output:price', array($amount, $currency_code,));
	}

}
