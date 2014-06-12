<?php

class StripeBillingCycle {

	protected $cycle;
	protected $label;
	protected $interval;
	protected $interval_count;

	/**
	 * Construct an interval for a cycle
	 * @param string $cycle e.g. monthly, quarterly etc.
	 * @param string $interval
	 * @param string $interval_count
	 */
	function __construct($cycle = '', $interval = '', $interval_count = '') {
		$intervals = self::getCycles();
		if ($cycle && isset($intervals[$cycle])) {
			$this->cycle = $cycle;
			$this->label = $intervals[$cycle]['label'];
			$this->interval = $intervals[$cycle]['interval'];
			$this->interval_count = $intervals[$cycle]['interval_count'];
		} else if ($interval && $interval_count) {
			foreach ($intervals as $cycle => $options) {
				if ($options['interval'] == $interval && $options['interval_count'] == (int) $interval_count) {
					$this->cycle = $cycle;
					$this->label = $options['label'];
					$this->interval = $options['interval'];
					$this->interval_count = $options['interval_count'];
				}
			}
		}
	}

	public function getCycleName() {
		return $this->cycle;
	}

	public function getLabel() {
		return $this->label;
	}

	public function getInterval() {
		return $this->interval;
	}

	public function getIntervalCount() {
		return (int) $this->interval_count;
	}

	/**
	 * Get a list of available system intervals
	 * @return type
	 */
	public static function getCycles($params = array()) {
		$default = array(
			'weekly' => array(
				'label' => elgg_echo('stripe:interval:weekly'),
				'interval' => 'week',
				'interval_count' => 1,
			),
			'biweekly' => array(
				'label' => elgg_echo('stripe:interval:biweekly'),
				'interval' => 'week',
				'interval_count' => 2,
			),
			'monthly' => array(
				'label' => elgg_echo('stripe:interval:monthly'),
				'interval' => 'month',
				'interval_count' => 1,
			),
			'quarterly' => array(
				'label' => elgg_echo('stripe:interval:quarterly'),
				'interval' => 'month',
				'interval_count' => 3,
			),
			'semiannually' => array(
				'label' => elgg_echo('stripe:interval:semiannually'),
				'interval' => 'month',
				'interval_count' => 6,
			),
			'yearly' => array(
				'label' => elgg_echo('stripe:interval:yearly'),
				'interval' => 'year',
				'interval_count' => 1,
			),
		);

		return elgg_trigger_plugin_hook('config.cycles', 'stripe', $params, $default);
	}

}
