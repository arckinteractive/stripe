<?php
$charge = elgg_extract('object', $vars);

if (!$charge instanceof Stripe_Charge) {
	return;
}

$class = 'stripe-status-active';

$status = array();
if ($charge->refunded) {
	$status_label = elgg_echo('stripe:charges:status:refunded');
	$class = 'stripe-status-inactive';
} else if ($charge->amount_refunded > 0) {
	$status_label = elgg_echo('stripe:charges:status:partially_refunded');
} else if ($charge->paid) {
	$status_label = elgg_echo('stripe:charges:status:paid');
} else {
	$status_label = elgg_echo('stripe:charges:status:failed');
	$status[] = $charge->failure_message;
	$class = 'stripe-status-inactive';
}
?>
<div class="stripe-row stripe-object stripe-charge <?php echo $class ?>">
	<div class="stripe-col-2of12 stripe-charge-timestamp">
		<?php echo date('M d, Y', $charge->created); ?>
	</div>
	<div class="stripe-col-2of12 stripe-amount stripe-charge-amount">
		<?php
		$pricing = new StripePricing($charge->amount / 100, 0, 0, $charge->currency);
		echo $pricing->getHumanAmount();
		?>
	</div>
	<div class="stripe-col-2of12 stripe-status stripe-charge-status">
		<?php
		echo '<span>' . $status_label . '</span>';
		?>
	</div>
	<div class="stripe-col-6of12 stripe-details">

		<div class="stripe-info">
			<?php
			echo elgg_view('stripe/objects/card', array(
				'object' => $charge->card,
				'full_view' => false,
			));
			?>
		</div>

		<div class="stripe-info">
			<?php
			echo implode('<br />', $status);

			if (sizeof($charge->refunds)) {
				echo '<ul class="stripe-charge-refunds">';
				foreach ($charge->refunds as $refund) {
					$pricing = new StripePricing($refund->amount / 100, 0, 0, $refund->currency);
					$refund_amount = '<span class="stripe-amount stripe-refund-amount">' . $pricing->getHumanAmount() . '</span>';
					echo '<li class="stripe-charge-refund">';
					echo '<div>' . elgg_echo('stripe:charges:refund', array($refund_amount, date('M d, Y', $refund->created))) . '</div>';
					echo '</li>';
				}
				echo '</ul>';
			}
			?>
		</div>

		<div class="stripe-info">
			<?php
			echo $charge->description;
			?>
		</div>

		<?php
		echo elgg_view_menu('stripe-actions', array(
			'object' => $charge,
			'sort_by' => 'priority',
			'vars' => $vars,
		))
		?>
	</div>
</div>

