<?php
$invoice = elgg_extract('object', $vars);

if (!$invoice instanceof Stripe_Invoice) {
	return;
}

$status = array();

if ($invoice->paid) {
	$status_label = elgg_echo('stripe:invoices:status:paid');
} else if ($invoice->date > time()) {
	$status_label = elgg_echo('stripe:invoices:status:upcoming');
} else if (!$invoice->closed) {
	$status_label = elgg_echo('stripe:invoices:status:open');
	$status[] = elgg_echo('stripe:invoices:next_payment_attempt', array(date('M d, Y', $invoice->next_payment_attempt)));
} else {
	$status_label = elgg_echo('stripe:invoices:status:closed');
}

$actions = elgg_view_menu('stripe-actions', array(
	'object' => $invoice,
	'sort_by' => 'priority',
	'vars' => $vars,
		));
?>

<div class="stripe-row stripe-object stripe-invoice">
	<div class="stripe-col-2of12 stripe-invoice-timestamp">
		<?php echo date('M d, Y', $invoice->date); ?>
	</div>

	<div class="stripe-col-2of12 stripe-amount stripe-invoice-total">
		<?php
		$pricing = new StripePricing($invoice->total / 100, 0, 0, $invoice->currency);
		echo $pricing->getHumanAmount();
		?>
	</div>
	<div class="stripe-col-2of12 stripe-status stripe-invoice-status">
		<?php
		echo $status_label;
		?>
	</div>
	<div class = "stripe-col-6of12 stripe-invoice-details">
		<div class="stripe-info">
			<?php
			echo elgg_echo('stripe:invoices:period', array(date('M d, Y', $invoice->period_start), date('M d, Y', $invoice->period_end)));
			?>
		</div>
		<div class="stripe-info">
			<?php
			if ($discount = $invoice->discount) {
				$coupon = $discount->coupon;
				if ($coupon->percent_off) {
					$discount_str = $coupon->percent_off . '%';
				} else {
					$pricing = new StripePricing($coupon->amount_off / 100, 0, 0, $coupon->currency);
					$discount_str = $pricing->getHumanAmount();
				}
				echo elgg_echo('stripe:invoices:incl_discount', array($discount_str));
			}
			?>
		</div>
		<div class="stripe-info">
			<?php
			echo implode('<br />', $status);
			?>
		</div>
		<div class="stripe-info">
			<?php
			echo $invoice->description;
			?>
		</div>
		<?php echo $actions ?>
	</div>
</div>