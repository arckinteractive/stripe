<?php
/**
 * Add new card form
 *
 * @uses boolean $vars['show_footer']	Whether or not the form button bank should be displayed
 * @uses boolean $vars['show_remember'] Whether to display a checkbox to toggle remember option
 * @uses boolean $vars['zip_check']		Whether the zip code field should be included in the form
 * @uses boolean $vars['address_check'] Whether the address_line1 field should be included in the form
 */
$required = elgg_echo('required');


$merchant = new StripeMerchant();
$brands = $merchant->getSupportedCards();
foreach ($brands as $brand) {
	$supported_brands .= '<li class="stripe-accepted-card">' . elgg_view('output/img', array(
				'src' => elgg_get_site_url() . 'mod/stripe/graphics/credit_card/' . strtolower(str_replace(' ', '', $brand)) . '.png',
	));
}
$supported_brands = '<ul class="stripe-accepted-cards">' . $supported_brands . '</ul>';

?>
<fieldset class="stripe-row" data-stripe>
	<div class="small-12 columns">
		<label><?php echo elgg_echo('stripe:cards:accepted_cards') ?></label>
		<?php echo $supported_brands ?>
	</div>
	<div class="small-12 columns">
		<label title="<?php echo $required ?>" class="required"><?php echo elgg_echo('stripe:cards:name') ?></label>
		<?php
		echo elgg_view('input/text', array(
			'data-stripe' => 'name',
			'required' => true,
			'parsley-trigger' => 'focusout',
			'parsley-validation-minlength' => 1,
			'parsley-minlength' => 6
		));
		?>
	</div>
	<?php if (elgg_extract('address_check', $vars, true)) { ?>
		<div class="small-12 large-6 columns">
			<label title="<?php echo $required ?>" class="required"><?php echo elgg_echo('stripe:cards:address_line1') ?></label>
			<?php
			echo elgg_view('input/text', array(
				'data-stripe' => 'address-line1',
				'required' => true,
				'parsley-trigger' => 'focusout',
				'parsley-validation-minlength' => 1,
				'parsley-minlength' => 1
			));
			?>
		</div>
	<?php } ?>
	<?php if (elgg_extract('zip_check', $vars, true)) { ?>
		<div class="small-12 large-6 columns">
			<label title="<?php echo $required ?>" class="required"><?php echo elgg_echo('stripe:cards:address_zip') ?></label>
			<?php
			echo elgg_view('input/text', array(
				'size' => 10,
				'maxlength' => 10,
				'data-stripe' => 'address-zip',
				'required' => true,
				'parsley-trigger' => 'focusout',
				'parsley-maxlength' => 10,
				'parsley-validation-minlength' => 1,
				'parsley-minlength' => 1
			));
			?>
		</div>
	<?php } ?>
	<div class="small-12 large-8 columns">
		<label title="<?php echo $required ?>" class="required"><?php echo elgg_echo('stripe:cards:number') ?></label>
		<?php
		echo elgg_view('input/text', array(
			'size' => 20,
			'maxlength' => 20,
			'data-stripe' => 'number',
			'required' => true,
			'parsley-trigger' => 'focusout',
			'parsley-type' => 'digits',
			'parsley-maxlength' => 20,
			'parsley-validation-minlength' => 1,
			'parsley-minlength' => 6
		));
		?>
	</div>
	<div class="small-12 large-4 columns">
		<label title="<?php echo $required ?>" class="required"><?php echo elgg_echo('stripe:cards:cvc') ?></label>
		<?php
		echo elgg_view('input/text', array(
			'size' => 4,
			'maxlength' => 4,
			'data-stripe' => 'cvc',
			'required' => true,
			'parsley-trigger' => 'focusout',
			'parsley-type' => 'digits',
			'parsley-maxlength' => 4,
			'parsley-validation-minlength' => 1,
			'parsley-minlength' => 1
		));
		?>
	</div>
	<div class="small-12 large-6 columns">
		<label title="<?php echo $required ?>" class="required"><?php echo elgg_echo('stripe:cards:expiration') ?></label>
		<?php
		echo elgg_view('input/dropdown', array(
			'data-stripe' => 'exp-month',
			'options' => range(1, 12),
			'required' => true,
			'class' => 'small-6 columns'
		));
		echo elgg_view('input/dropdown', array(
			'data-stripe' => 'exp-year',
			'options' => range((int) date("Y"), (int) date("Y") + 20),
			'required' => true,
			'class' => 'small-6 columns'
		));
		?>
	</div>
	<div class="small-12 large-6 columns">
		<?php
		if (elgg_extract('show_remember', $vars, false)) {
			echo '<label class="text-left">';
			echo elgg_view('input/checkbox', array(
				'name' => 'stripe-remember',
				'checked' => true,
				'value' => 1,
				'default' => false,
			));
			echo '<span class="inline">' . elgg_echo('stripe:cards:remember') . '</span>';
			echo '</label>';
		}
		?>
	</div>
	<div class="large-12 columns">
		<div class="stripe-errors"></div>
	</div>
</fieldset>

<?php
if (elgg_extract('show_footer', $vars, true)) {
	echo '<div class="row elgg-foot text-right">';
	echo elgg_view('input/submit', array(
		'value' => elgg_echo('save')
	));
	echo '</div>';
}