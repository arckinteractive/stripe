<?php

class StripeClientFactory {

	static $private_log;
	static $public_log;
	static $environment;
	static $api_config;

	const ENV_SANDBOX = 'sandbox';
	const ENV_PRODUCTION = 'production';

	/**
	 * Initialize Stripe
	 */
	public static function init() {
		self::$private_log = array();
		self::$public_log = array();

		if (!self::$environment) {
			self::$environment = self::filterEnvironment();
		}
		$config = new stdClass();
		if (self::$environment == self::ENV_PRODUCTION) {
			$config->secret_key = elgg_get_plugin_setting('stripe_production_secret_key', 'stripe');
			$config->publishable_key = elgg_get_plugin_setting('stripe_production_publishable_key', 'stripe');
		} else {
			$config->secret_key = elgg_get_plugin_setting('stripe_test_secret_key', 'stripe');
			$config->publishable_key = elgg_get_plugin_setting('stripe_test_publishable_key', 'stripe');
		}
		self::$api_config = $config;
		Stripe::setApiKey($config->secret_key);
	}

	/**
	 * Stage an environment
	 * @param string $environment
	 */
	public static function stageEnvironment($environment = null) {
		self::$environment = self::filterEnvironment($environment);
		self::init();
	}

	/**
	 * Get current environment setting
	 * @param string $environment
	 * @return string
	 */
	public static function filterEnvironment($environment = null) {

		if (!$environment) {
			$environment = elgg_get_plugin_setting('stripe_environment', 'stripe');
		}

		if ($environment !== self::ENV_PRODUCTION) {
			$environment = self::ENV_SANDBOX;
		}
		return $environment;
	}

	/**
	 * Get secret API key
	 * @return string
	 */
	public static function getSecretKey() {
		return self::$api_config->secret_key;
	}

	/**
	 * Get publishable API key
	 * @return string
	 */
	public static function getPublishableKey() {
		return self::$api_config->publishable_key;
	}

}
