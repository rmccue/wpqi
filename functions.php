<?php

function current_url() {
	return (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . trailingslashit(dirname($_SERVER['REQUEST_URI']));
}

/**
 * Navigates through an array and removes slashes from the values.
 * Identical to WordPress's stripslashes_deep other than the name to prevent conflict.
 *
 * If an array is passed, the array_map() function causes a callback to pass the
 * value back to the function. The slashes from this value will removed.
 *
 * @since 2.0.0
 *
 * @param array|string $value The array or string to be striped.
 * @return array|string Stripped array (or string in the callback).
 */
function _stripslashes_deep($value) {
	$value = is_array($value) ? array_map('_stripslashes_deep', $value) : stripslashes($value);
	return $value;
}
