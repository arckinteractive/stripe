<?php

$objects = elgg_extract('objects', $vars);
$limit = elgg_extract('limit', $vars);
$ending_before = elgg_extract('ending_before', $vars);
$starting_after = elgg_extract('starting_after', $vars);

if ($objects && $objects->data) {
	echo '<ul class="elgg-list stripe-list">';
	foreach ($objects->data as $object) {
		$type = $object->object;
		echo '<li class="elgg-item stripe-item stripe-' . $type . '">';
		echo elgg_view("stripe/objects/$type", array(
			'object' => $object,
		));
		echo '</li>';
	}
	echo '</ul>';

	if ($objects->has_more || $starting_after) {
		echo '<ul class="elgg-pagination stripe-pagination">';
		if ($starting_after) {
			echo '<li class="prev">';
			echo elgg_view('output/url', array(
				'text' => elgg_echo('previous'),
				'href' => elgg_http_add_url_query_elements(current_page_url(), array(
					'starting_after' => '',
					'ending_before' => $objects->data[0]->id,
					'limit' => $limit
				))
			));
			echo '</li>';
		}

		if ($objects->has_more) {
			echo '<li class="next">';
			echo elgg_view('output/url', array(
				'text' => elgg_echo('next'),
				'href' => elgg_http_add_url_query_elements(current_page_url(), array(
					'ending_before' => '',
					'starting_after' => end($objects->data)->id,
					'limit' => $limit
				))
			));
			echo '</li>';
		}
		echo '</ul>';
	}
} else {
	echo '<p>' . elgg_echo('stripe:list:empty') . '</p>';
}