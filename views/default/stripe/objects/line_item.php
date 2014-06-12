<?php
$invoiceitem = elgg_extract('object', $vars);

if (!$invoiceitem instanceof Stripe_Object) {
	return;
}
?>
<div class="stripe-row stripe-object stripe-invoiceitem <?php echo $class ?>">
	<div class="stripe-col-6of12 stripe-details">

		<?php
		echo '<div class="stripe-info">';
		echo elgg_echo('stripe:ivnoices:items:type:' . $invoiceitem->type);
		echo '</div>';
		
		switch ($invoiceitem->type) {

			default :
			case 'invoiceitem' :
				echo '<div class="stripe-info">';
				echo $invoiceitem->description;
				echo '</div>';
				break;

			case 'subscription' :
				$cycle = new StripeBillingCycle('', $invoiceitem->plan->interval, $invoiceitem->plan->interval_count);
				$pricing = new StripePricing($invoiceitem->plan->amount / 100, 0, 0, $invoiceitem->plan->currency);
				echo '<div class="stripe-info">';
				echo $invoiceitem->plan->name;
				echo '</div>';
				echo '<div class="stripe-info">';
				echo $pricing->getHumanAmount() . ' ' . $cycle->getLabel();
				echo '</div>';
				break;
		}
		echo elgg_view_menu('stripe-actions', array(
			'object' => $invoiceitem,
			'sort_by' => 'priority',
			'vars' => $vars,
		))
		?>
	</div>
	<div class="stripe-col-4of12 stripe-invoiceitem-type">
		<?php
		echo date('M d, Y', $invoiceitem->period->start) . ' - ' . date('M d, Y', $invoiceitem->period->end);
		?>
	</div>
	<div class="stripe-col-2of12 stripe-amount stripe-invoiceitem-amount">
		<?php
		$pricing = new StripePricing($invoiceitem->amount / 100, 0, 0, $invoiceitem->currency);
		echo $pricing->getHumanAmount();
		?>
	</div>

</div>

