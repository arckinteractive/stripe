<?php

$card = elgg_extract('object', $vars);
$full = elgg_extract('full_view', $vars);

if (!$card instanceof Stripe_Card) {
	return;
}

$img = strtolower(str_replace(' ', '', $card->type));
$icon = elgg_view('output/img', array(
	'src' => "mod/stripe/graphics/credit_card/$img.png",
	'class' => 'stripe-card-icon',
	'width' => 50
));

$title = "{$card->type}-{$card->last4}";

$actions = elgg_view_menu('stripe-actions', array(
	'object' => $card,
	'sort_by' => 'priority',
));

if (!$full) {
	echo elgg_view_image_block($icon, $title);
	return;
}
?>

<div class="stripe-row stripe-object stripe-card">
	<div class="stripe-col-1of12 stripe-card-icon">
		<?php echo $icon ?>
	</div>
	<div class="stripe-col-4of12 stripe-card-title">
		<?php echo $title ?>
	</div>
	<div class="stripe-col-4of12 stripe-card-expiry">
		<?php echo "{$card->exp_month} / {$card->exp_year}" ?>
	</div>
	<div class="stripe-col-3of12 stripe-card-actions">
		<?php echo $actions ?>
	</div>
</div>