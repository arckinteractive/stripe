<?php

/**
 * Display a dropdown of currencies
 *
 * @uses string $vars['name'] Name of the input
 * @uses string $vars['value'] Currency code
 * @uses array $vars['options'] An array of available currencies
 */

$entity = elgg_extract('entity', $vars);

$currencies = $vars['options'];
unset($vars['options']);

$vars['options_values'] = array();

$currencies = StripeCurrencies::getCurrencies();
foreach ($currencies as $currency_code => $options) {
	$vars['options_values'][$currency_code] = $currency_code;
}

echo elgg_view('input/dropdown', $vars);
