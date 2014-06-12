<?php

/**
 * Display a price input with a X.XX pattern
 *
 * @uses string $vars['name'] Name of the input. Defaults to 'price'
 * @uses integer $vars['value'] Amount.
 */

$name = elgg_extract('name', $vars, 'price');
$value = (float) elgg_extract('value', $vars, 0);

$vars['pattern'] = "\d+(\.\d{2})?";
$vars['value'] = number_format($value, 2, '.', '');

echo elgg_view('input/text', $vars);
