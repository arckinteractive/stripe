<?php
$subscription = elgg_extract('object', $vars);

if (!$subscription instanceof Stripe_Subscription) {
	return;
}

$status = array();
switch ($subscription->status) {

	case 'active' :
		$status_label = elgg_echo('stripe:subscriptions:status:active');
		break;

	case 'trialing' :
		$status_label = elgg_echo('stripe:subscriptions:status:trialing');
		$status[] = elgg_echo('stripe:subscriptions:status:trial_ends_at', array(date('M d, Y', $subscription->trial_end)));
		break;

	case 'past_due' :
		$status_label = elgg_echo('stripe:subscriptions:status:past_due');
		break;

	case 'canceled' :
	case 'unpaid' :
		$status_label = elgg_echo('stripe:subscriptions:status:inactive');
		break;
}

if ($subscription->canceled_at) {
	$status[] = elgg_echo('stripe:subscriptions:status:canceled_at', array(date('M d, Y', $subscription->canceled_at)));
}
if ($subscription->cancel_at_period_end) {
	$status[] = elgg_echo('stripe:subscriptions:status:ends_at', array(date('M d, Y', $subscription->current_period_end)));
}
if ($subscription->ended_at) {
	$status[] = elgg_echo('stripe:subscriptions:status:ended_at', array(date('M d, Y', $subscription->ended_at)));
}


$cycle = new StripeBillingCycle('', $subscription->plan->interval, $subscription->plan->interval_count);
$pricing = new StripePricing($subscription->plan->amount / 100, 0, 0, $subscription->plan->currency);

$actions = elgg_view_menu('stripe-actions', array(
	'object' => $subscription,
	'sort_by' => 'priority',
	'vars' => $vars,
		));
?>

<div class="stripe-row stripe-object stripe-subscription">
	<div class="stripe-col-3of12 stripe-subscription-plan-name">
		<?php
		echo $subscription->plan->name;
		?>
	</div>
	<div class="stripe-col-3of12 stripe-subscription-status">
		<?php
		echo $status_label;
		?>
	</div>
	<div class="stripe-col-6of12 stripe-subscription-details">
		<div class="stripe-info">
			<?php
			echo '<span class="stripe-amount">' . $pricing->getHumanAmount() . '</span> ' . $cycle->getLabel();
			?>
		</div>
		<div class="stripe-info">
			<?php
			echo implode('<br />', $status);
			?>
		</div>
		<?php echo $actions ?>
	</div>
</div>
