<?php

$entity = elgg_extract('entity', $vars);

echo '<h3>' . elgg_echo('stripe:settings:environment') . '</h3>';

echo '<div>';
echo '<label>' . elgg_echo('stripe:settings:environment:select') . '</label>';
echo elgg_view('input/dropdown', array(
	'name' => 'params[environment]',
	'value' => ($entity->environment) ? $entity->environment : StripeClientFactory::ENV_SANDBOX,
	'options_values' => array(
		StripeClientFactory::ENV_SANDBOX => elgg_echo('stripe:settings:environment:sandbox'),
		StripeClientFactory::ENV_PRODUCTION => elgg_echo('stripe:settings:environment:production'),
	)
));
echo '</div>';

echo '<h3>' . elgg_echo('stripe:settings:sandbox:api_keys') . '</h3>';

echo '<div>';
echo '<label>' . elgg_echo('stripe:settings:sandbox:secret_key') . '</label>';
echo elgg_view('input/text', array(
	'name' => 'params[stripe_test_secret_key]',
	'value' => $entity->stripe_test_secret_key,
));
echo '</div>';

echo '<div>';
echo '<label>' . elgg_echo('stripe:settings:sandbox:publishable_key') . '</label>';
echo elgg_view('input/text', array(
	'name' => 'params[stripe_test_publishable_key]',
	'value' => $entity->stripe_test_publishable_key,
));
echo '</div>';

echo '<h3>' . elgg_echo('stripe:settings:production:api_keys') . '</h3>';

echo '<div>';
echo '<label>' . elgg_echo('stripe:settings:production:secret_key') . '</label>';
echo elgg_view('input/text', array(
	'name' => 'params[stripe_production_secret_key]',
	'value' => $entity->stripe_production_secret_key,
));
echo '</div>';

echo '<div>';
echo '<label>' . elgg_echo('stripe:settings:production:publishable_key') . '</label>';
echo elgg_view('input/text', array(
	'name' => 'params[stripe_production_publishable_key]',
	'value' => $entity->stripe_production_publishable_key,
));
echo '</div>';
