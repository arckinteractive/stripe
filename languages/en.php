<?php

$english = array(

	'menu:page:header:stripe' => 'Stripe',

	'admin:stripe' => 'Stripe',
	'admin:stripe:customers' => 'Customers',
	
	'stripe:settings:environment' => 'Environment',
	'stripe:settings:environment:select' => 'Select environment',
	'stripe:settings:environment:sandbox' => 'SANDBOX - TEST',
	'stripe:settings:environment:production' => 'PRODUCTION - LIVE',
	'stripe:settings:sandbox:api_keys' => 'STAGING - Test Api Keys',
	'stripe:settings:sandbox:secret_key' => 'Secret Key',
	'stripe:settings:sandbox:publishable_key' => 'Publishable Key',
	'stripe:settings:production:api_keys' => 'PRODUCTION - Live Api Keys',
	'stripe:settings:production:secret_key' => 'Secret Key',
	'stripe:settings:production:publishable_key' => 'Publishable Key',

	'stripe:generic_error' => 'Something went wrong with your request: %s',
	'stripe:invalid_request_error' => 'There was a problem with your request: %s',
	'stripe:api_error' => 'There was a problem communicating with our stripe: %s',
	'stripe:authentication_error' => 'There was a problem authenticating your account. Please check your API keys: %s',
	'stripe:card_error' => 'There was a problem with processing your card: %s',
	'stripe:card_error:incorrect_number' => 'The card number is incorrect',
	'stripe:card_error:invalid_number' => 'The card number is not a valid credit card number',
	'stripe:card_error:invalid_expiry_month' => 'The card\'s expiration month is invalid',
	'stripe:card_error:invalid_expiry_year' => 'The card\'s expiration year is invalid',
	'stripe:card_error:invalid_cvc' => 'The card\'s security code is invalid',
	'stripe:card_error:expired_card' => 'The card has expired',
	'stripe:card_error:incorrect_zip' => 'The card\'s zip code failed validation',
	'stripe:card_error:card_declined' => 'The card was declined',
	'stripe:card_error:missing' => 'There is no card on file for your account',
	'stripe:card_error:processing_error' => 'An error occurred while processing the card',
	'stripe:card_error:rate_limit' => 'An error occurred due to requests hitting the API too quickly. Please email support@stripe.com if you\'re consistently running into this error',

	'stripe:access_error' => 'You do not have sufficient permissions to perform this action',

	'stripe:list:empty' => 'No items to display',

	'stripe:customers:title' => 'Custsomer %s',
	'stripe:customers:id' => 'Customer ID',
	'stripe:customers:email' => 'Email',
	'stripe:customers:user' => 'User',
	'stripe:customers:description' => 'Description',
	'stripe:customers:list' => 'Customers',
	'stripe:customers:sync' => 'Synchronize customers',
	
	'stripe:cards' => 'Cards',
	'stripe:cards:none' => 'There are no cards associated with your account',
	'stripe:cards:all' => 'Credit cards',
	'stripe:cards:list' => 'Your cards',
	'stripe:cards:add' => 'Add credit card',
	'stripe:cards:add:success' => 'Card was successfully saved',
	'stripe:cards:add:error' => 'Card could not be saved',
	'stripe:cards:remove' => 'Remove',
	'stripe:cards:remove:success' => 'Card was successfully removed from your account',
	'stripe:cards:remove:error' => 'Card could not be removed',
	'stripe:cards:default' => 'Default card',
	'stripe:cards:set_default' => 'Set as default',
	'stripe:cards:set_default:success' => 'Default card has changed',
	'stripe:cards:set_default:error' => 'Default card could not be changed',
	'stripe:cards:card_details' => 'Card Details',
	'stripe:cards:card_address' => 'Billing Address',
	'stripe:cards:title' => '%s-%s',
	'stripe:cards:instructions' => '',
	'stripe:cards:name' => 'Card Holder Name',
	'stripe:cards:number' => 'Card Number',
	'stripe:cards:cvc' => 'CVC',
	'stripe:cards:expires' => 'Expires %s/%s',
	'stripe:cards:expiration' => 'Expiration',
	'stripe:cards:address_line1' => 'Street Address',
	'stripe:cards:address_zip' => 'Postal/ZIP Code',
	'stripe:cards:accepted_cards' => 'Accepted Cards',
	'stripe:cards:validating' => 'Validating...',
	'stripe:cards:saving' => 'Saving...',
	'stripe:cards:select' => 'Select card ...',
	'stripe:cards:remember' => 'Remember this card',
	'stripe:cards:unsupported_type' => 'This card type is not supported for this order',

	'stripe:charges:title' => 'Charge <i>%s</i>',
	'stripe:charges:all' => 'Transaction history',
	'stripe:charges:view' => 'Charge details',
	'stripe:charges:status' => 'Status',
	'stripe:charges:status:paid' => 'Paid',
	'stripe:charges:status:refunded' => 'Refunded',
	'stripe:charges:status:partially_refunded' => 'Partially Refunded',
	'stripe:charges:status:pending' => 'Pending',
	'stripe:charges:status:failed' => 'Failed',
	'stripe:charges:refund' => '%s refunded on %s',

	'stripe:invoices:title' => 'Invoice <i>%s</i>',
	'stripe:invoices:items:title' => 'Invoice items <i>%s</i>',
	'stripe:invoices:all' => 'Invoices',
	'stripe:invoices:view' => 'Invoice details',
	'stripe:invoices:items:view' => 'View details',
	'stripe:invoices:upcoming' => 'Upcoming invoice',
	'stripe:invoices:status:paid' => 'Paid',
	'stripe:invoices:status:upcoming' => 'Upcoming',
	'stripe:invoices:status:closed' => 'Failed to collect payment',
	'stripe:invoices:status:open' => 'Attempting to collect payment',
	'stripe:invoices:next_payment_attempt' => 'Next attempt to collect payment will be made on %s',
	'stripe:invoices:period' => 'Period from %s to %s',
	'stripe:invoices:incl_discount' => 'Total includes a discount of %s',
	'stripe:invoices:items' => 'Invoice items',
	'stripe:ivnoices:items:type:subscription' => 'Subscription',
	'stripe:ivnoices:items:type:invoiceitem' => 'Other',

	'stripe:address:address_line1' => 'Street Address',
	'stripe:address:address_line2' => 'Street Address 2',
	'stripe:address:address_city' => 'City/Town',
	'stripe:address:address_state' => 'State/Province',
	'stripe:address:address_zip' => 'ZIP/Postal Code',
	'stripe:address:address_country' => 'Country',
	'stripe:address:edit:error_required_field_empty' => 'Please check that you have filled out all the required fields',
	'stripe:address:edit:error_generic' => 'Address could not be saved',
	'stripe:address:edit:success' => 'Address successfully saved',

	'stripe:output:price' => '%s %s',

	'stripe:interval:weekly' => 'weekly',
	'stripe:interval:biweekly' => 'every 2 weeks',
	'stripe:interval:monthly' => 'monthly',
	'stripe:interval:quarterly' => 'every 3 months',
	'stripe:interval:semiannually' => 'every 6 months',
	'stripe:interval:yearly' => 'yearly',

	'stripe:subscriptions:title' => 'Subscription <i>%s</i>',
	'stripe:subscriptions:all' => 'Active subscriptions',
	'stripe:subscriptions:view' => 'View details',
	'stripe:subscriptions:cancel' => 'Cancel subscription',
	'stripe:subscriptions:plan:details' => '<b>%s</b> %s %s',
	'stripe:subscriptions:status:active' => 'Active',
	'stripe:subscriptions:status:trialing' => 'Trialing',
	'stripe:subscriptions:status:past_due' => 'Active, payment is overdue',
	'stripe:subscriptions:status:inactive' => 'Inactive/Suspended',
	'stripe:subscriptions:status:canceled_at' => 'Canceled on %s',
	'stripe:subscriptions:status:ended_at' => 'Ended on %s',
	'stripe:subscriptions:status:ends_at' => 'Ends on %s',
	'stripe:subscriptions:status:trial_ends_at' => 'Trial ends on %s',

	'stripe:notification:charge:succeeded:subject' => 'Payment to %s succeeded',
	'stripe:notification:charge:succeeded:body' => 'Dear %s,

		Your payment of %s to %s was successful. The payment was made with you %s-%s.

		To view the details of this transactions, visit:
		%s',

	'stripe:notification:charge:failed:subject' => 'Payment to %s was not successful',
	'stripe:notification:charge:failed:body' => 'Dear %s,

		Your payment of %s to %s could not be completed.

		There was a problem with your %s-%s:
		%s.

		To view the details of this transactions, visit:
		%s',

	'stripe:notification:charge:refunded:subject' => 'You have recieved a refund from %s',
	'stripe:notification:charge:refunded:body' => 'Dear %s,

		Your have recieved a refund in the amount of %s from %s.

		Funds will be returned to your %s-%s.

		To view the details of this transactions, visit:
		%s',


);

add_translation('en', $english);
