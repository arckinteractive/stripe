<?php
$customer = elgg_extract('object', $vars);

if (!$customer instanceof Stripe_Customer) {
	return;
}
?>

<div class="stripe-row stripe-object stripe-customer">
	<div class="stripe-col-6of12 stripe-customer-user">
		<?php
		if ($user = stripe_get_user_from_customer_id($customer->id)) {
			$icon = elgg_view_entity_icon($user, 'tiny');
			$link = elgg_view('output/url', array(
				'text' => $user->name,
				'href' => $user->getURL()
			));
			$alt = '';
			if (is_array($user->stripe_customer_id)) {
				$count = sizeof($user->stripe_customer_id);
				if ($user->getPrivateSetting('stripe_customer_id') == $customer->id) {
					$alt = "Primary ($count total)";
				} else {
					$alt = "Alternative ($count total)";
				}
			}

			echo elgg_view_image_block($icon, $link, array(
				'image_alt' => $alt,
			));
		}
		?>
	</div>
	<div class="stripe-col-6of12 stripe-customer-details">
		<div class="stripe-info">
			<?php echo $customer->id ?>
		</div>
		<div class="stripe-info">
			<?php echo $customer->email ?>
		</div>
		<div class="stripe-info">
			<?php echo $customer->description ?>
		</div>
	</div>
</div>