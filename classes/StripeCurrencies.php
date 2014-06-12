<?php

class StripeCurrencies {

	const DEFAULT_CURRENCY = 'USD';

	protected $currency_code;
	protected $currency_symbol;
	protected $country_code;
	protected $country_name;

	public static function getDefaultCurrency() {
		$default = elgg_get_plugin_setting('default_currency', 'stripe');
		$currencies = self::getCurrencies();
		return ($default && array_key_exists($default, $currencies)) ? $default : self::DEFAULT_CURRENCY;
	}

	public static function getCurrencies() {
		return StripeCountries::getCountries('currency_code', array('name', 'iso', 'currency_symbol'), 'currency_code');
	}

	function __construct($currency_code = '') {
		$this->setCurrencyCode($currency_code);
	}

	public function setCurrencyCode($currency_code = '') {
		$this->currency_code = strtoupper($currency_code);
		$currencies = $this->getCurrencies();
		if (isset($currencies[$currency_code])) {
			$this->setCountryCode($currencies[$currency_code]['iso']);
			$this->setCountryName($currencies[$currency_code]['name']);
			$this->setCurrencySymbol($currencies[$currency_code]['currency_symbol']);
		}
	}

	public function getCurrencyCode() {
		if (!$this->isValid()) {
			return self::getDefaultCurrency();
		}

		return $this->currency_code;
	}
	
	protected function setCountryCode($country_code = '') {
		$this->country_code = $country_code;
	}

	public function getCountryCode() {
		return $this->country_code;
	}

	protected function setCountryName($country_name = '') {
		$this->country_name = $country_name;
	}

	protected function setCurrencySymbol($currency_symbol = '') {
		$this->currency_symbol = $currency_symbol;
	}

	public function getCurrencySymbol() {
		return $this->currency_symbol;
	}
	
	public function getCountryName() {
		return $this->country_name;
	}

	public function isValid() {
		$currencies = $this->getCurrencies();
		return (array_key_exists($this->currency_code, $currencies));
	}

}
