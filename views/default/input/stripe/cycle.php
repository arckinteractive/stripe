<?php

$cycles = StripeBillingCycle::getCycles($vars);

foreach ($cycles as $short => $options) {
	$vars['options_values'][$short] = $options['label'];
}

echo elgg_view('input/dropdown', $vars);
